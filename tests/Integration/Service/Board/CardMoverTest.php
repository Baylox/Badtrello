<?php

namespace App\Tests\Integration\Service\Board;

use App\Entity\Card;
use App\Factory\BoardFactory;
use App\Factory\CardFactory;
use App\Factory\LaneFactory;
use App\Service\Board\CardMover;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Exercises CardMover against a real database (transaction + locks + DQL bulk updates).
 *
 * The repository helpers the mover delegates to (compactAfterRemoval, makeRoomAt,
 * shiftWithinLane) are DQL UPDATE statements that bypass the Doctrine identity map,
 * so every scenario clears the entity manager after the move and re-reads the
 * positions from the database before asserting.
 */
#[CoversClass(CardMover::class)]
final class CardMoverTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testMoveToAnotherLaneCompactsSourceAndMakesRoomInTarget(): void
    {
        $board = BoardFactory::createOne();
        $laneA = LaneFactory::createOne(['board' => $board, 'position' => 1]);
        $laneB = LaneFactory::createOne(['board' => $board, 'position' => 2]);

        $a0 = CardFactory::createOne(['lane' => $laneA, 'position' => 0]);
        $a1 = CardFactory::createOne(['lane' => $laneA, 'position' => 1]);
        $a2 = CardFactory::createOne(['lane' => $laneA, 'position' => 2]);
        $b0 = CardFactory::createOne(['lane' => $laneB, 'position' => 0]);
        $b1 = CardFactory::createOne(['lane' => $laneB, 'position' => 1]);

        $laneAId = $laneA->getId();
        $laneBId = $laneB->getId();
        $a0Id = $a0->getId();
        $a1Id = $a1->getId();
        $a2Id = $a2->getId();
        $b0Id = $b0->getId();
        $b1Id = $b1->getId();

        $this->mover()->move($a1->_real(), $laneB->_real(), 1);

        // Discard stale in-memory positions: the move ran DQL bulk updates.
        $this->em()->clear();

        // Source lane is compacted back to a dense (0, 1) sequence.
        $this->assertCardAt($a0Id, $laneAId, 0);
        $this->assertCardAt($a2Id, $laneAId, 1);

        // Moved card lands at index 1; the former B[1] is pushed down to 2.
        $this->assertCardAt($b0Id, $laneBId, 0);
        $this->assertCardAt($a1Id, $laneBId, 1);
        $this->assertCardAt($b1Id, $laneBId, 2);
    }

    public function testMoveDownWithinLaneShiftsIntermediateCardsUp(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $first = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $second = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $third = CardFactory::createOne(['lane' => $lane, 'position' => 2]);

        $laneId = $lane->getId();
        $firstId = $first->getId();
        $secondId = $second->getId();
        $thirdId = $third->getId();

        $this->mover()->move($first->_real(), $lane->_real(), 2);

        $this->em()->clear();

        // Former positions 1 and 2 slide up to 0 and 1; the moved card takes 2.
        $this->assertCardAt($secondId, $laneId, 0);
        $this->assertCardAt($thirdId, $laneId, 1);
        $this->assertCardAt($firstId, $laneId, 2);
    }

    public function testMoveUpWithinLaneShiftsIntermediateCardsDown(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $first = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $second = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $third = CardFactory::createOne(['lane' => $lane, 'position' => 2]);

        $laneId = $lane->getId();
        $firstId = $first->getId();
        $secondId = $second->getId();
        $thirdId = $third->getId();

        $this->mover()->move($third->_real(), $lane->_real(), 0);

        $this->em()->clear();

        // The moved card takes 0; former positions 0 and 1 slide down to 1 and 2.
        $this->assertCardAt($thirdId, $laneId, 0);
        $this->assertCardAt($firstId, $laneId, 1);
        $this->assertCardAt($secondId, $laneId, 2);
    }

    public function testMoveToSameIndexKeepsAllPositionsUnchanged(): void
    {
        $board = BoardFactory::createOne();
        $lane = LaneFactory::createOne(['board' => $board, 'position' => 1]);

        $first = CardFactory::createOne(['lane' => $lane, 'position' => 0]);
        $second = CardFactory::createOne(['lane' => $lane, 'position' => 1]);
        $third = CardFactory::createOne(['lane' => $lane, 'position' => 2]);

        $laneId = $lane->getId();
        $firstId = $first->getId();
        $secondId = $second->getId();
        $thirdId = $third->getId();

        $this->mover()->move($second->_real(), $lane->_real(), 1);

        $this->em()->clear();

        $this->assertCardAt($firstId, $laneId, 0);
        $this->assertCardAt($secondId, $laneId, 1);
        $this->assertCardAt($thirdId, $laneId, 2);
    }

    private function mover(): CardMover
    {
        return static::getContainer()->get(CardMover::class);
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Asserts a card's lane and position as stored in the database.
     * EntityManager::clear() must have been called after the move so the
     * card is re-hydrated from the database instead of the identity map.
     */
    private function assertCardAt(int $cardId, int $expectedLaneId, int $expectedPosition): void
    {
        $card = $this->em()->find(Card::class, $cardId);

        $this->assertNotNull($card, sprintf('Card %d should exist.', $cardId));
        $this->assertSame($expectedLaneId, $card->getLane()->getId());
        $this->assertSame($expectedPosition, $card->getPosition());
    }
}
