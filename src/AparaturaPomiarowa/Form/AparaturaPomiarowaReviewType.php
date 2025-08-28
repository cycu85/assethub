<?php

namespace App\AparaturaPomiarowa\Form;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\Repository\DictionaryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AparaturaPomiarowaReviewType extends AbstractType
{
    public function __construct(
        private DictionaryRepository $dictionaryRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Pobranie opcji z słowników
        $reviewTypes = $this->getReviewTypeChoices();

        $builder
            ->add('plannedDate', DateType::class, [
                'label' => 'Planowana data kalibracji',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Data planowanej kalibracji jest wymagana'])
                ]
            ])
            
            ->add('reviewType', ChoiceType::class, [
                'label' => 'Typ kalibracji',
                'choices' => $reviewTypes,
                'placeholder' => 'Wybierz typ kalibracji',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Typ kalibracji jest wymagany'])
                ]
            ])
            
            ->add('reviewCompany', TextType::class, [
                'label' => 'Laboratorium kalibracyjne',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Laboratorium Metrologiczne ABC'
                ]
            ])
            
            ->add('notes', TextareaType::class, [
                'label' => 'Uwagi do kalibracji',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Szczególne wymagania kalibracyjne, zakresy pomiarowe, historia problemów...'
                ]
            ]);

        // Dodanie pól wyboru urządzenia/zestawu tylko jeśli nie są określone w opcjach
        if (!$options['equipment'] && !$options['equipment_set']) {
            $builder
                ->add('equipment', EntityType::class, [
                    'class' => AparaturaPomiarowaEquipment::class,
                    'choice_label' => function(AparaturaPomiarowaEquipment $equipment) {
                        return $equipment->getName() . ' (' . $equipment->getInventoryNumber() . ')';
                    },
                    'label' => 'Urządzenie do kalibracji',
                    'required' => false,
                    'placeholder' => 'Wybierz urządzenie',
                    'attr' => [
                        'class' => 'form-select',
                        'data-toggle' => 'equipment-select'
                    ],
                    'query_builder' => function($repository) {
                        return $repository->createQueryBuilder('e')
                            ->where('e.status IN (:statuses)')
                            ->setParameter('statuses', ['available', 'assigned'])
                            ->orderBy('e.name', 'ASC');
                    }
                ])
                
                ->add('equipmentSet', EntityType::class, [
                    'class' => AparaturaPomiarowaEquipmentSet::class,
                    'choice_label' => function(AparaturaPomiarowaEquipmentSet $equipmentSet) {
                        return $equipmentSet->getName() . ' (' . $equipmentSet->getEquipment()->count() . ' elementów)';
                    },
                    'label' => 'Zestaw aparatury do kalibracji',
                    'required' => false,
                    'placeholder' => 'Wybierz zestaw',
                    'attr' => [
                        'class' => 'form-select',
                        'data-toggle' => 'equipment-set-select'
                    ],
                    'query_builder' => function($repository) {
                        return $repository->createQueryBuilder('es')
                            ->where('es.status IN (:statuses)')
                            ->setParameter('statuses', ['available', 'assigned'])
                            ->orderBy('es.name', 'ASC');
                    }
                ]);
        }

        // Pola do wypełnienia po zakończeniu kalibracji (tylko w trybie edycji)
        if ($options['mode'] === 'completion') {
            $builder
                ->add('result', ChoiceType::class, [
                    'label' => 'Wynik kalibracji',
                    'choices' => AparaturaPomiarowaReview::RESULTS,
                    'placeholder' => 'Wybierz wynik kalibracji',
                    'attr' => [
                        'class' => 'form-select'
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Wynik kalibracji jest wymagany'])
                    ]
                ])
                
                ->add('certificateNumber', TextType::class, [
                    'label' => 'Numer certyfikatu kalibracji',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Numer certyfikatu z laboratorium'
                    ]
                ])
                
                ->add('findings', TextareaType::class, [
                    'label' => 'Stwierdzone odchyłki/uwagi',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 4,
                        'placeholder' => 'Opis stwierdzonych odchyłek, niepewności pomiarowych, uwag technicznych...'
                    ]
                ])
                
                ->add('recommendations', TextareaType::class, [
                    'label' => 'Zalecenia',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'Zalecenia dotyczące dalszego użytkowania, konserwacji, następnej kalibracji...'
                    ]
                ])
                
                ->add('cost', MoneyType::class, [
                    'label' => 'Koszt kalibracji',
                    'required' => false,
                    'currency' => 'PLN',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => '0.00'
                    ]
                ])
                
                ->add('nextReviewDate', DateType::class, [
                    'label' => 'Data następnej kalibracji',
                    'required' => false,
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'help' => 'Zalecana data kolejnej kalibracji'
                ]);
        }

        // Dodanie przycisku submit
        if ($options['include_submit']) {
            $submitLabel = match ($options['mode']) {
                'completion' => 'Zakończ kalibrację',
                'edit' => 'Aktualizuj kalibrację',
                default => 'Utwórz kalibrację'
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
            'data_class' => AparaturaPomiarowaReview::class,
            'include_submit' => true,
            'mode' => 'create', // create, edit, completion
            'equipment' => null,
            'equipment_set' => null,
        ]);

        $resolver->setAllowedTypes('equipment', ['null', 'App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment']);
        $resolver->setAllowedTypes('equipment_set', ['null', 'App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet']);
    }

    private function getReviewTypeChoices(): array
    {
        $dictionaries = $this->dictionaryRepository->findByType('aparatura_review_types');
        $choices = [];
        
        foreach ($dictionaries as $dictionary) {
            if ($dictionary->isActive()) {
                $choices[$dictionary->getName()] = $dictionary->getValue();
            }
        }
        
        // Fallback jeśli słowniki nie są dostępne
        if (empty($choices)) {
            $choices = AparaturaPomiarowaReview::TYPES;
        }
        
        return $choices;
    }
}