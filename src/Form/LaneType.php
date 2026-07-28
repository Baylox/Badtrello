<?php

namespace App\Form;

use App\Dto\Lane\LaneInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LaneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // empty_data '' keeps the non-nullable DTO property satisfied on
            // empty submissions (NotBlank then reports the error).
            ->add('title', null, ['empty_data' => ''])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        $resolver->setDefaults([
            'data_class' => LaneInput::class,
        ]);
    }
}
