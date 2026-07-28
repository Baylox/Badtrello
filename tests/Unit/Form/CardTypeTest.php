<?php

namespace App\Tests\Unit\Form;

use App\Dto\Card\CardInput;
use App\Enum\CardStatus;
use App\Form\CardType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[CoversClass(CardType::class)]
final class CardTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }

    public function testSubmitMapsAllFieldsToDto(): void
    {
        $input = new CardInput();
        $form = $this->factory->create(CardType::class, $input);

        $form->submit([
            'title' => 'Write tests',
            'description' => 'Cover the new services',
            'status' => 'todo',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('Write tests', $input->title);
        $this->assertSame('Cover the new services', $input->description);
        $this->assertSame(CardStatus::TODO, $input->status);
    }

    public function testStatusIsOptional(): void
    {
        $input = new CardInput();
        $form = $this->factory->create(CardType::class, $input);

        $form->submit(['title' => 'No status', 'description' => null, 'status' => '']);

        $this->assertTrue($form->isValid());
        $this->assertNull($input->status);
    }

    public function testSubmitBlankTitleIsInvalid(): void
    {
        $form = $this->factory->create(CardType::class, new CardInput());

        $form->submit(['title' => '']);

        $this->assertFalse($form->isValid());
    }
}
