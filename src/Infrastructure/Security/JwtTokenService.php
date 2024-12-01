<?php

namespace App\Infrastructure\Security;

use App\Domain\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class JwtTokenService
{
    private const int ACCESS_TOKEN_EXPIRY  = 3600;    // 1 hour
    private const int REFRESH_TOKEN_EXPIRY = 2592000; // 30 days

    public function __construct(
        private readonly ContainerBagInterface $params,
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function createAccessToken(User $user): string
    {
        return $this->generateToken($user, self::ACCESS_TOKEN_EXPIRY, 'access');
    }

    public function createRefreshToken(User $user): string
    {
        return $this->generateToken($user, self::REFRESH_TOKEN_EXPIRY, 'refresh');
    }

    private function generateToken(User $user, int $expiry, string $type): string
    {
        $secret = $this->params->get('app.jwt_secret');
        $now = time();

        $payload = [
            'iat' => $now,
            'exp' => $now + $expiry,
            'sub' => $user->id()->toString(),
            'email' => $user->email()->toString(),
            'type' => $type,
            'role' => $user->role()->value
        ];

        return JWT::encode($payload, $secret, 'HS256');
    }

    public function validateRefreshToken(string $token): User
    {
        $secret = $this->params->get('app.jwt_secret');

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            if ($decoded->type !== 'refresh') {
                throw new \InvalidArgumentException('Invalid token type');
            }

            $user = $this->userRepository->findById(
                \App\Domain\Shared\ValueObject\UniqId::fromString($decoded->sub)
            );

            if (!$user) {
                throw new \InvalidArgumentException('User not found');
            }

            return $user;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid refresh token');
        }
    }

    public function validateAccessToken(string $token): ?User
    {
        $secret = $this->params->get('app.jwt_secret');

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            if ($decoded->type !== 'access') {
                throw new \InvalidArgumentException('Invalid token type');
            }

            return $this->userRepository->findById(
                \App\Domain\Shared\ValueObject\UniqId::fromString($decoded->sub)
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}