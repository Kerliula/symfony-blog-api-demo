<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findByOwner(User $user)
    {
        return $this->findBy(
            ['owner' => $user],
            ['createdAt' => 'DESC'],
        );
    }

    public function countAll(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        if ($search !== null && $search !== '') {
            $qb->where('p.title LIKE :search OR p.content LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findPaginated(int $offset, int $limit, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search !== null && $search !== '') {
            $qb->where('p.title LIKE :search OR p.content LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('p.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
