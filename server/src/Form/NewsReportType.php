<?php

namespace App\Form;

use App\Entity\NewsReport;
use App\Entity\ReportTag;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewsReportType extends AbstractType
{
    // Auteurs: Sadok LAJMI, Mélina WANG
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, [
                'label' => 'Article Title*',
            ])
            ->add('tag', EntityType::class, [
                'class' => ReportTag::class,
                'choice_label' => 'name',
                'label' => 'Tag*',
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content*',
                'attr' => [
                    'rows' => 12,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewsReport::class,
        ]);
    }
}
