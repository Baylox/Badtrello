<?php

namespace App\Dto\Card;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO for the drag & drop "move card" endpoint.
 *
 * Mapped from the JSON request payload via #[MapRequestPayload].
 */
final class CardMoveInput
{
    #[Assert\Positive]
    public int $cardId = 0;

    #[Assert\Positive]
    public int $toLaneId = 0;

    #[Assert\PositiveOrZero]
    public int $newIndex = 0;
}
