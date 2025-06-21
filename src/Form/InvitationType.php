<?php

namespace App\Form;

use App\Entity\Invitation;
use App\Entity\Note;
use App\Entity\User;
use App\Enums\InvitationStatusEnum;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description')
            ->add('note', EntityType::class, [
                'class' => Note::class,
                'choice_label' => 'title',
            ])
            ->remove('status', ChoiceType::class, [
                'choices' => array_combine(
                    array_map(fn($status) => ucfirst(strtolower($status->name)), InvitationStatusEnum::cases()),
                    InvitationStatusEnum::cases()
                ),
                'choice_label' => fn($choice) => ucfirst(strtolower($choice->name)),
                'choice_value' => fn(?InvitationStatusEnum $choice) => $choice?->value,
            ])
            ->remove('createdAt', null, [
                'widget' => 'single_text',
            ])
            ->remove('updatedAt', null, [
                'widget' => 'single_text',
            ])
            ->remove('sender', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
            ])
            ->add('receiver', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
            ])

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invitation::class,
        ]);
    }
}
