<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\UniqId;
use App\Domain\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use function key_exists;

readonly class UserRepository implements UserProviderInterface, UserRepositoryInterface{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    public function save(User $user,): void{
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findByEmail(Email $email,): ?User{
        return $this->entityManager->getRepository(User::class)
                                   ->findOneBy(['email' => $email->toString()]);
    }

    public function findByCriteria(
        array $criteria,
        int $page = 1,
        int $perPage = 10,
        ?string $sortBy = null,
        string $sortDirection = 'ASC',
    ): array{
        $qb = $this->createQueryBuilder();

        if(key_exists("search", $criteria)){
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if($sortBy){
            $qb->orderBy('u.' . $sortBy, $sortDirection);
        }

        return $qb->setFirstResult(($page - 1) * $perPage)
                  ->setMaxResults($perPage)
                  ->getQuery()
                  ->getResult();
    }

    private function createQueryBuilder(): QueryBuilder{
        return $this->entityManager->createQueryBuilder()
                                   ->select('u')
                                   ->from(User::class, 'u');
    }

    public function countByCriteria(array $criteria,): int{
        $qb = $this->createQueryBuilder('u')
                   ->select('COUNT(u.id)');

        if(key_exists("search", $criteria)){
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        return $qb->getQuery()
                  ->getSingleScalarResult();
    }

    public function clear(): void{
        $this->createQueryBuilder()
             ->delete()
             ->getQuery()
             ->execute();
    }

    public function refreshUser(UserInterface $user,): UserInterface{
        $class = get_class($user);
        if(!$this->supportsClass($class)){
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        if(!$refreshedUser = $this->findById(
            $user->id()
                 ->toString()
        )){
            throw new User\Exception\UserNotFoundException(
                sprintf(
                    'User with id %s not found',
                    json_encode(
                        $user->id()
                             ->toString()
                    )
                )
            );
        }

        return $refreshedUser;
    }

    public function supportsClass($class,): bool{
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function findById(UniqId $id,): ?User{
        return $this->entityManager->getRepository(User::class)
                                   ->find($id->toString());
    }

    public function loadUserByIdentifier(string $identifier,): UserInterface{
        return $this->findByEmailString($identifier);
    }

    public function findByEmailString(string $email,): ?User{
        return $this->entityManager->getRepository(User::class)
                                   ->findOneBy(['email' => $email]);
    }
}
