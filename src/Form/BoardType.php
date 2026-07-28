<?php

namespace App\Form;

use App\Dto\Board\BoardInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class BoardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                // Map an empty submission to '' (not null) so the non-nullable
                // DTO property is satisfied and NotBlank reports it cleanly.
                'empty_data' => '',
                'label'    => 'Board title',
                'attr'     => [
                    'placeholder' => 'Enter a title',
                    'class' => 'input input-bordered w-full text-base-content bg-base-100'
                ],
                'label_attr' => [
                    'class' => 'label-text text-base-content font-medium'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        // Validation lives on BoardInput's constraints.
        $resolver->setDefaults([
            'data_class' => BoardInput::class,
        ]);
    }
}
