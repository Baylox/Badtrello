<?php

namespace App\Form;

use App\Dto\Account\PasswordResetRequestInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResetPasswordRequestFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'attr' => ['autocomplete' => 'email'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO; validation lives on PasswordResetRequestInput.
        $resolver->setDefaults([
            'data_class' => PasswordResetRequestInput::class,
        ]);
    }
}
