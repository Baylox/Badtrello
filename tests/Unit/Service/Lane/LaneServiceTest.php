<?php

namespace App\Tests\Unit\Service\Lane;

use App\Dto\Lane\LaneInput;
use App\Entity\Board;
use App\Entity\Lane;
use App\Repository\LaneRepository;
use App\Service\Lane\LaneService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LaneService::class)]
final class LaneServiceTest extends TestCase
{
    public function testCreateForBoardAssignsTitleBoardAndNextPosition(): void
    {
        $board = new Board();

        $repo = $this->createMock(LaneRepository::class);
        $repo->method('getNextPositionForBoard')->with($board)->willReturn(4);
        $repo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Lane::class));

        $service = new LaneService($repo);

        $input = new LaneInput();
        $input->title = 'Backlog';

        $lane = $service->createForBoard($input, $board);

        $this->assertSame('Backlog', $lane->getTitle());
        $this->assertSame($board, $lane->getBoard());
        $this->assertSame(4, $lane->getPosition());
    }

    public function testUpdateAppliesTitleAndPersists(): void
    {
        $lane = (new Lane())->setTitle('Old');

        $repo = $this->createMock(LaneRepository::class);
        $repo->expects($this->once())->method('save')->with($lane);

        (new LaneService($repo))->update($lane, (function () {
            $i = new LaneInput();
            $i->title = 'New';
            return $i;
        })());

        $this->assertSame('New', $lane->getTitle());
    }

    public function testDeleteRemovesLane(): void
    {
        $lane = new Lane();

        $repo = $this->createMock(LaneRepository::class);
        $repo->expects($this->once())->method('remove')->with($lane);

        (new LaneService($repo))->delete($lane);
    }
}
