<?php

namespace App\Service\Board;

use App\Dto\Board\BoardInput;
use App\Entity\Account;
use App\Entity\Board;
use App\Repository\BoardRepository;

/**
 * Application service owning the Board write use-cases.
 *
 * Controllers delegate every mutation here; persistence itself is delegated
 * to BoardRepository. This keeps the chain Controller -> DTO -> Service ->
 * Repository and removes EntityManager usage from controllers.
 */
final class BoardService
{
    public function __construct(
        private readonly BoardRepository $boards,
    ) {
    }

    /**
     * Create a new board owned by the given account.
     */
    public function create(BoardInput $input, Account $owner): Board
    {
        $board = new Board();
        $board->setTitle($input->title);
        $board->setOwner($owner);

        $this->boards->save($board);

        return $board;
    }

    /**
     * Apply an input onto an existing board and persist the change.
     */
    public function update(Board $board, BoardInput $input): void
    {
        $board->setTitle($input->title);

        $this->boards->save($board);
    }

    /**
     * Permanently delete a board.
     */
    public function delete(Board $board): void
    {
        $this->boards->remove($board);
    }
}
