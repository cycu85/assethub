<?php

namespace App\AsekuracyjnySPM\Form;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\Repository\DictionaryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AsekuracyjnyEquipmentType extends AbstractType
{
    public function __construct(
        private DictionaryRepository $dictionaryRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Pobranie opcji z słowników
        $equipmentTypes = $this->getEquipmentTypeChoices();
        $statusChoices = AsekuracyjnyEquipment::STATUSES;

        $builder
            ->add('inventoryNumber', TextType::class, [
                'label' => 'Numer inwentarzowy',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. ASK-001-2024'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Numer inwentarzowy jest wymagany']),
                    new Assert\Length(['max' => 100, 'maxMessage' => 'Numer inwentarzowy nie może być dłuższy niż {{ limit }} znaków'])
                ]
            ])
            
            ->add('name', TextType::class, [
                'label' => 'Nazwa sprzętu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Szelki robocze Petzl AVAO'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Nazwa sprzętu jest wymagana']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'Nazwa nie może być dłuższa niż {{ limit }} znaków'])
                ]
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Opis',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Szczegółowy opis sprzętu, jego przeznaczenie, cechy charakterystyczne...'
                ]
            ])
            
            ->add('equipmentType', ChoiceType::class, [
                'label' => 'Typ sprzętu',
                'choices' => $equipmentTypes,
                'placeholder' => 'Wybierz typ sprzętu',
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Typ sprzętu jest wymagany'])
                ]
            ])
            
            ->add('manufacturer', TextType::class, [
                'label' => 'Producent',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Petzl, Black Diamond, Edelrid'
                ]
            ])
            
            ->add('model', TextType::class, [
                'label' => 'Model',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. AVAO BOD, Momentum, Jay III'
                ]
            ])
            
            ->add('serialNumber', TextType::class, [
                'label' => 'Numer seryjny',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numer seryjny producenta'
                ]
            ])
            
            ->add('manufacturingDate', DateType::class, [
                'label' => 'Data produkcji',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            
            ->add('purchaseDate', DateType::class, [
                'label' => 'Data zakupu',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            
            ->add('purchasePrice', MoneyType::class, [
                'label' => 'Cena zakupu',
                'required' => false,
                'currency' => 'PLN',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00'
                ]
            ])
            
            ->add('supplier', TextType::class, [
                'label' => 'Dostawca',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nazwa firmy dostawcy'
                ]
            ])
            
            ->add('invoiceNumber', TextType::class, [
                'label' => 'Numer faktury',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numer faktury zakupu'
                ]
            ])
            
            ->add('warrantyExpiry', DateType::class, [
                'label' => 'Koniec gwarancji',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            
            ->add('nextReviewDate', DateType::class, [
                'label' => 'Data następnego przeglądu',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Planowana data kolejnego przeglądu okresowego'
            ])
            
            ->add('reviewIntervalMonths', IntegerType::class, [
                'label' => 'Okres przeglądu (miesiące)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 1,
                    'max' => 120,
                    'placeholder' => 'np. 12'
                ],
                'help' => 'Odstęp między przeglądami w miesiącach',
                'constraints' => [
                    new Assert\Positive(['message' => 'Okres przeglądu musi być liczbą dodatnią']),
                    new Assert\Range(['min' => 1, 'max' => 120, 'notInRangeMessage' => 'Okres przeglądu musi być między {{ min }} a {{ max }} miesiącami'])
                ]
            ])
            
            ->add('location', TextType::class, [
                'label' => 'Lokalizacja',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Magazyn A, Szafa nr 3, Biuro'
                ]
            ])
            
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => $statusChoices,
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Status jest wymagany']),
                    new Assert\Choice(['choices' => array_values($statusChoices)])
                ]
            ])
            
            ->add('notes', TextareaType::class, [
                'label' => 'Uwagi',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Dodatkowe informacje, historia napraw, specjalne wymagania...'
                ]
            ]);

        // Dodanie przycisku submit tylko w trybie tworzenia/edycji
        if ($options['include_submit']) {
            $builder->add('submit', SubmitType::class, [
                'label' => $options['submit_label'],
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AsekuracyjnyEquipment::class,
            'include_submit' => true,
            'submit_label' => 'Zapisz'
        ]);
    }

    private function getEquipmentTypeChoices(): array
    {
        $dictionaries = $this->dictionaryRepository->findByType('assek_equipment_types');
        $choices = [];
        
        foreach ($dictionaries as $dictionary) {
            if ($dictionary->isActive()) {
                $choices[$dictionary->getName()] = $dictionary->getValue();
            }
        }
        
        // Fallback jeśli słowniki nie są dostępne
        if (empty($choices)) {
            $choices = [
                'Szelki' => 'harness',
                'Liny' => 'rope',
                'Kaski' => 'helmet',
                'Zaciski' => 'ascender',
                'Blokady' => 'stopper'
            ];
        }
        
        return $choices;
    }
}