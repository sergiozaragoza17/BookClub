<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => false,
            ])
            ->add('userName', TextType::class, [
                'label' => 'UserName',
                'required' => false,
            ])
            ->add('email', EmailType::class)
            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'required' => false,
            ])
            ->add('favBook', EntityType::class, [
                'class' => Book::class,
                'choice_label' => 'title',
                'multiple' => false,
                'expanded' => false,
                'query_builder' => function (\App\Repository\BookRepository $repo) use ($options) {
                    $user = $options['user'];
                    return $repo->createQueryBuilder('b')
                        ->join('b.userBooks', 'ub')
                        ->where('ub.user = :user')
                        ->andWhere('ub.status = :status')
                        ->setParameter('user', $user)
                        ->setParameter('status', 'finished');
                },
                'required' => false,
                'placeholder' => 'Select a book',
                'label' => 'Favorite Book',
            ])
            ->add('currentlyReading', EntityType::class, [
                'class' => Book::class,
                'choice_label' => 'title',
                'multiple' => false,
                'expanded' => false,
                'query_builder' => function (\App\Repository\BookRepository $repo) use ($options) {
                    $user = $options['user'];
                    return $repo->createQueryBuilder('b')
                        ->join('b.userBooks', 'ub')
                        ->where('ub.user = :user')
                        ->andWhere('ub.status = :status')
                        ->setParameter('user', $user)
                        ->setParameter('status', 'reading');
                },
                'required' => false,
                'placeholder' => 'Select a book',
                'label' => 'Currently Reading',
            ])
            ->add('favGenres', ChoiceType::class, [
                'label' => 'Favorite Genres',
                'choices' => [
                    'Fantasy' => 'fantasy',
                    'Science Fiction' => 'sci-fi',
                    'Romance' => 'romance',
                    'Mystery' => 'mystery',
                    'Non-fiction' => 'non-fiction',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('profileImage', FileType::class, [
                'label' => 'Profile Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG or PNG)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'user' => null,
        ]);
    }
}