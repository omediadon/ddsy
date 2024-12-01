<?php

namespace App\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class JwtAuthenticator extends AbstractAuthenticator{
    public function __construct(
        private readonly JwtTokenService $jwtTokenService,
    ){}

    public function supports(Request $request,): ?bool{
        return $request->headers->has('Authorization') && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request,): Passport{
        $token = substr($request->headers->get('Authorization'), 7);

        $user = $this->jwtTokenService->validateAccessToken($token);

        if(!$user){
            throw new AuthenticationException('Invalid token');
        }

        return new SelfValidatingPassport(
            new UserBadge($user->getUserIdentifier(), fn() => $user)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName,): ?Response{
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception,): ?Response{
        return new JsonResponse([
                                    'error' => 'Authentication failed',
'message' => $exception->getMessage()
                                ], Response::HTTP_UNAUTHORIZED);
    }
}