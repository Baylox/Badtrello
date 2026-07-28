<?php

namespace App\Form;

use App\Dto\Account\RegistrationInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'First Name',
                'empty_data' => '',
            ])
            ->add('lastname', null, [
                'label' => 'Last Name',
                'empty_data' => '',
            ])
            ->add('email', null, [
                'empty_data' => '',
            ])
            ->add('agreeTerms', CheckboxType::class)
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                // Validation lives on RegistrationInput::$plainPassword.
                'first_options'  => [
                    'label' => 'Password',
                    'attr' => ['autocomplete' => 'new-password'],
                    'help' => 'At least 8 characters with uppercase, lowercase, number and special character',
                ],
                'second_options' => [
                    'label' => 'Repeat Password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'The password fields must match.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        $resolver->setDefaults([
            'data_class' => RegistrationInput::class,
        ]);
    }
}
