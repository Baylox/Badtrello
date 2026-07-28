<?php

namespace App\Form;

use App\Dto\Account\ProfileInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'attr' => ['autocomplete' => 'given-name'],
            ])
            ->add('lastname', TextType::class, [
                'empty_data' => '',
                'attr' => ['autocomplete' => 'family-name'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                // Validation lives on ProfileInput::$plainPassword.
                'first_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'label' => 'New Password',
                    'help' => 'At least 8 characters with uppercase, lowercase, number and special character',
                ],
                'second_options' => [
                    'attr' => ['autocomplete' => 'new-password'],
                    'label' => 'Confirm New Password',
                ],
                'invalid_message' => 'The password fields must match.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        $resolver->setDefaults([
            'data_class' => ProfileInput::class,
        ]);
    }
}
