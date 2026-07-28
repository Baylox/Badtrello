<?php
namespace App\Service\Board;

use App\Entity\Card;
use App\Entity\Lane;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;

/**
 * Deliberate exception to the "no EntityManager outside repositories" rule:
 * moving a card spans two lanes and needs a single transaction with
 * pessimistic locks on both, which CardRepository::save()/remove() cannot
 * express. Not final so CardServiceTest can mock it.
 */
class CardMover
{
    public function __construct(
        private EntityManagerInterface $em,
        private CardRepository $cards,
    ) {}

    /** Moves a card to $toLane at index $newIndex (0-based) */
    public function move(Card $card, Lane $toLane, int $newIndex): void
    {
        $this->em->wrapInTransaction(function () use ($card, $toLane, $newIndex) {
            $fromLane = $card->getLane();

            // Concurrency (optional but safe)
            $this->em->lock($toLane, LockMode::PESSIMISTIC_WRITE);
            if ($fromLane && $fromLane !== $toLane) {
                $this->em->lock($fromLane, LockMode::PESSIMISTIC_WRITE);
            }

            if ($fromLane !== $toLane) {
                // Closes the gap in the old lane
                $this->cards->compactAfterRemoval($fromLane, $card->getPosition());
                // Makes room in the new lane
                $this->cards->makeRoomAt($toLane, $newIndex);
                // Applies the move
                $card->setLane($toLane);
                $card->setPosition($newIndex);
            } else {
                // Intra-lane move
                $this->cards->shiftWithinLane($toLane, $card->getPosition(), $newIndex);
                $card->setPosition($newIndex);
            }
        });
    }
}
