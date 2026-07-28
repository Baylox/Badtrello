<?php

namespace App\Tests\Unit\Dto\Board;

use App\Dto\Board\InvitationInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(InvitationInput::class)]
final class InvitationInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testValidEmailPasses(): void
    {
        $input = new InvitationInput();
        $input->email = 'guest@example.com';

        $this->assertCount(0, $this->validator()->validate($input));
    }

    public function testBlankEmailIsInvalid(): void
    {
        $input = new InvitationInput();
        $input->email = '';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testMalformedEmailIsInvalid(): void
    {
        $input = new InvitationInput();
        $input->email = 'nope';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }
}
