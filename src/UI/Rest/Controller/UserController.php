<?php

namespace App\UI\Rest\Controller;

use App\Application\Command\CreateUser\CreateUserCommand;
use App\Application\Query\GetUserProfile\GetUserProfileHandler;
use App\Application\Query\GetUserProfile\GetUserProfileQuery;
use App\Domain\User\Exception\UserNotFoundException;
use App\UI\Rest\Request\CreateUserRequest;
use App\UI\Rest\Request\ListUsersRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
final  class UserController extends AbstractController{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private ValidatorInterface  $validator,
    ){}

    #[Route('', methods: ['POST'])]
    public function create(Request $request,): JsonResponse{
        $createUserRequest = CreateUserRequest::fromRequest($request);
        $violations        = $this->validator->validate($createUserRequest);

        if(count($violations) > 0){
            // Handle validation errors
            return new JsonResponse(['errors' => 'Well, something wrong happened...'], Response::HTTP_BAD_REQUEST);
        }

        $command = new CreateUserCommand(
            $createUserRequest->email, $createUserRequest->name
        );

        $user = $this->commandBus->dispatch($command)->last(HandledStamp::class)->getResult();

        return new JsonResponse(
            [
                'id' => $user->id()
                             ->toString()
            ], Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getProfile(string $id,): JsonResponse{
        try{
            $query   = new GetUserProfileQuery($id);
            $profile = $this->queryBus->dispatch($query);

            return new JsonResponse($profile->last(HandledStamp::class)->getResult());
        }
        catch(UserNotFoundException|ExceptionInterface $e){
            throw $this->createNotFoundException('User not found');
        }
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request,): JsonResponse{
        $listUsersRequest = ListUsersRequest::fromRequest($request);

        $violations = $this->validator->validate($listUsersRequest);
        if(count($violations) > 0){
            return new JsonResponse(
                [
                    'errors' => array_map(
                        fn($violation,) => [
                            'property' => $violation->getPropertyPath(),
                            'message'  => $violation->getMessage(),
                        ],
                        iterator_to_array($violations)
                    )
                ], Response::HTTP_BAD_REQUEST
            );
        }

        $response = $this->queryBus->dispatch(
            $listUsersRequest->toListUsersQuery()
        );

        return new JsonResponse($response->last(HandledStamp::class)->getResult());
    }
}
