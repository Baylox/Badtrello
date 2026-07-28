<?php

namespace App\Tests\Unit\Form;

use App\Dto\Lane\LaneInput;
use App\Form\LaneType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[CoversClass(LaneType::class)]
final class LaneTypeTest extends TypeTestCase
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
        $input = new LaneInput();
        $form = $this->factory->create(LaneType::class, $input);

        $form->submit(['title' => 'Backlog']);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertSame('Backlog', $input->title);
    }

    public function testSubmitBlankTitleIsInvalid(): void
    {
        $form = $this->factory->create(LaneType::class, new LaneInput());

        $form->submit(['title' => '']);

        $this->assertFalse($form->isValid());
    }
}
