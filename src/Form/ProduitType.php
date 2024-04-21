<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomProduit')
            ->add('description', TextareaType::class, [ 
            'attr' => ['class' => 'form-control', 'id' => 'floatingDescriptionProduit']
        ])
            ->add('prix')
            ->add('categorie', EntityType::class, [
                'class' => categorie::class,
                'choice_label' => 'nomCategorie',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}