<?php

namespace App\AsekuracyjnySPM\Form;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\Repository\DictionaryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AsekuracyjnyEquipmentSetType extends AbstractType
{
    public function __construct(
        private DictionaryRepository $dictionaryRepository
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Pobranie opcji z słowników
        $setTypes = $this->getSetTypeChoices();

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nazwa zestawu',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'np. Zestaw podstawowy do prac na wysokości'
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Nazwa zestawu jest wymagana']),
                    new Assert\Length(['max' => 255, 'maxMessage' => 'Nazwa nie może być dłuższa niż {{ limit }} znaków'])
                ]
            ])
            
            ->add('description', TextareaType::class, [
                'label' => 'Opis zestawu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Szczegółowy opis zestawu, jego przeznaczenie, zakres zastosowania...'
                ]
            ])
            
            ->add('setType', ChoiceType::class, [
                'label' => 'Typ zestawu',
                'required' => false,
                'choices' => $setTypes,
                'placeholder' => 'Wybierz typ zestawu',
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            
            ->add('nextReviewDate', DateType::class, [
                'label' => 'Data następnego przeglądu',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
                'help' => 'Planowana data kolejnego przeglądu zestawu'
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
            
            ->add('notes', TextareaType::class, [
                'label' => 'Uwagi',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Dodatkowe informacje o zestawie, specjalne wymagania, instrukcje...'
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
            'data_class' => AsekuracyjnyEquipmentSet::class,
            'include_submit' => true,
            'submit_label' => 'Zapisz'
        ]);
    }

    private function getSetTypeChoices(): array
    {
        $dictionaries = $this->dictionaryRepository->findByType('assek_set_types');
        $choices = [];
        
        foreach ($dictionaries as $dictionary) {
            if ($dictionary->isActive()) {
                $choices[$dictionary->getName()] = $dictionary->getValue();
            }
        }
        
        // Fallback jeśli słowniki nie są dostępne
        if (empty($choices)) {
            $choices = [
                'Podstawowy' => 'basic',
                'Zaawansowany' => 'advanced',
                'Specjalistyczny' => 'specialist',
                'Ratowniczy' => 'rescue'
            ];
        }
        
        return $choices;
    }
}