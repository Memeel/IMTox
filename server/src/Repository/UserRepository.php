<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use phpDocumentor\Reflection\Types\Array_;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    public function getUsers(): array
    {
        return array_filter(
            $this->findAll(),
            function (User $user): bool {
                return $user->getState() !== "deleted";
            }
        );
    }

   public function getNumberActiveUsers(array $users): int
    {
        return count(array_filter($users, fn($user) => $user->getState()->value === 'active'));
    }

    public function getNumberLockedUsers(array $users): int
    {
        return count(array_filter($users, fn($user) => $user->getState()->value === 'locked'));
    }
}
