<?php

namespace App\Form;

use App\Entity\Category;
use App\Classe\Search;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;

class SearchType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('string', TextType::class, [
                'label'=>false,
                'required'=>false,
                'attr'=>[
                    'placeholder'=>'Votre recherche...',
                    'class'=>'form-control-sm'
                ]
            ])
            ->add('categories', EntityType::class,[ //EntityType pour indiquer qu'il faut lié cet input à une entité
                'label'=>false, //Désactive le label (pas nécessaire)
                'required'=>false,//Required false car pas obligatoire
                'class'=>Category::class, //Pour indiquer avec quelle classe l'input est associer
                'multiple'=>true,//Pour indiquer qu'il peut y avoir plusieurs choix
                'expanded'=>true //Permet de sélectionner plusieurs valeurs

            ])
            ->add('submit',SubmitType::class,[
                'label' => 'Filtrer',
                'attr' => [
                    'class' => 'btn-block btn-info' //class dans attr permet d'ajouter une classe à l'élément créer
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Search::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix() // à mettre pour permettre d'avoir un "bel" affichage dans l'URL
    {
        return '';
    }
}