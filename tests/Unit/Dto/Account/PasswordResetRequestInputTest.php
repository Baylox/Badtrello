<?php

namespace App\Tests\Unit\Dto\Account;

use App\Dto\Account\PasswordResetRequestInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(PasswordResetRequestInput::class)]
final class PasswordResetRequestInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testValidEmailPasses(): void
    {
        $input = new PasswordResetRequestInput();
        $input->email = 'user@example.com';

        $this->assertCount(0, $this->validator()->validate($input));
    }

    public function testBlankEmailIsInvalid(): void
    {
        $input = new PasswordResetRequestInput();
        $input->email = '';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testMalformedEmailIsInvalid(): void
    {
        $input = new PasswordResetRequestInput();
        $input->email = 'bad';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }
}
