<?php

namespace App\Tests\Unit\Dto\Account;

use App\Dto\Account\ProfileInput;
use App\Entity\Account;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(ProfileInput::class)]
final class ProfileInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testFromEntityCopiesNamesButNotPassword(): void
    {
        $account = (new Account())->setName('John')->setLastname('Doe');

        $input = ProfileInput::fromEntity($account);

        $this->assertSame('John', $input->name);
        $this->assertSame('Doe', $input->lastname);
        $this->assertNull($input->plainPassword);
    }

    public function testValidProfileWithoutPasswordPasses(): void
    {
        // plainPassword stays null: optional password constraints are skipped.
        $input = new ProfileInput();
        $input->name = 'John';
        $input->lastname = 'Doe';

        $this->assertCount(0, $this->validator()->validate($input));
    }

    public function testBlankNameIsInvalid(): void
    {
        $input = new ProfileInput();
        $input->name = '';
        $input->lastname = 'Doe';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }

    public function testTooShortLastnameIsInvalid(): void
    {
        $input = new ProfileInput();
        $input->name = 'John';
        $input->lastname = 'D';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }
}
