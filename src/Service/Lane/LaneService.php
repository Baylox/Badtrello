<?php

namespace App\Service\Lane;

use App\Dto\Lane\LaneInput;
use App\Entity\Board;
use App\Entity\Lane;
use App\Repository\LaneRepository;

/**
 * Application service owning the Lane write use-cases.
 */
final class LaneService
{
    public function __construct(
        private readonly LaneRepository $lanes,
    ) {
    }

    /**
     * Create a lane at the bottom of the given board.
     */
    public function createForBoard(LaneInput $input, Board $board): Lane
    {
        $lane = new Lane();
        $lane->setBoard($board);
        $lane->setTitle($input->title);
        $lane->setPosition($this->lanes->getNextPositionForBoard($board));

        $this->lanes->save($lane);

        return $lane;
    }

    public function update(Lane $lane, LaneInput $input): void
    {
        $lane->setTitle($input->title);

        $this->lanes->save($lane);
    }

    public function delete(Lane $lane): void
    {
        $this->lanes->remove($lane);
    }
}
