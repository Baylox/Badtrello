<?php

namespace App\Form;

use App\Dto\Card\CardInput;
use App\Enum\CardStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Length;

class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'empty_data' => '',
                'label'    => 'Card title',
                'attr'     => [
                    'placeholder' => 'Enter a title',
                    'class' => 'input input-bordered w-full text-base-content bg-base-100'
                ],
                'label_attr' => [
                    'class' => 'label-text text-base-content font-medium'
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'attr'     => [
                    'placeholder' => 'Enter a description',
                    'class' => 'textarea textarea-bordered w-full text-base-content bg-base-100'
                ],
                'label_attr' => [
                    'class' => 'label-text text-base-content font-medium'
                ],
            ])
            ->add('status', EnumType::class, [
                'class' => CardStatus::class,
                'choice_label' => fn(CardStatus $status) => $status->getLabel(),
                'label' => 'Status',
                'required' => false,
                'placeholder' => 'Select a status',
                'attr' => [
                    'class' => 'select select-bordered w-full text-base-content bg-base-100'
                ],
                'label_attr' => [
                    'class' => 'label-text text-base-content font-medium'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // The form maps to the DTO, not to the Doctrine entity.
        $resolver->setDefaults([
            'data_class' => CardInput::class,
        ]);
    }
}
