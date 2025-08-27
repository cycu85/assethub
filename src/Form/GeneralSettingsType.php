<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class GeneralSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('app_name', TextType::class, [
                'label' => 'Nazwa aplikacji',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Wprowadź nazwę aplikacji'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Nazwa aplikacji nie może być pusta']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Nazwa aplikacji musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwa aplikacji nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('company_logo', FileType::class, [
                'label' => 'Logo firmy',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                            'image/svg+xml'
                        ],
                        'mimeTypesMessage' => 'Proszę wgrać prawidłowy plik obrazu (JPEG, PNG, GIF, WebP lub SVG)',
                        'maxSizeMessage' => 'Plik jest za duży ({{ size }} {{ suffix }}). Maksymalny rozmiar to {{ limit }} {{ suffix }}.',
                    ])
                ]
            ])
            ->add('primary_color', HiddenType::class, [
                'constraints' => [
                    new NotBlank(['message' => 'Główny kolor musi być wybrany'])
                ]
            ])
            ->add('primary_color_text', TextType::class, [
                'label' => 'Główny kolor aplikacji',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#405189',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7
                ]
            ])
            ->add('sidebar_bg_color', HiddenType::class, [
                'required' => false
            ])
            ->add('sidebar_bg_color_text', TextType::class, [
                'label' => 'Kolor tła menu',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#2a3042',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7
                ]
            ])
            ->add('sidebar_text_color', HiddenType::class, [
                'required' => false
            ])
            ->add('sidebar_text_color_text', TextType::class, [
                'label' => 'Kolor tekstu w menu',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#ffffff',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7
                ]
            ])
            ->add('sidebar_active_color', HiddenType::class, [
                'required' => false
            ])
            ->add('sidebar_active_color_text', TextType::class, [
                'label' => 'Kolor aktywnego elementu w menu',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '#405189',
                    'pattern' => '^#[0-9A-Fa-f]{6}$',
                    'maxlength' => 7
                ]
            ])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Strefa czasowa',
                'choices' => $this->getTimezoneChoices(),
                'attr' => [
                    'class' => 'form-select'
                ],
                'required' => false,
                'placeholder' => 'Wybierz strefę czasową...'
            ])
            ->add('date_format', ChoiceType::class, [
                'label' => 'Format daty',
                'choices' => [
                    'DD/MM/YYYY (31/12/2024)' => 'd/m/Y',
                    'MM/DD/YYYY (12/31/2024)' => 'm/d/Y', 
                    'YYYY-MM-DD (2024-12-31)' => 'Y-m-d',
                    'DD.MM.YYYY (31.12.2024)' => 'd.m.Y',
                    'DD-MM-YYYY (31-12-2024)' => 'd-m-Y'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'required' => false
            ])
            ->add('time_format', ChoiceType::class, [
                'label' => 'Format czasu', 
                'choices' => [
                    '24 godzinny (23:59)' => 'H:i',
                    '12 godzinny (11:59 PM)' => 'h:i A'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'required' => false
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz ustawienia',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }

    private function getTimezoneChoices(): array
    {
        $timezones = [
            // Europa
            'Europa/Warsaw' => 'Europa/Warsaw (Warszawa)',
            'Europe/London' => 'Europe/London (Londyn)',
            'Europe/Berlin' => 'Europe/Berlin (Berlin)', 
            'Europe/Paris' => 'Europe/Paris (Paryż)',
            'Europe/Rome' => 'Europe/Rome (Rzym)',
            'Europe/Madrid' => 'Europe/Madrid (Madryt)',
            'Europe/Amsterdam' => 'Europe/Amsterdam (Amsterdam)',
            'Europe/Vienna' => 'Europe/Vienna (Wiedeń)',
            'Europe/Prague' => 'Europe/Prague (Praga)',
            'Europe/Budapest' => 'Europe/Budapest (Budapeszt)',
            'Europe/Moscow' => 'Europe/Moscow (Moskwa)',
            
            // Ameryka Północna
            'America/New_York' => 'America/New_York (EST)',
            'America/Chicago' => 'America/Chicago (CST)',  
            'America/Denver' => 'America/Denver (MST)',
            'America/Los_Angeles' => 'America/Los_Angeles (PST)',
            'America/Toronto' => 'America/Toronto (Kanada)',
            
            // Azja
            'Asia/Tokyo' => 'Asia/Tokyo (Tokio)',
            'Asia/Shanghai' => 'Asia/Shanghai (Szanghaj)',
            'Asia/Dubai' => 'Asia/Dubai (Dubaj)',
            'Asia/Kolkata' => 'Asia/Kolkata (Kalkuta)',
            
            // Australia
            'Australia/Sydney' => 'Australia/Sydney (Sydney)',
            'Australia/Melbourne' => 'Australia/Melbourne (Melbourne)',
            
            // UTC
            'UTC' => 'UTC (Czas uniwersalny)'
        ];

        // Odwróć tablicę - klucze to co wyświetlamy, wartości to co zapisujemy
        return array_flip($timezones);
    }
}