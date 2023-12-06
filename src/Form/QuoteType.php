<?php

namespace App\Form;

use App\Entity\Quote;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Product;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status')
            ->add('quote_number')
            ->add('quote_issuance_date')
            ->add('expiry_date')
            ->add('total_price')
            ->add('discount')
            ->add('tva')
            ->add('customer')
            ->add('products');
            //->add('products', EntityType::class, [
            //     'class' => 'App\Entity\Product',
            //     'choice_label' => 'name',
            //     'multiple' => true,
            //     'expanded' => true,
            // ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
