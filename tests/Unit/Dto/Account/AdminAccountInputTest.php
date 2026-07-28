<?php

namespace App\Tests\Unit\Dto\Account;

use App\Dto\Account\AdminAccountInput;
use App\Entity\Account;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[CoversClass(AdminAccountInput::class)]
final class AdminAccountInputTest extends TestCase
{
    private function validator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testFromEntityCopiesFields(): void
    {
        $account = (new Account())
            ->setEmail('admin@example.com')
            ->setName('Ada')
            ->setLastname('Lovelace')
            ->setRole('ROLE_ADMIN');

        $input = AdminAccountInput::fromEntity($account);

        $this->assertSame('admin@example.com', $input->email);
        $this->assertSame('Ada', $input->name);
        $this->assertSame('Lovelace', $input->lastname);
        $this->assertSame('ROLE_ADMIN', $input->role);
    }

    public function testValidInputPasses(): void
    {
        $input = new AdminAccountInput();
        $input->email = 'admin@example.com';
        $input->name = 'Ada';
        $input->lastname = 'Lovelace';
        $input->role = 'ROLE_USER';

        $this->assertCount(0, $this->validator()->validate($input));
    }

    public function testInvalidEmailIsInvalid(): void
    {
        $input = new AdminAccountInput();
        $input->email = 'nope';
        $input->name = 'Ada';
        $input->lastname = 'Lovelace';

        $this->assertGreaterThan(0, $this->validator()->validate($input)->count());
    }
}
