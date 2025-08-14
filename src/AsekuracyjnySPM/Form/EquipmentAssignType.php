<?php

namespace App\AsekuracyjnySPM\Form;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EquipmentAssignType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assignee', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getFullName() . ' (' . $user->getUsername() . ')';
                },
                'label' => 'Przypisz do pracownika',
                'placeholder' => 'Wybierz pracownika',
                'attr' => [
                    'class' => 'form-select'
                ],
                'query_builder' => function (UserRepository $repository) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('u.fullName', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Należy wybrać pracownika'])
                ]
            ])
            
            ->add('notes', TextareaType::class, [
                'label' => 'Uwagi do przypisania',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Cel przypisania, czas użytkowania, specjalne instrukcje...'
                ]
            ])
            
            ->add('submit', SubmitType::class, [
                'label' => 'Przypisz sprzęt',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Brak data_class - to nie jest encja
        ]);
    }
}