<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'       => 'Titre',
                'constraints' => [
                    new NotBlank(message: 'Le titre est obligatoire'),
                    new Length(max: 255),
                ],
                'attr' => ['placeholder' => 'Titre de l\'article'],
            ])
            ->add('content', TextareaType::class, [
                'label'       => 'Contenu',
                'constraints' => [
                    new NotBlank(message: 'Le contenu est obligatoire'),
                ],
                'attr' => [
                    'rows'        => 10,
                    'placeholder' => 'Contenu de l\'article',
                ],
            ])
            ->add('slug', TextType::class, [
                'label'       => 'Slug',
                'constraints' => [
                    new NotBlank(message: 'Le slug est obligatoire'),
                    new Length(max: 255),
                ],
                'attr' => ['placeholder' => 'mon-article'],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Brouillon' => 'draft',
                    'Publié'    => 'published',
                ],
            ])
            ->add('publishedAt', DateTimeType::class, [
                'label'  => 'Date de publication',
                'widget' => 'single_text',
            ])
            ->add('image', TextType::class, [
                'label'    => 'Image (URL)',
                'required' => false,
                'attr'     => ['placeholder' => 'https://...'],
            ])
            ->add('author', EntityType::class, [
                'label'        => 'Auteur',
                'class'        => User::class,
                'choice_label' => 'username',
            ])
            ->add('category', EntityType::class, [
                'label'        => 'Catégorie',
                'class'        => Category::class,
                'choice_label' => 'name',
                'required'     => false,
                'placeholder'  => 'Aucune catégorie',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
