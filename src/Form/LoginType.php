<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label'       => 'Nom d\'utilisateur',
                'constraints' => [
                    new NotBlank(message: 'Le nom d\'utilisateur est obligatoire'),
                ],
                'attr' => ['placeholder' => 'Votre username'],
            ])
            ->add('password', PasswordType::class, [
                'label'       => 'Mot de passe',
                'constraints' => [
                    new NotBlank(message: 'Le mot de passe est obligatoire'),
                ],
                'attr' => ['placeholder' => 'Votre mot de passe'],
            ])
            ->add('_remember_me', CheckboxType::class, [
                'label'    => 'Se souvenir de moi',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
