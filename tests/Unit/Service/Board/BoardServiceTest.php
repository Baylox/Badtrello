<?php

namespace App\Tests\Unit\Service\Board;

use App\Dto\Board\BoardInput;
use App\Entity\Account;
use App\Entity\Board;
use App\Repository\BoardRepository;
use App\Service\Board\BoardService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BoardService::class)]
final class BoardServiceTest extends TestCase
{
    public function testCreateBuildsBoardAndPersistsIt(): void
    {
        $repo = $this->createMock(BoardRepository::class);
        $repo->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Board::class));

        $service = new BoardService($repo);

        $input = new BoardInput();
        $input->title = 'My board';
        $owner = new Account();

        $board = $service->create($input, $owner);

        $this->assertSame('My board', $board->getTitle());
        $this->assertSame($owner, $board->getOwner());
    }

    public function testUpdateAppliesInputAndPersists(): void
    {
        $board = (new Board())->setTitle('Old title');

        $repo = $this->createMock(BoardRepository::class);
        $repo->expects($this->once())
            ->method('save')
            ->with($board);

        $service = new BoardService($repo);

        $input = new BoardInput();
        $input->title = 'New title';

        $service->update($board, $input);

        $this->assertSame('New title', $board->getTitle());
    }

    public function testDeleteRemovesBoard(): void
    {
        $board = new Board();

        $repo = $this->createMock(BoardRepository::class);
        $repo->expects($this->once())
            ->method('remove')
            ->with($board);

        $service = new BoardService($repo);

        $service->delete($board);
    }
}
