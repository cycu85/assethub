<?php

namespace App\AsekuracyjnySPM\Form;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\Repository\DictionaryRepository;
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

class AsekuracyjnyReviewType extends AbstractType
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
                'label' => 'Planowana data przeglądu',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Data planowanego przeglądu jest wymagana'])
                ]
            ])
            
            ->add('reviewType', ChoiceType::class, [
                'label' => 'Typ przeglądu',
                'choices' => $reviewTypes,
                'placeholder' => 'Wybierz typ przeglądu',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Typ przeglądu jest wymagany'])
                ]
            ])
            
            ->add('reviewCompany', TextType::class, [
                'label' => 'Firma przeprowadzająca przegląd',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Centrum Bezpieczeństwa XYZ'
                ]
            ])
            
            ->add('notes', TextareaType::class, [
                'label' => 'Uwagi do przeglądu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Szczególne wymagania, obszary do sprawdzenia, historia problemów...'
                ]
            ]);

        // Pola do wypełnienia po zakończeniu przeglądu (tylko w trybie edycji)
        if ($options['mode'] === 'completion') {
            $builder
                ->add('result', ChoiceType::class, [
                    'label' => 'Wynik przeglądu',
                    'choices' => AsekuracyjnyReview::RESULTS,
                    'placeholder' => 'Wybierz wynik przeglądu',
                    'attr' => [
                        'class' => 'form-select'
                    ],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Wynik przeglądu jest wymagany'])
                    ]
                ])
                
                ->add('certificateNumber', TextType::class, [
                    'label' => 'Numer certyfikatu/protokołu',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Numer dokumentu potwierdzającego przegląd'
                    ]
                ])
                
                ->add('findings', TextareaType::class, [
                    'label' => 'Stwierdzone usterki/uwagi',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 4,
                        'placeholder' => 'Opis stwierdzonychech usterek, wad, obserwacji...'
                    ]
                ])
                
                ->add('recommendations', TextareaType::class, [
                    'label' => 'Zalecenia',
                    'required' => false,
                    'attr' => [
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'Zalecenia dotyczące dalszego użytkowania, napraw, wymiany...'
                    ]
                ])
                
                ->add('cost', MoneyType::class, [
                    'label' => 'Koszt przeglądu',
                    'required' => false,
                    'currency' => 'PLN',
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => '0.00'
                    ]
                ])
                
                ->add('nextReviewDate', DateType::class, [
                    'label' => 'Data następnego przeglądu',
                    'required' => false,
                    'widget' => 'single_text',
                    'attr' => [
                        'class' => 'form-control'
                    ],
                    'help' => 'Zalecana data kolejnego przeglądu'
                ]);
        }

        // Dodanie przycisku submit
        if ($options['include_submit']) {
            $submitLabel = match ($options['mode']) {
                'completion' => 'Zakończ przegląd',
                'edit' => 'Aktualizuj przegląd',
                default => 'Utwórz przegląd'
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
            'data_class' => AsekuracyjnyReview::class,
            'include_submit' => true,
            'mode' => 'create', // create, edit, completion
            'equipment' => null,
            'equipment_set' => null,
        ]);

        $resolver->setAllowedTypes('equipment', ['null', 'App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment']);
        $resolver->setAllowedTypes('equipment_set', ['null', 'App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet']);
    }

    private function getReviewTypeChoices(): array
    {
        $dictionaries = $this->dictionaryRepository->findByType('assek_review_types');
        $choices = [];
        
        foreach ($dictionaries as $dictionary) {
            if ($dictionary->isActive()) {
                $choices[$dictionary->getName()] = $dictionary->getValue();
            }
        }
        
        // Fallback jeśli słowniki nie są dostępne
        if (empty($choices)) {
            $choices = AsekuracyjnyReview::TYPES;
        }
        
        return $choices;
    }
}