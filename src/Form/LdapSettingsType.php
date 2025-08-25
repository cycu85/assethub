<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class LdapSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Konfiguracja serwera LDAP
            ->add('ldap_enabled', CheckboxType::class, [
                'label' => 'Włącz integrację LDAP',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('ldap_host', TextType::class, [
                'label' => 'Serwer LDAP',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'ldap://192.168.1.100 lub ldaps://ad.company.com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Adres serwera LDAP jest wymagany']),
                    new Length([
                        'min' => 3,
                        'max' => 255,
                        'minMessage' => 'Adres serwera musi mieć co najmniej {{ limit }} znaki',
                        'maxMessage' => 'Adres serwera nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('ldap_port', IntegerType::class, [
                'label' => 'Port',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '389 (LDAP) lub 636 (LDAPS)'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Port LDAP jest wymagany']),
                    new Range([
                        'min' => 1,
                        'max' => 65535,
                        'notInRangeMessage' => 'Port musi być między {{ min }} a {{ max }}',
                    ])
                ]
            ])
            ->add('ldap_encryption', ChoiceType::class, [
                'label' => 'Szyfrowanie',
                'choices' => [
                    'Brak' => 'none',
                    'StartTLS' => 'starttls',
                    'SSL/TLS' => 'ssl'
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Typ szyfrowania jest wymagany'])
                ]
            ])
            
            // Dane uwierzytelniania
            ->add('ldap_bind_dn', TextType::class, [
                'label' => 'Bind DN (użytkownik serwisowy)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'CN=ldapuser,OU=Service Accounts,DC=company,DC=com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Bind DN jest wymagany']),
                    new Length([
                        'min' => 5,
                        'max' => 500,
                        'minMessage' => 'Bind DN musi mieć co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Bind DN nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('ldap_bind_password', PasswordType::class, [
                'label' => 'Hasło użytkownika serwisowego',
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
            
            // Konfiguracja wyszukiwania
            ->add('ldap_base_dn', TextType::class, [
                'label' => 'Base DN (katalog bazowy)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'OU=Users,DC=company,DC=com'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Base DN jest wymagany']),
                    new Length([
                        'min' => 5,
                        'max' => 500,
                        'minMessage' => 'Base DN musi mieć co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Base DN nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            ->add('ldap_user_filter', TextType::class, [
                'label' => 'Filtr użytkowników',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '(&(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Filtr użytkowników jest wymagany']),
                    new Length([
                        'min' => 5,
                        'max' => 500,
                        'minMessage' => 'Filtr musi mieć co najmniej {{ limit }} znaków',
                        'maxMessage' => 'Filtr nie może mieć więcej niż {{ limit }} znaków',
                    ])
                ]
            ])
            
            // Mapowanie pól
            ->add('ldap_map_username', TextType::class, [
                'label' => 'Pole username (login)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'sAMAccountName'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Mapowanie username jest wymagane']),
                ]
            ])
            ->add('ldap_map_email', TextType::class, [
                'label' => 'Pole email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'mail'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Mapowanie email jest wymagane']),
                ]
            ])
            ->add('ldap_map_firstname', TextType::class, [
                'label' => 'Pole imię',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'givenName'
                ],
                'required' => false
            ])
            ->add('ldap_map_lastname', TextType::class, [
                'label' => 'Pole nazwisko',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'sn'
                ],
                'required' => false
            ])
            ->add('ldap_map_displayname', TextType::class, [
                'label' => 'Pole pełna nazwa',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'displayName'
                ],
                'required' => false
            ])
            
            // Mapowanie dodatkowych pól pracowniczych
            ->add('ldap_map_employee_number', TextType::class, [
                'label' => 'Pole numer pracownika',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'employeeNumber'
                ],
                'required' => false
            ])
            ->add('ldap_map_phone', TextType::class, [
                'label' => 'Pole telefon',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'telephoneNumber'
                ],
                'required' => false
            ])
            ->add('ldap_map_position', TextType::class, [
                'label' => 'Pole stanowisko',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'title'
                ],
                'required' => false
            ])
            ->add('ldap_map_department', TextType::class, [
                'label' => 'Pole dział',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'department'
                ],
                'required' => false
            ])
            ->add('ldap_map_office', TextType::class, [
                'label' => 'Pole lokalizacja/oddział',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'physicalDeliveryOfficeName'
                ],
                'required' => false
            ])
            ->add('ldap_map_manager', TextType::class, [
                'label' => 'Pole przełożony',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'manager'
                ],
                'required' => false
            ])
            ->add('ldap_map_status', TextType::class, [
                'label' => 'Pole status pracownika',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'userAccountControl'
                ],
                'required' => false
            ])
            
            // Opcje synchronizacji
            ->add('ldap_auto_create_users', CheckboxType::class, [
                'label' => 'Automatycznie twórz nowych użytkowników',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('ldap_update_existing_users', CheckboxType::class, [
                'label' => 'Aktualizuj istniejących użytkowników',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ]
            ])
            ->add('ldap_ignore_ssl_cert', CheckboxType::class, [
                'label' => 'Ignoruj błędny certyfikat SSL/TLS',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'help' => 'Wyłącza weryfikację certyfikatu SSL/TLS (użyj tylko w środowisku testowym)'
            ])
            
            // Przyciski akcji
            ->add('save', SubmitType::class, [
                'label' => 'Zapisz ustawienia',
                'attr' => [
                    'class' => 'btn btn-primary'
                ]
            ])
            ->add('test', SubmitType::class, [
                'label' => 'Testuj połączenie',
                'attr' => [
                    'class' => 'btn btn-info ms-2',
                    'formnovalidate' => true
                ]
            ])
            ->add('sync_existing', SubmitType::class, [
                'label' => 'Synchronizuj istniejących',
                'attr' => [
                    'class' => 'btn btn-warning ms-2',
                    'formnovalidate' => true
                ]
            ])
            ->add('sync_new', SubmitType::class, [
                'label' => 'Synchronizuj nowych',
                'attr' => [
                    'class' => 'btn btn-success ms-2',
                    'formnovalidate' => true
                ]
            ])
            ->add('sync_hierarchy', SubmitType::class, [
                'label' => 'Synchronizuj hierarchię',
                'attr' => [
                    'class' => 'btn btn-secondary ms-2',
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