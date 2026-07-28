<?php

namespace App\Tests\Unit\Dto\Account;

use App\Dto\Account\RegistrationInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * RegistrationInput carries a class-level UniqueEntity constraint (requires
 * Doctrine), so we validate individual properties to keep the test hermetic.
 */
#[CoversClass(RegistrationInput::class)]
final class RegistrationInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testValidEmailPropertyPasses(): void
    {
        $input = new RegistrationInput();
        $input->email = 'new@example.com';

        $this->assertCount(0, $this->validator()->validateProperty($input, 'email'));
    }

    public function testBlankEmailPropertyIsInvalid(): void
    {
        $input = new RegistrationInput();
        $input->email = '';

        $this->assertGreaterThan(0, $this->validator()->validateProperty($input, 'email')->count());
    }

    public function testTooShortNamePropertyIsInvalid(): void
    {
        $input = new RegistrationInput();
        $input->name = 'A';

        $this->assertGreaterThan(0, $this->validator()->validateProperty($input, 'name')->count());
    }

    public function testAgreeTermsMustBeTrue(): void
    {
        $input = new RegistrationInput();
        $input->agreeTerms = false;

        $this->assertGreaterThan(0, $this->validator()->validateProperty($input, 'agreeTerms')->count());

        $input->agreeTerms = true;
        $this->assertCount(0, $this->validator()->validateProperty($input, 'agreeTerms'));
    }
}
