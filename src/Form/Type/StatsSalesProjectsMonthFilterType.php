<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatsSalesProjectsMonthFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        for ($i = date('Y'); $i >= 2022; --$i) {
            $choices[$i] = $i;
        }

        $builder
            ->add('date', ChoiceType::class, [
                'label' => 'Année',
                'attr' => [
                    'data-model' => 'date',
                ],
                'choices' => $choices,
            ]
            )
            ->add('users', EntityType::class, [
                'class' => User::class,
                'multiple' => true,
                'expanded' => true,
                'placeholder' => '',
                'required' => true,
                'query_builder' => function (UserRepository $er) {
                    return $er->getCommercialsQb();
                },
                'label' => 'Commercial',
            ]
            )
            ->add('project', EntityType::class, [
                'class' => Project::class,
                'multiple' => false,
                'expanded' => false,
                'placeholder' => '',
                'label' => 'Projet',
                'attr' => [
                    'data-model' => 'project',
                ],
                'required' => true,
                'query_builder' => function (ProjectRepository $er) {
                    return $er->createQueryBuilder('p')
                        ->where('p.archive = false')
                        ->orderBy('p.name', 'ASC');
                },
            ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
        ]);
    }
}
