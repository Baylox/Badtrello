<?php

namespace App\Repository;

use App\Entity\Board;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Account;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Board>
 */
class BoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Board::class);
    }

    /**
     * Persist a board. Single entry point for board writes.
     */
    public function save(Board $board, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($board);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Remove a board.
     */
    public function remove(Board $board, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($board);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Find boards where the user is a member (via accounts).
     * Does not include boards where the user is only the owner.
     * @param Account $account
     * @return Board[]
     */
    public function findByAccount(Account $account): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.accounts', 'a')
            ->andWhere('a = :account')
            ->setParameter('account', $account)
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find one board with its lanes and cards, ordered by position.
     * @param int $id
     * @return Board|null
     */
    public function findWithLanesAndCards(int $id): ?Board
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.lanes', 'l')->addSelect('l')
            ->leftJoin('l.cards', 'c')->addSelect('c')
            ->andWhere('b.id = :id')->setParameter('id', $id)
            ->orderBy('l.position', 'ASC')
            ->addOrderBy('c.position', 'ASC')
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Find boards visible to the given user (either owner or member).
     * Replaces findAll() for listing boards.
     * @param Account $user
     * @return Board[]
     */
    public function findVisibleForUser(Account $user): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.accounts', 'a')
            ->andWhere('b.owner = :user OR a = :user')
            ->setParameter('user', $user)
            ->distinct()
            ->orderBy('b.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all boards with their owners, for admin listing with optional search.
     * @param string|null $search Search term to filter by board title or owner email.
     * @return QueryBuilder
     */
    public function qbForAdmin(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.owner','o')->addSelect('o');

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(b.title)', ':search'),
                    $qb->expr()->like('LOWER(o.email)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        return $qb->orderBy('b.title', 'ASC');
    }

    /**
     * Check if a user is a member of a given board without hydrating the collection.
     * @param Board $board
     * @param Account $user
     * @return bool
     */
    public function isBoardMember(Board $board, Account $user): bool
    {
        return (bool) $this->createQueryBuilder('b')
            ->select('1')
            ->innerJoin('b.accounts', 'a')
            ->andWhere('b = :b')
            ->andWhere('a = :u')
            ->setParameter('b', $board)
            ->setParameter('u', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
