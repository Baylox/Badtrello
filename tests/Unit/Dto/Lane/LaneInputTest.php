<?php

namespace App\Tests\Unit\Dto\Lane;

use App\Dto\Lane\LaneInput;
use App\Entity\Lane;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(LaneInput::class)]
final class LaneInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testFromEntityCopiesTitle(): void
    {
        $lane = (new Lane())->setTitle('In progress');

        $this->assertSame('In progress', LaneInput::fromEntity($lane)->title);
    }

    public function testBlankTitleIsInvalid(): void
    {
        $input = new LaneInput();
        $input->title = '';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testValidTitlePasses(): void
    {
        $input = new LaneInput();
        $input->title = 'Done';

        $this->assertCount(0, $this->validator()->validate($input));
    }
}
