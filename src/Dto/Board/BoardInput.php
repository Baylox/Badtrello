<?php

namespace App\Dto\Board;

use App\Entity\Board;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input DTO carrying the writable data of a Board.
 *
 * Forms map to this DTO (never directly to the Doctrine entity); the
 * BoardService is responsible for turning it into a persisted Board.
 */
final class BoardInput
{
    #[Assert\NotBlank(message: 'The title field cannot be empty.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Title must be at most {{ limit }} characters.'
    )]
    public string $title = '';

    /**
     * Build an input pre-filled from an existing Board (edit screens).
     */
    public static function fromEntity(Board $board): self
    {
        $input = new self();
        $input->title = $board->getTitle() ?? '';

        return $input;
    }
}
