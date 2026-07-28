<?php

namespace App\Tests\Unit\Dto\Board;

use App\Dto\Board\BoardInput;
use App\Entity\Board;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(BoardInput::class)]
final class BoardInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testFromEntityCopiesTitle(): void
    {
        $board = (new Board())->setTitle('Roadmap');

        $input = BoardInput::fromEntity($board);

        $this->assertSame('Roadmap', $input->title);
    }

    public function testBlankTitleIsInvalid(): void
    {
        $input = new BoardInput();
        $input->title = '';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testTooLongTitleIsInvalid(): void
    {
        $input = new BoardInput();
        $input->title = str_repeat('a', 51);

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testValidTitlePasses(): void
    {
        $input = new BoardInput();
        $input->title = 'A perfectly valid title';

        $this->assertCount(0, $this->validator()->validate($input));
    }
}
