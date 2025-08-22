<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class EmailSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('smtp_host', TextType::class, [
                'label' => 'Serwer SMTP',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'smtp.gmail.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Adres serwera SMTP jest wymagany']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Adres serwera musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Adres serwera nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('smtp_port', IntegerType::class, [
                'label' => 'Port',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '587'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Port SMTP jest wymagany']),
                    new Range([
                        'min' => 1,
                        'max' => 65535,
                        'notInRangeMessage' => 'Port musi być między {{ min }} a {{ max }}',
                    ])
                ]
            ])
            ->add('smtp_encryption', ChoiceType::class, [
                'label' => 'Szyfrowanie',
                'choices' => [
                    'Brak' => 'none',
                    'TLS' => 'tls',
                    'SSL' => 'ssl'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Typ szyfrowania jest wymagany'])
                ]
            ])
            ->add('smtp_username', TextType::class, [
                'label' => 'Nazwa użytkownika',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'your-email@gmail.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Nazwa użytkownika jest wymagana']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Nazwa użytkownika musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwa użytkownika nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('smtp_password', PasswordType::class, [
                'label' => 'Hasło',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '••••••••'
                ],
                'required' => false,
                'constraints' => [
                    new Length([
                        'min' => 1,
                        'max' => 255,
                        'minMessage' => 'Hasło musi mieć co najmniej {{ limit }} znak',
                        'maxMessage' => 'Hasło nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('from_email', EmailType::class, [
                'label' => 'Adres nadawcy',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'noreply@your-domain.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Adres nadawcy jest wymagany']),
                    new Email(['message' => 'Podaj prawidłowy adres email'])
                ]
            ])
            ->add('from_name', TextType::class, [
                'label' => 'Nazwa nadawcy',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'AssetHub System'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Nazwa nadawcy jest wymagana']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Nazwa nadawcy musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Nazwa nadawcy nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('history_retention_days', IntegerType::class, [
                'label' => 'Dni przechowywania historii maili',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '90'
                ],
                'help' => 'Liczba dni przez które przechowywana jest historia wysłanych maili (minimum 7 dni)',
                'constraints' => [
                    new NotBlank(['message' => 'Liczba dni jest wymagana']),
                    new Range([
                        'min' => 7,
                        'max' => 3650,
                        'notInRangeMessage' => 'Liczba dni musi być między {{ min }} a {{ max }}',
                    ])
                ]
            ])
            ->add('test_email', EmailType::class, [
                'label' => 'Email testowy',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'test@example.com'
                ],
                'constraints' => [
                    new Email(['message' => 'Podaj prawidłowy adres email'])
                ]
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz ustawienia',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->add('test', SubmitType::class, [
                'label' => 'Wyślij test',
                'attr' => [
                    'class' => 'btn btn-info ms-2',
                    'formnovalidate' => true
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}