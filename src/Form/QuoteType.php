<?php
//QuoteType.php
namespace App\Form;

use App\Entity\Quote;
use App\Entity\Customer;
use App\Repository\CustomerRepository;
use Symfony\Component\Form\AbstractType;
use App\Util\Quote\Status\QuoteStatusLabel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Validator\Constraint as EasyVowsConstraint;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class QuoteType extends AbstractType
{
public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * @var Quote|null $quoteData
         */
        $quoteData = $options['data'] ?? null;

        $defaultData = [
            'quote_issuance_date' => !empty($quoteData->getQuoteIssuanceDate())
                ? $quoteData->getQuoteIssuanceDate()
                : new \DateTime(),
            'expiry_date' => !empty($quoteData->getExpiryDate())
                ? $quoteData->getExpiryDate()
                : new \DateTime('+1 month'),
        ];

        $builder
            ->add('customer', EntityType::class, [
                'class' => Customer::class,
                'label' => 'Client',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Client',
                ],
'query_builder' => function (CustomerRepository $customerRepository) {
                    if(in_array('ROLE_ADMIN',$this->security->getUser()->getRoles())){
                        return $customerRepository->createQueryBuilder('c');
                    }else{
                        return $customerRepository->createQueryBuilder('c')
                            ->where('c.company = :company')
                            ->setParameter('company', $this->security->getUser()->getCompany());
                    }
                }
            ])
            ->add('quote_number', TextType::class, [
                'label' => 'Numéro',
                'help' => 'Le numéro de devis doit être unique.',
                'attr' => [
                    'placeholder' => 'Numéro',
                ],
            ])
            ->add('quote_issuance_date', DateType::class, [
                'label' => 'Date d\'émission',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Date d\'émission',
                ],
                'data' => $defaultData['quote_issuance_date'],
            ])
            ->add('expiry_date', DateType::class, [
                'label' => 'Date d\'expiration',
                'widget' => 'single_text',
                'attr' => [
                    'placeholder' => 'Date d\'expiration',
                ],
                'data' => $defaultData['expiry_date'],
            ])
            ->add('discount', NumberType::class, [
                'label' => 'Remise',
                'help' => 'Remise en pourcentage. Exemple: 10 pour 10% de remise.',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Remise',
                    'min' => '0',
                    'max' => '100',
                    'step' => '0.01',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => QuoteStatusLabel::all(),
            ])
            ->add('productQuotes', CollectionType::class, [
                'entry_type' => ProductQuoteType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
