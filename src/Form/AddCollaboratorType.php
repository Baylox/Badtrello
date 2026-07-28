<?php

namespace App\Form;

use App\Dto\Board\InvitationInput;
use App\Entity\Board;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddCollaboratorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'required' => true,
                'empty_data' => '',
                'label' => false,
                'attr' => [
                    'placeholder' => 'Enter user email address...',
                    'class' => 'input input-bordered flex-1'
                ],
                // Validation lives on InvitationInput::$email.
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to an entity.
        $resolver->setDefaults([
            'data_class' => InvitationInput::class,
        ]);
        $resolver->setRequired('board');
        $resolver->setAllowedTypes('board', Board::class);
    }
}
