<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;


class RegisterUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Email(),
                ],
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'max' => 50
                    ])
                ],
                'mapped' => false,
            ])
            ->add('firstname', TextType::class, [
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 30
                    ])
                ]
            ])
            ->add('lastname', TextType::class, [
                'constraints' => [
                    new Length([
                        'min' => 2,
                        'max' => 30
                    ])
                ]
            ])
            ->add('username')
            // ->add('image_name')
            // ->add('lastActivityTime', DateTimeType::class, [
            //     'widget' => 'single_text',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => false,  // Désactiver CSRF pour ce formulaire spécifique
        ]);
    }
}
