<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    public function createUser(User $user)
    {
        $user->setDateUpdated(new DateTime());
        $user->setDateAdded(new DateTime());
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $user;
    }

    public function updateUser(User $user)
    {
        $user->setDateUpdated(new DateTime());
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        return $user;
    }

    public function deleteUser(User $user) {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($user);
        $entityManager->flush();
        return $user;
    }

    public function findByParams(string $sort,  string $order, int $count)
    {
        $query = $this->createQueryBuilder('p')
            ->addOrderBy('p.'.$sort, $order);
        if ($count !== null && $count > 0) {
            $query->setMaxResults($count);
        }
        return $query->getQuery()
            ->getResult()
            ;
    }

    public function findApiTokensByParams(User $user, string $sort,  string $order, int $count)
    {
        $em = $this->getEntityManager();
        return $em->createQuery("SELECT apitok FROM App\Entity\ApiToken apitok
                                   WHERE apitok.user = :user")
            ->setParameter('user', $user)
            ->getResult();
    }

    public function findByEmail(string $email)
    {
        return $this->findOneBy(["email" => $email]);
    }
}
