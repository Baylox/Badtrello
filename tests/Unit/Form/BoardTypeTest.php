<?php

namespace App\Tests\Unit\Form;

use App\Dto\Board\BoardInput;
use App\Form\BoardType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[CoversClass(BoardType::class)]
final class BoardTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }

    public function testSubmitMapsTitleToDto(): void
    {
        $input = new BoardInput();
        $form = $this->factory->create(BoardType::class, $input);

        $form->submit(['title' => 'My board']);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('My board', $input->title);
    }

    public function testSubmitBlankTitleIsInvalid(): void
    {
        $form = $this->factory->create(BoardType::class, new BoardInput());

        $form->submit(['title' => '']);

        $this->assertFalse($form->isValid());
    }
}
