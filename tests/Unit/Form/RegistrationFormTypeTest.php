<?php

namespace App\Tests\Unit\Form;

use App\Dto\Account\RegistrationInput;
use App\Form\RegistrationFormType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(RegistrationFormType::class)]
final class RegistrationFormTypeTest extends TypeTestCase
{
    public function testFormHasExpectedFields(): void
    {
        $form = $this->factory->create(RegistrationFormType::class, new RegistrationInput());

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('lastname'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('agreeTerms'));
        $this->assertTrue($form->has('plainPassword'));
    }

    public function testSubmitMapsFieldsToDto(): void
    {
        $input = new RegistrationInput();
        $form = $this->factory->create(RegistrationFormType::class, $input);

        // Validation disabled (UniqueEntity/NotCompromisedPassword need infra) — mapping only.
        $form->submit([
            'name' => 'Grace',
            'lastname' => 'Hopper',
            'email' => 'grace@example.com',
            'agreeTerms' => true,
            'plainPassword' => ['first' => 'Cobol#1234', 'second' => 'Cobol#1234'],
        ], false);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('Grace', $input->name);
        $this->assertSame('Hopper', $input->lastname);
        $this->assertSame('grace@example.com', $input->email);
        $this->assertTrue($input->agreeTerms);
        $this->assertSame('Cobol#1234', $input->plainPassword);
    }
}
