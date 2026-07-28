<?php

namespace App\Repository;

use App\Entity\Lane;
use App\Entity\Board;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Lane>
 */
class LaneRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lane::class);
    }

    /**
     * Persist a lane. Single entry point for lane writes.
     */
    public function save(Lane $lane, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($lane);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Remove a lane.
     */
    public function remove(Lane $lane, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($lane);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Returns the next available position for a lane within the specified board.
     *
     * @param Board $board The board entity for which to determine the next lane position.
     * @return int The next position value for a new lane in the specified board.
     */

    public function getNextPositionForBoard(Board $board): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COALESCE(MAX(l.position), 0)')
            ->andWhere('l.board = :board')
            ->setParameter('board', $board)
            ->getQuery()
            ->getSingleScalarResult() + 1;
    }
}
