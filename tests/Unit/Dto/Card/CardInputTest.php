<?php

namespace App\Tests\Unit\Dto\Card;

use App\Dto\Card\CardInput;
use App\Entity\Card;
use App\Enum\CardStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(CardInput::class)]
final class CardInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testFromEntityCopiesAllFields(): void
    {
        $card = (new Card())
            ->setTitle('Deploy')
            ->setDescription('Ship it')
            ->setStatus(CardStatus::DONE);

        $input = CardInput::fromEntity($card);

        $this->assertSame('Deploy', $input->title);
        $this->assertSame('Ship it', $input->description);
        $this->assertSame(CardStatus::DONE, $input->status);
    }

    public function testBlankTitleIsInvalid(): void
    {
        $input = new CardInput();
        $input->title = '';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testTooLongTitleIsInvalid(): void
    {
        $input = new CardInput();
        $input->title = str_repeat('x', 51);

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testValidCardPasses(): void
    {
        $input = new CardInput();
        $input->title = 'A valid card';
        $input->description = null;
        $input->status = null;

        $this->assertCount(0, $this->validator()->validate($input));
    }
}
