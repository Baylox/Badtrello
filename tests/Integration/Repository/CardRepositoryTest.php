<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Card;
use App\Enum\CardStatus;
use App\Factory\BoardFactory;
use App\Factory\CardFactory;
use App\Factory\LaneFactory;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises CardRepository against a real database.
 *
 * compactAfterRemoval, makeRoomAt and shiftWithinLane are DQL bulk UPDATEs that
 * bypass the Doctrine identity map: the tests call EntityManager::clear() after
 * executing them and re-read the positions from the database before asserting.
 *
 * Several scenarios use non-contiguous positions on purpose: the card table has
 * a UNIQUE(lane_id, position) constraint and MariaDB validates unique keys row
 * by row while a multi-row UPDATE runs, so shifting a dense sequence could raise
 * a transient duplicate-key error depending on the scan order. Gaps keep the
 * interval semantics under test deterministic.
 */
#[CoversClass(CardRepository::class)]
final class CardRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testFindMaxPositionInLaneReturnsHighestPosition(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $otherLane = LaneFactory::createOne(['board' => $board, 'position' => 2]);

        CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        CardFactory::createOne(['lane' => $lane, 'position' => 2]);
        CardFactory::createOne(['lane' => $lane, 'position' => 5]);
        // A higher position in another lane must not leak into the result.
        CardFactory::createOne(['lane' => $otherLane, 'position' => 9]);

        $this->assertSame(5, $this->repository()->findMaxPositionInLane($lane->_real()));
    }

    public function testFindMaxPositionInLaneReturnsZeroForAnEmptyLane(): void
    {
        $board = BoardFactory::createOne();
        $emptyLane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $otherLane = LaneFactory::createOne(['board' => $board, 'position' => 2]);

        CardFactory::createOne(['lane' => $otherLane, 'position' => 3]);

        $this->assertSame(0, $this->repository()->findMaxPositionInLane($emptyLane->_real()));
    }

    public function testFindIdsByLaneOrderedReturnsIdsSortedByAscendingPosition(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $otherLane = LaneFactory::createOne(['board' => $board, 'position' => 2]);

        // Created out of order so insertion order cannot mask the sorting.
        $last = CardFactory::createOne(['lane' => $lane, 'position' => 2]);
        $first = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $middle = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        // Cards from another lane must not be listed.
        CardFactory::createOne(['lane' => $otherLane, 'position' => 0]);

        $this->assertSame(
            [$first->getId(), $middle->getId(), $last->getId()],
            $this->repository()->findIdsByLaneOrdered($lane->_real())
        );
    }

    public function testCompactAfterRemovalDecrementsOnlyPositionsAboveTheRemovedOne(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $otherLane = LaneFactory::createOne(['board' => $board, 'position' => 2]);

        $below = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $removed = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $above = CardFactory::createOne(['lane' => $lane, 'position' => 2]);
        $other = CardFactory::createOne(['lane' => $otherLane, 'position' => 2]);

        $belowId = $below->getId();
        $aboveId = $above->getId();
        $otherId = $other->getId();

        // The contract is "after removing a card at $oldPos": delete it first.
        $this->repository()->remove($removed->_real());

        $affected = $this->repository()->compactAfterRemoval($lane->_real(), 1);

        $this->assertSame(1, $affected);

        $this->em()->clear();

        $this->assertCardPosition($belowId, 0);
        $this->assertCardPosition($aboveId, 1);
        // Another lane sharing the same position values is left untouched.
        $this->assertCardPosition($otherId, 2);
    }

    public function testMakeRoomAtIncrementsOnlyPositionsAtOrAboveTheIndex(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $otherLane = LaneFactory::createOne(['board' => $board, 'position' => 2]);

        $below = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $atIndex = CardFactory::createOne(['lane' => $lane, 'position' => 3]);
        $above = CardFactory::createOne(['lane' => $lane, 'position' => 7]);
        $other = CardFactory::createOne(['lane' => $otherLane, 'position' => 3]);

        $belowId = $below->getId();
        $atIndexId = $atIndex->getId();
        $aboveId = $above->getId();
        $otherId = $other->getId();

        $affected = $this->repository()->makeRoomAt($lane->_real(), 3);

        $this->assertSame(2, $affected);

        $this->em()->clear();

        $this->assertCardPosition($belowId, 0);
        $this->assertCardPosition($atIndexId, 4);
        $this->assertCardPosition($aboveId, 8);
        $this->assertCardPosition($otherId, 3);
    }

    public function testShiftWithinLaneWithGreaterNewIndexDecrementsTheHalfOpenInterval(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $atOldIndex = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $inInterval = CardFactory::createOne(['lane' => $lane, 'position' => 2]);
        $atNewIndex = CardFactory::createOne(['lane' => $lane, 'position' => 6]);
        $outside = CardFactory::createOne(['lane' => $lane, 'position' => 9]);

        $atOldIndexId = $atOldIndex->getId();
        $inIntervalId = $inInterval->getId();
        $atNewIndexId = $atNewIndex->getId();
        $outsideId = $outside->getId();

        // new > old: positions in (old..new] move down by one.
        $affected = $this->repository()->shiftWithinLane($lane->_real(), 0, 6);

        $this->assertSame(2, $affected);

        $this->em()->clear();

        // Lower bound is exclusive, upper bound inclusive.
        $this->assertCardPosition($atOldIndexId, 0);
        $this->assertCardPosition($inIntervalId, 1);
        $this->assertCardPosition($atNewIndexId, 5);
        $this->assertCardPosition($outsideId, 9);
    }

    public function testShiftWithinLaneWithLowerNewIndexIncrementsTheHalfOpenInterval(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $below = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $atNewIndex = CardFactory::createOne(['lane' => $lane, 'position' => 3]);
        $inInterval = CardFactory::createOne(['lane' => $lane, 'position' => 7]);
        $atOldIndex = CardFactory::createOne(['lane' => $lane, 'position' => 9]);

        $belowId = $below->getId();
        $atNewIndexId = $atNewIndex->getId();
        $inIntervalId = $inInterval->getId();
        $atOldIndexId = $atOldIndex->getId();

        // new < old: positions in [new..old) move up by one.
        $affected = $this->repository()->shiftWithinLane($lane->_real(), 9, 3);

        $this->assertSame(2, $affected);

        $this->em()->clear();

        // Lower bound is inclusive, upper bound exclusive.
        $this->assertCardPosition($belowId, 0);
        $this->assertCardPosition($atNewIndexId, 4);
        $this->assertCardPosition($inIntervalId, 8);
        $this->assertCardPosition($atOldIndexId, 9);
    }

    public function testShiftWithinLaneWithSameIndexIsANoOp(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $first = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $second = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $third = CardFactory::createOne(['lane' => $lane, 'position' => 2]);

        $firstId = $first->getId();
        $secondId = $second->getId();
        $thirdId = $third->getId();

        $this->assertSame(0, $this->repository()->shiftWithinLane($lane->_real(), 1, 1));

        $this->em()->clear();

        $this->assertCardPosition($firstId, 0);
        $this->assertCardPosition($secondId, 1);
        $this->assertCardPosition($thirdId, 2);
    }

    public function testSavePersistsACard(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $card = (new Card())
            ->setTitle('Persisted via repository')
            ->setDescription('Written by CardRepository::save().')
            ->setStatus(CardStatus::TODO)
            ->setLane($lane->_real())
            ->setPosition(0);

        $this->repository()->save($card);

        $id = $card->getId();
        $this->assertNotNull($id);

        $this->em()->clear();

        $reloaded = $this->repository()->find($id);
        $this->assertNotNull($reloaded);
        $this->assertSame('Persisted via repository', $reloaded->getTitle());
        $this->assertSame(0, $reloaded->getPosition());
    }

    public function testRemoveDeletesACard(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $card = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $id = $card->getId();

        $this->repository()->remove($card->_real());

        $this->em()->clear();

        $this->assertNull($this->repository()->find($id));
    }

    private function repository(): CardRepository
    {
        return static::getContainer()->get(CardRepository::class);
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Asserts a card position as stored in the database. EntityManager::clear()
     * must have been called after a DQL bulk UPDATE so the card is re-hydrated
     * from the database instead of the stale identity map.
     */
    private function assertCardPosition(int $cardId, int $expectedPosition): void
    {
        $card = $this->em()->find(Card::class, $cardId);

        $this->assertNotNull($card, sprintf('Card %d should exist.', $cardId));
        $this->assertSame($expectedPosition, $card->getPosition());
    }
}
