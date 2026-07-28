<?php

namespace App\Tests\Unit\Dto\Card;

use App\Dto\Card\CardMoveInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(CardMoveInput::class)]
final class CardMoveInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testValidPayloadPasses(): void
    {
        $input = new CardMoveInput();
        $input->cardId = 12;
        $input->toLaneId = 3;
        $input->newIndex = 0;

        $this->assertCount(0, $this->validator()->validate($input));
    }

    public function testNonPositiveIdsAreInvalid(): void
    {
        $input = new CardMoveInput();
        $input->cardId = 0;
        $input->toLaneId = -1;
        $input->newIndex = 0;

        $this->assertGreaterThanOrEqual(2, $this->validator()->validate($input)->count());
    }

    public function testNegativeIndexIsInvalid(): void
    {
        $input = new CardMoveInput();
        $input->cardId = 1;
        $input->toLaneId = 1;
        $input->newIndex = -5;

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }
}
