<?php

namespace App\Repository;

use App\Entity\Card;
use App\Entity\Lane;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    /**
     * Persist a card. Single entry point for card writes.
     */
    public function save(Card $card, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($card);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Remove a card.
     */
    public function remove(Card $card, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($card);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Finds the maximum position value of cards in the given lane.
     * Returns 0 if there are no cards in the lane.
     * @param Lane $lane
     * @return int Maximum position, or 0 if none.
     */
    public function findMaxPositionInLane(Lane $lane): int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.position) as maxPosition')
            ->andWhere('c.lane = :lane')
            ->setParameter('lane', $lane)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? 0;
    }

    /**
     * Retrieves the IDs of the cards in a lane, ordered by ascending position.
     * @param Lane $lane
     * @return int[] Array of card IDs in ascending order by position.
     */
    public function findIdsByLaneOrdered(Lane $lane): array
    {
        $ids = $this->createQueryBuilder('c')
            ->select('c.id')
            ->andWhere('c.lane = :lane')
            ->setParameter('lane', $lane)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $ids);
    }

    /**
     * After removing a card at $oldPos in $fromLane,
     * close the gap by decrementing positions > oldPos.
     * @param Lane $fromLane
     * @param int $oldPos
     * @return int
     */
    public function compactAfterRemoval(Lane $fromLane, int $oldPos): int
    {
        return $this->getEntityManager()->createQuery('
            UPDATE App\Entity\Card c
            SET c.position = c.position - 1
            WHERE c.lane = :lane AND c.position > :oldPos
        ')
        ->setParameter('lane', $fromLane)
        ->setParameter('oldPos', $oldPos)
        ->execute();
    }

    /**
    * Before inserting at $newIndex in $toLane,
    * "make room" by incrementing positions >= newIndex.
    * @param Lane $toLane
    * @param int $newIndex
    * @return int
    */
    public function makeRoomAt(Lane $toLane, int $newIndex): int
    {
        return $this->getEntityManager()->createQuery('
            UPDATE App\Entity\Card c
            SET c.position = c.position + 1
            WHERE c.lane = :lane AND c.position >= :newIndex
        ')
        ->setParameter('lane', $toLane)
        ->setParameter('newIndex', $newIndex)
        ->execute();
    }

    /**
     * Intra-lane movement: shift cards between oldIndex and newIndex.
     * - If new > old: move cards down (positions -1 on interval (old..new])
     * - If new < old: move cards up (positions +1 on interval [new..old))
     */
    public function shiftWithinLane(Lane $lane, int $oldIndex, int $newIndex): int
    {
        if ($newIndex === $oldIndex) {
            return 0;
        }

        if ($newIndex > $oldIndex) {
            return $this->getEntityManager()->createQuery('
                UPDATE App\Entity\Card c
                SET c.position = c.position - 1
                WHERE c.lane = :lane AND c.position > :old AND c.position <= :new
            ')
            ->setParameter('lane', $lane)
            ->setParameter('old', $oldIndex)
            ->setParameter('new', $newIndex)
            ->execute();
        }

        // $newIndex < $oldIndex
        return $this->getEntityManager()->createQuery('
            UPDATE App\Entity\Card c
            SET c.position = c.position + 1
            WHERE c.lane = :lane AND c.position >= :new AND c.position < :old
        ')
        ->setParameter('lane', $lane)
        ->setParameter('old', $oldIndex)
        ->setParameter('new', $newIndex)
        ->execute();
    }
}
