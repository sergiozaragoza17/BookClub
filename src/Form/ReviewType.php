<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Club;
use App\Entity\Review;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', HiddenType::class)
            ->add('content', TextareaType::class, [
                'label' => 'Content',
            ]);
        $builder->get('rating')
            ->addModelTransformer(new CallbackTransformer(
                fn($ratingAsString) => (int) $ratingAsString,
                fn($ratingAsInt) => (string) $ratingAsInt
            ));
        if ($options['is_admin']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Review::class,
            'is_admin' => false,
        ]);
    }
}
