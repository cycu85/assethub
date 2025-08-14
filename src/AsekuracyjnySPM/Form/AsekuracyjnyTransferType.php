<?php

namespace App\AsekuracyjnySPM\Form;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyTransfer;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class AsekuracyjnyTransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recipient', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getFullName() . ' (' . $user->getUsername() . ')';
                },
                'label' => 'Odbiorca',
                'placeholder' => 'Wybierz odbiorców',
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
                    new Assert\NotBlank(['message' => 'Należy wybrać odbiorców'])
                ]
            ])
            
            ->add('transferDate', DateType::class, [
                'label' => 'Data przekazania',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'data' => new \DateTime(), // Domyślnie dzisiejsza data
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Data przekazania jest wymagana'])
                ]
            ])
            
            ->add('returnDate', DateType::class, [
                'label' => 'Planowana data zwrotu',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Jeśli określona, system będzie monitorować terminy'
            ])
            
            ->add('purpose', TextareaType::class, [
                'label' => 'Cel przekazania',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Opis celu, dla którego przekazywany jest sprzęt (praca na wysokości, szkolenie, projekt XYZ)...'
                ]
            ])
            
            ->add('conditions', TextareaType::class, [
                'label' => 'Warunki przekazania',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Szczególne warunki użytkowania, odpowiedzialność, wymagania bezpieczeństwa...'
                ]
            ])
            
            ->add('location', TextType::class, [
                'label' => 'Miejsce użytkowania',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Budowa A, Biuro, Teren zakładu'
                ]
            ])
            
            ->add('notes', TextareaType::class, [
                'label' => 'Uwagi',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Dodatkowe informacje, instrukcje, ostrzeżenia...'
                ]
            ]);

        // Pole do przesyłania skanu protokołu (tylko w trybie upload)
        if ($options['mode'] === 'upload_scan') {
            $builder->add('protocolScan', FileType::class, [
                'label' => 'Skan podpisanego protokołu',
                'mapped' => false, // Nie mapuje bezpośrednio do encji
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.jpg,.jpeg,.png'
                ],
                'help' => 'Dozwolone formaty: PDF, JPG, PNG. Maksymalny rozmiar: 10MB',
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        'mimeTypesMessage' => 'Proszę przesłać prawidłowy plik PDF lub obraz (JPG, PNG)',
                        'maxSizeMessage' => 'Plik jest zbyt duży ({{ size }} {{ suffix }}). Maksymalny rozmiar to {{ limit }} {{ suffix }}.'
                    ])
                ]
            ]);
        }

        // Dodanie przycisku submit
        if ($options['include_submit']) {
            $submitLabel = match ($options['mode']) {
                'upload_scan' => 'Prześlij skan protokołu',
                'edit' => 'Aktualizuj przekazanie',
                default => 'Utwórz przekazanie'
            };
            
            $builder->add('submit', SubmitType::class, [
                'label' => $submitLabel,
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AsekuracyjnyTransfer::class,
            'include_submit' => true,
            'mode' => 'create' // create, edit, upload_scan
        ]);
    }
}