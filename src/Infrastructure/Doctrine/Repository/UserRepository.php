<?php

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\UniqId;
use App\Domain\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

class DoctrineUserRepository implements UserRepositoryInterface{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ){}

    public function save(User $user,): void{
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function findById(UniqId $id,): ?User{
        return $this->entityManager->getRepository(User::class)
                                   ->find($id->toString());
    }

    public function findByEmail(Email $email,): ?User{
        return $this->entityManager->getRepository(User::class)
                                   ->findOneBy(['email' => $email->toString()]);
    }

    public function findByEmailString(string $email,): ?User{
        return $this->entityManager->getRepository(User::class)
                                   ->findOneBy(['email' => $email]);
    }

    public function findByCriteria(
        array   $criteria,
        int     $page = 1,
        int     $perPage = 10,
        ?string $sortBy = null,
        string  $sortDirection = 'ASC',
    ): array{
        $qb = $this->createQueryBuilder();

        if(isset($criteria['search'])){
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

        if(isset($criteria['search'])){
            $qb->andWhere('u.name LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        return $qb->getQuery()
                  ->getSingleScalarResult();
    }

}
