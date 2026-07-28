<?php

namespace App\Dto\Card;

use App\Entity\Card;
use App\Enum\CardStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO carrying the writable data of a Card.
 *
 * Forms map to this DTO; CardService turns it into a persisted Card.
 * Position and lane assignment are managed by the service.
 */
final class CardInput
{
    #[Assert\NotBlank(message: 'The title field cannot be empty.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Title must be at most {{ limit }} characters.'
    )]
    public string $title = '';

    public ?string $description = null;

    public ?CardStatus $status = null;

    public static function fromEntity(Card $card): self
    {
        $input = new self();
        $input->title = $card->getTitle() ?? '';
        $input->description = $card->getDescription();
        $input->status = $card->getStatus();

        return $input;
    }
}
