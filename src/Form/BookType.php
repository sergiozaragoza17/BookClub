<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Genre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['is_admin'] || $options['is_new']) {
            $builder
                ->add('title', TextType::class, [
                    'label' => 'Title',
                ])
                ->add('author', TextType::class, [
                    'label' => 'Author',
                ])
                ->add('publishedYear', IntegerType::class, [
                    'label' => 'Published Year',
                ])
                ->add('description', TextareaType::class, [
                    'label' => 'Description',
                    'required' => false,
                ])
                ->add('genre', EntityType::class, [
                    'class' => Genre::class,
                    'choice_label' => 'name',
                    'placeholder' => 'Select a genre',

                ])
                ->add('coverImage', FileType::class, [
                    'label' => 'Cover Image (PNG/JPEG)',
                    'mapped' => false,
                    'required' => false,
                    'constraints' => [
                        new File([
                            'maxSize' => '2M',
                            'mimeTypes' => ['image/jpeg', 'image/png'],
                            'mimeTypesMessage' => 'Please upload a valid image (JPEG or PNG)',
                        ])
                    ],
                ]);
        }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
            'is_admin' => false,
            'is_new' => false,
        ]);
    }
}
