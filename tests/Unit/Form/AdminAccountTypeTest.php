<?php

namespace App\Tests\Unit\Form;

use App\Dto\Account\AdminAccountInput;
use App\Form\AdminAccountType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[CoversClass(AdminAccountType::class)]
final class AdminAccountTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }

    public function testSubmitMapsFieldsToDto(): void
    {
        $input = new AdminAccountInput();
        $form = $this->factory->create(AdminAccountType::class, $input);

        $form->submit([
            'email' => 'admin@example.com',
            'name' => 'Ada',
            'lastname' => 'Lovelace',
            'role' => 'ROLE_USER',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('admin@example.com', $input->email);
        $this->assertSame('Ada', $input->name);
        $this->assertSame('Lovelace', $input->lastname);
        $this->assertSame('ROLE_USER', $input->role);
    }

    public function testSubmitInvalidEmailIsInvalid(): void
    {
        $form = $this->factory->create(AdminAccountType::class, new AdminAccountInput());

        $form->submit([
            'email' => 'not-an-email',
            'name' => 'Ada',
            'lastname' => 'Lovelace',
            'role' => 'ROLE_USER',
        ]);

        $this->assertFalse($form->isValid());
    }
}
