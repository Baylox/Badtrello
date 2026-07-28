<?php

namespace App\Tests\Unit\Form;

use App\Dto\Board\InvitationInput;
use App\Entity\Board;
use App\Form\AddCollaboratorType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[CoversClass(AddCollaboratorType::class)]
final class AddCollaboratorTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }

    public function testSubmitMapsEmailToDto(): void
    {
        $input = new InvitationInput();
        $form = $this->factory->create(AddCollaboratorType::class, $input, ['board' => new Board()]);

        $form->submit(['email' => 'collaborator@example.com']);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('collaborator@example.com', $input->email);
    }

    public function testSubmitInvalidEmailIsInvalid(): void
    {
        $form = $this->factory->create(AddCollaboratorType::class, new InvitationInput(), ['board' => new Board()]);

        $form->submit(['email' => 'not-an-email']);

        $this->assertFalse($form->isValid());
    }
}
