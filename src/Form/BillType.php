<?php

namespace App\Form;

use App\Entity\Bill;
use App\Entity\Customer;
use App\Service\Customer\AccessibleCustomerService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class BillType extends AbstractType
{
    public function __construct(
        private readonly AccessibleCustomerService $accessibleCustomerService,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'label' => 'Client',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Client',
                ],
                'query_builder' => $this->accessibleCustomerService->getFindAllQuery(),
            ])
            ->add('bill_number', IntegerType::class, [
                'label' => 'Numéro',
                'attr' => [
                    'placeholder' => 'Entrez le numéro de la facture',
                ],
            ])
            ->add('billIssuanceDate', DateType::class, [
                'label' => 'Date d\'émission',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Entrez la date d\'émission de la facture',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Brouillon' => Bill::STATUS_DRAFT,
                    'Envoyé' => Bill::STATUS_SENT,
                ],
                'multiple' => false,
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Remise',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Entrez la remise',
                ],])
            ->add('productBills', CollectionType::class, [
                'entry_type' => ProductBillType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bill::class,
        ]);
    }
}
