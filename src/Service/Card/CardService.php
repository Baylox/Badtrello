<?php

namespace App\Service\Card;

use App\Dto\Card\CardInput;
use App\Entity\Card;
use App\Entity\Lane;
use App\Repository\CardRepository;
use App\Service\Board\CardMover;

/**
 * Application service owning the Card write use-cases.
 *
 * The positional move logic lives in CardMover (transaction + locks); this
 * service is the single orchestration point controllers talk to.
 */
final class CardService
{
    public function __construct(
        private readonly CardRepository $cards,
        private readonly CardMover $mover,
    ) {
    }

    /**
     * Create a card at the bottom of the given lane.
     */
    public function createForLane(CardInput $input, Lane $lane): Card
    {
        $card = new Card();
        $card->setLane($lane);
        // Position at the bottom of the lane (robust if the lane is empty).
        $card->setPosition($this->cards->findMaxPositionInLane($lane) + 1);
        $this->apply($card, $input);

        $this->cards->save($card);

        return $card;
    }

    public function update(Card $card, CardInput $input): void
    {
        $this->apply($card, $input);

        $this->cards->save($card);
    }

    public function delete(Card $card): void
    {
        $this->cards->remove($card);
    }

    /**
     * Move a card to a target lane at a given index.
     * CardMover wraps the operation in a transaction.
     */
    public function move(Card $card, Lane $toLane, int $newIndex): void
    {
        $this->mover->move($card, $toLane, $newIndex);
    }

    private function apply(Card $card, CardInput $input): void
    {
        $card->setTitle($input->title);
        $card->setDescription($input->description);
        $card->setStatus($input->status);
    }
}
