<?php

namespace App\Form;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ClubBookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $club = $options['club'];

        $builder->add('book', EntityType::class, [
            'class' => Book::class,
            'choice_label' => 'title',
            'query_builder' => function (BookRepository $repo) use ($club) {
                return $repo->createQueryBuilder('b')
                    ->where('b.genre = :genre')
                    ->setParameter('genre', $club->getGenre());
            },
            'placeholder' => 'Select a book',
        ]);
    }

    public function configureOptions(\Symfony\Component\OptionsResolver\OptionsResolver $resolver): void
    {
        $resolver->setRequired('club');
    }
}