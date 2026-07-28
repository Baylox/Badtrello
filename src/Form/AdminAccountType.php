<?php

namespace App\Form;

use App\Dto\Account\AdminAccountInput;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;


class AdminAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'empty_data' => '',
                'attr' => [
                    'autocomplete' => 'email',
                    'inputmode' => 'email', // Mobile-friendly keyboard
                ]
            ])
            ->add('name', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'label'    => 'First name',
                'attr'     => ['autocomplete' => 'given-name'],
            ])
            ->add('lastname', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'label'    => 'Last name',
                'attr'     => ['autocomplete' => 'family-name'],
            ])
        ->add('role', ChoiceType::class, [
            'choices' => [
                'User' => 'ROLE_USER',
            ],
            'label' => 'Role',
            'placeholder' => 'Select a role...',
            'attr' => [
                'class' => 'select select-bordered w-full text-base-content bg-base-100'
            ],
            'label_attr' => [
                'class' => 'label-text text-base-content font-medium'
            ]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        $resolver->setDefaults([
            'data_class' => AdminAccountInput::class,
        ]);
    }
}
