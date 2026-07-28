<?php

namespace App\Form;

use App\Dto\Account\NewPasswordInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                // Validation lives on NewPasswordInput::$plainPassword.
                'first_options' => [
                    'label' => 'New password',
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                ],
                'invalid_message' => 'The password fields must match.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not directly to the entity.
        $resolver->setDefaults([
            'data_class' => NewPasswordInput::class,
        ]);
    }
}
