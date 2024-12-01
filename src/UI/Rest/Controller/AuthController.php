<?php

namespace App\UI\Rest\Controller;

use App\Application\Service\UserService;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Security\JwtTokenService;
use App\UI\Rest\Request\LoginRequest;
use App\UI\Rest\Request\RegisterRequest;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
final class AuthController extends BaseController{
    public function __construct(
        private readonly MessageBusInterface         $commandBus,
        private readonly ValidatorInterface          $validator,
        private readonly JwtTokenService             $jwtTokenService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface     $userRepository,
    ){}

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request,): JsonResponse{
        $registerRequest = RegisterRequest::fromRequest($request);
        $violations      = $this->validator->validate($registerRequest);

        if(count($violations) > 0){
            return new JsonResponse([
                                        'errors' => array_map(
                                            fn($violation,) => [
                                                'property' => $violation->getPropertyPath(),
                                                'message'  => $violation->getMessage(),
                                            ],
                                            iterator_to_array($violations)
                                        )
                                    ], Response::HTTP_BAD_REQUEST);
        }

        // Create user command
        $command = $registerRequest->toCreateUserCommand();

        // Dispatch command and get created user
        /**
         * @var \App\Domain\User $user
         */
        $user = $this->commandBus->dispatch($command)
                                 ->last(HandledStamp::class)
                                 ->getResult();

        // Generate tokens
        $accessToken  = $this->jwtTokenService->createAccessToken($user);
        $refreshToken = $this->jwtTokenService->createRefreshToken($user);

        $data = [
            'user'   => [
                'id'    => $user->id()
                                ->toString(),
                'name'  => $user->name(),
                'email' => $user->email()
                                ->toString(),
                'role'  => $user->role()->value
            ],
            'tokens' => [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken
            ]
        ];

        return new JsonResponse($data, Response::HTTP_CREATED);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request,): JsonResponse{
        $loginRequest = LoginRequest::fromRequest($request);
        $violations   = $this->validator->validate($loginRequest);

        if(count($violations) > 0){
            return new JsonResponse([
                                        'errors' => array_map(
                                            fn($violation,) => [
                                                'property' => $violation->getPropertyPath(),
                                                'message'  => $violation->getMessage(),
                                            ],
                                            iterator_to_array($violations)
                                        )
                                    ], Response::HTTP_BAD_REQUEST);
        }

        try{
            $user = $this->userRepository
                         ->findByEmailString($loginRequest->email);

            if(!$user || !$this->passwordHasher->isPasswordValid($user, $loginRequest->password)){
                throw new AuthenticationException('Invalid credentials');
            }

            // Generate tokens
            $accessToken  = $this->jwtTokenService->createAccessToken($user);
            $refreshToken = $this->jwtTokenService->createRefreshToken($user);

            return new JsonResponse([
                                        'user'   => [
                                            'id'    => $user->id()
                                                            ->toString(),
                                            'name'  => $user->name(),
                                            'email' => $user->email()
                                                            ->toString(),
                                            'role'  => $user->role()->value
                                        ],
                                        'tokens' => [
                                            'access_token'  => $accessToken,
                                            'refresh_token' => $refreshToken
                                        ]
                                    ]);
        }
        catch(AuthenticationException $e){
            $data = [
                'error'   => 'Authentication failed',
                'message' => $e->getMessage()
            ];

            return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/me', methods: ['GET'])]
    public function getCurrentUserProfile(): JsonResponse{
        $user = $this->getLoggedInUser();

        if(!$user){
            return new JsonResponse([
                                        'error' => 'User not authenticated'
                                    ], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
                                    'id'         => $user->id()
                                                         ->toString(),
                                    'name'       => $user->name(),
                                    'email'      => $user->email()
                                                         ->toString(),
                                    'role'       => $user->role()->value,
                                    'created_at' => $user->createdAt()
                                                         ->format('c')
                                ]);
    }

    #[Route('/refresh-token', methods: ['POST'])]
    public function refreshToken(Request $request,): JsonResponse{
        $refreshToken = $request->getPayload()->get('refresh_token');

        try{
            $user = $this->jwtTokenService->validateRefreshToken($refreshToken);

            $newAccessToken  = $this->jwtTokenService->createAccessToken($user);
            $newRefreshToken = $this->jwtTokenService->createRefreshToken($user);

            return new JsonResponse([
                                        'tokens' => [
                                            'access_token'  => $newAccessToken,
                                            'refresh_token' => $newRefreshToken
                                        ]
                                    ]);
        }
        catch(Exception $e){
            return new JsonResponse([
                                        'error' => 'Invalid refresh token'
                                    ], Response::HTTP_UNAUTHORIZED);
        }
    }
}