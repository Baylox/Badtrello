<?php

namespace App\Tests\Unit\Service\Card;

use App\Dto\Card\CardInput;
use App\Entity\Card;
use App\Entity\Lane;
use App\Enum\CardStatus;
use App\Repository\CardRepository;
use App\Service\Board\CardMover;
use App\Service\Card\CardService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CardService::class)]
final class CardServiceTest extends TestCase
{
    public function testCreateForLanePlacesCardAtBottom(): void
    {
        $lane = new Lane();

        $repo = $this->createMock(CardRepository::class);
        $repo->method('findMaxPositionInLane')->with($lane)->willReturn(2);
        $repo->expects($this->once())->method('save')->with($this->isInstanceOf(Card::class));

        $mover = $this->createMock(CardMover::class);
        $service = new CardService($repo, $mover);

        $input = new CardInput();
        $input->title = 'Task';
        $input->description = 'Details';
        $input->status = CardStatus::cases()[0];

        $card = $service->createForLane($input, $lane);

        $this->assertSame('Task', $card->getTitle());
        $this->assertSame('Details', $card->getDescription());
        $this->assertSame($lane, $card->getLane());
        $this->assertSame(3, $card->getPosition());
    }

    public function testUpdateAppliesInputAndPersists(): void
    {
        $card = (new Card())->setTitle('Old');

        $repo = $this->createMock(CardRepository::class);
        $repo->expects($this->once())->method('save')->with($card);

        $service = new CardService($repo, $this->createMock(CardMover::class));

        $input = new CardInput();
        $input->title = 'New';

        $service->update($card, $input);

        $this->assertSame('New', $card->getTitle());
    }

    public function testDeleteRemovesCard(): void
    {
        $card = new Card();

        $repo = $this->createMock(CardRepository::class);
        $repo->expects($this->once())->method('remove')->with($card);

        (new CardService($repo, $this->createMock(CardMover::class)))->delete($card);
    }

    public function testMoveDelegatesToCardMover(): void
    {
        $card = new Card();
        $lane = new Lane();

        $mover = $this->createMock(CardMover::class);
        $mover->expects($this->once())->method('move')->with($card, $lane, 5);

        $service = new CardService($this->createMock(CardRepository::class), $mover);
        $service->move($card, $lane, 5);
    }
}
