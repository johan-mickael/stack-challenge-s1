<?php

namespace App\Controller\Quote\Live;

use App\Entity\Product;
use App\Entity\Quote;
use App\Form\QuoteType;
use App\Service\Quote\QuoteCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsLiveComponent]
class QuoteCreator extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentWithFormTrait;

    #[LiveProp(writable: true)]
    #[Assert\Valid]
    public ?Quote $quoteData = null;

    #[LiveProp(writable: true)]
    #[Assert\NotNull(message: 'Vous devez choisir un client.')]
    #[Assert\Positive(message: 'Le client choisi est invalide.')]
    public ?int $customerId = null;

    #[LiveProp(writable: true)]
    #[Assert\NotNull(message: 'Vous devez saisir un numéro de devis.')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le numéro de devis doit contenir au moins {{ limit }} caractères.', maxMessage: 'Le numéro de devis doit contenir au maximum {{ limit }} caractères.')]
    public ?string $quote_number = null;

    #[LiveProp(writable: true)]
    #[Assert\PositiveOrZero(message: 'La remise doit être supérieur ou égale à 0.')]
    #[Assert\LessThanOrEqual(value: 100, message: 'La remise doit être inférieur ou égale à 100.')]
    public ?float $discount = 0.0;

    #[LiveProp(writable: true)]
    #[Assert\Choice(choices: [0, 1, 2, 3], message: 'Le statut choisi est invalide.')]
    public ?int $status = null;

    #[LiveProp(writable: true, format: 'Y-m-d')]
    #[Assert\NotNull(message: 'Vous devez choisir une date d\'émission.')]
    #[Assert\LessThan(propertyPath: 'expiry_date', message: 'La date d\'émission doit être inférieur à la date d\'expiration.')]
    public ?\DateTime $quote_issuance_date = null;

    #[LiveProp(writable: true, format: 'Y-m-d')]
    #[Assert\NotNull(message: 'Vous devez choisir une date d\'expiration.')]
    #[Assert\GreaterThan(propertyPath: 'quote_issuance_date', message: 'La date d\'expiration doit être supérieur à la date d\'émission.')]
    public ?\DateTime $expiry_date = null;

    #[LiveProp]
    public array $lineItems = [];

    #[LiveProp]
    public string $mode = 'create';

    #[LiveProp]
    public float $total = 0.0;

    #[LiveProp]
    public $totalDiscount = 0.0;

    #[LiveProp]
    public float $totalWithDiscount = 0.0;

    public bool $savedSuccessfully = false;

    private const FIELD_TO_VALIDATE = [
        'customerId',
        'quote_issuance_date',
        'expiry_date',
        'discount',
        'status',
    ];

    public function __construct(
        private readonly QuoteCreatorService $quoteCreatorService,
    )
    {
    }

    public function mount(Quote $quoteData): void
    {
        $this->quoteData = $quoteData;
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(QuoteType::class, $this->quoteData);
    }

    private function ensureErrorMessageBeforeSaving(): void
    {
        $this->addFlash('error', 'Veuillez corriger les erreurs avant de sauvegarder.');
    }

    private function clearFlashBag(Session $session): void
    {
        $session->getFlashBag()->clear();
    }

    private function postQuoteSaving(): RedirectResponse
    {
        $this->addFlash('success', 'Votre devis a été enregistré avec succès.');
        $this->savedSuccessfully = true;
        return $this->redirectToRoute('app_quote_show', [
            'id' => $this->quoteData->getId(),
        ]);
    }

    private function ensureDefaultCustomer(?Quote $quote = null): void
    {
        $this->customerId = $this->quoteCreatorService->getDefaultCustomer($quote)->getId();
    }

    private function setDefaultData(?Quote $quote = null): void
    {
        $this->setDefaultQuoteNumber($quote);
        $this->setDefaultDates($quote);
        $this->setDefaultDiscount($quote);
        $this->setDefaultStatus($quote);
        $this->setDefaultProductQuotes($quote);
    }

    private function setDefaultDates(?Quote $quote = null): void
    {
        $this->quoteCreatorService::setDefaultDates(
            $this->quote_issuance_date,
            $this->expiry_date,
            $quote,
        );
    }

    private function setDefaultDiscount(?Quote $quote = null): void
    {
        $quoteExists = !empty($quote) && $quote->hasId();
        $this->discount = $quoteExists ? $quote->getDiscount() : 0.0;
    }

    private function setDefaultStatus(?Quote $quote = null): void
    {
        $quoteExists = !empty($quote) && $quote->hasId();
        $this->status = $quoteExists ? $quote->getStatus() : Quote::STATUS_DRAFT;
    }

    private function setDefaultQuoteNumber(?Quote $quote = null): void
    {
        $this->quote_number = $this->quoteCreatorService->getDefaultQuoteNumber($quote);
    }

    private function setDefaultProductQuotes(?Quote $quote = null): void
    {
        $this->quoteCreatorService::setDefaultProductQuotes(
            $quote,
            $this->lineItems,
        );
    }

    #[PreReRender]
    public function onEachUpdate(): void
    {
        $this->refreshTotal();
    }

    #[PreMount]
    public function preMount(array $data): array
    {
        $this->initializeQuoteData();
        return $data;
    }

    #[PostMount]
    public function postMount()
    {
        $this->initializeQuoteData();
        $this->refreshTotal();
    }

    private function initializeQuoteData(): void
    {
        $this->ensureDefaultCustomer($this->quoteData);
        $this->setDefaultData($this->quoteData);
    }

    #[LiveAction]
    public function addLineItem(): void
    {
        $this->quoteCreatorService->addLineItem($this->lineItems);
    }

    #[LiveAction]
    public function saveQuote(EntityManagerInterface $entityManager, Session $session)
    {
        if (QuoteCreatorService::READONLY_MODE == $this->getMode()) {
            $this->addFlash('warning', 'Vous ne pouvez pas enregistrer un devis en mode lecture seule.');
            return;
        }
        if (!$this->canSaveQuote()) {
            $this->addFlash('warning', 'Veuillez remplir tous les champs obligatoires et ajouter des produits pour enregistrer le devis.');
            return;
        }
        $this->ensureErrorMessageBeforeSaving();
        $this->validate();

        $this->clearFlashBag($session);

        try {
            $this->quoteCreatorService->saveQuote(
                $entityManager,
                $this->quoteData,
                $this->customerId,
                $this->quote_number,
                $this->quote_issuance_date,
                $this->expiry_date,
                $this->discount,
                $this->status,
                $this->lineItems,
            );
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_quote_index');
        }

        return $this->postQuoteSaving();
    }

    #[LiveListener('removeLineItem')]
    public function removeLineItem(#[LiveArg] int $key): void
    {
        $this->quoteCreatorService::removeLineItem($this->lineItems, $key);
    }

    #[LiveListener('line_item:change_edit_mode')]
    public function onLineItemEditModeChange(#[LiveArg] int $key, #[LiveArg] $isEditing): void
    {
        $this->quoteCreatorService::onLineItemEditModeChange($this->lineItems, $key, $isEditing);
    }

    #[LiveListener('line_item:save')]
    public function saveLineItem(
        #[LiveArg] int $key,
        #[LiveArg] Product $product,
        #[LiveArg] float $price,
        #[LiveArg] int $quantity,
        #[LiveArg] float $tva,
        #[LiveArg] float $discount,
        #[LiveArg] float $total,
    ): void
    {
        if (QuoteCreatorService::READONLY_MODE == $this->getMode()) {
            $this->addFlash('warning', 'Vous ne pouvez pas modifier un devis en mode lecture seule.');
            return;
        }
        $this->quoteCreatorService->saveLineItem($this->lineItems, $key, $product, $quantity, $tva, $discount, $price, $total);
    }

    #[ExposeInTemplate('_mode')]
    public function getMode(): string
    {
        return $this->mode;
    }

    #[ExposeInTemplate('_isReadOnlyMode')]
    public function isReadOnlyMode(): bool
    {
        if ('edit' == $this->mode && !$this->canEdit()) {
            return true;
        }
        return ('show' == $this->mode);
    }

    #[ExposeInTemplate('_disableForms')]
    public function disableForms(): bool
    {
        return $this->isReadOnlyMode() || $this->canEdit();
    }

    #[ExposeInTemplate('_canEdit')]
    public function canEdit(): bool
    {
        return ('edit' == $this->mode) && ($this->quoteData->canEdit());
    }

    #[ExposeInTemplate('_showSaveButton')]
    public function showSaveButton(): bool
    {
        if ('edit' == $this->mode) {
            return $this->quoteData->canEdit();
        }

        if ('create' == $this->mode) {
            return true;
        }

        if ('show' == $this->mode) {
            return false;
        }

        return 'false';
    }

    #[ExposeInTemplate]
    public function isEditing(): bool
    {
        return $this->quoteData->hasId();
    }

    #[ExposeInTemplate('_isValid')]
    public function canSaveQuote(): bool
    {
        return $this->quoteCreatorService->canSaveQuote($this->lineItems);
    }

    #[ExposeInTemplate('_hasErrors')]
    public function hasErrors(): bool
    {
        foreach (self::FIELD_TO_VALIDATE as $field) {
            if ($this->getErrors($field)) {
                return true;
            }
        }
        return false;
    }

    #[ExposeInTemplate('_allValid')]
    public function isValid(): bool
    {
        return !$this->hasErrors() && $this->canSaveQuote();
    }

    #[ExposeInTemplate]
    public function productItemsIsEmpty(): bool
    {
        return $this->quoteCreatorService->productItemsIsEmpty($this->lineItems);
    }

    #[ExposeInTemplate('_hasDiscountValue')]
    public function hasDiscountValue(): bool
    {
        return $this->discount > 0;
    }

    #[ExposeInTemplate('_canAddProductItem')]
    public function canAddProductItem(): bool
    {
        if ('create' == $this->mode) {
            return true;
        }

        if ('edit' == $this->mode) {
            return $this->quoteData->canEdit();
        }

        if ('show' == $this->mode) {
            return false;
        }

        return false;
    }

    public function _getTotal(): float
    {
        $total = 0.0;
        foreach ($this->lineItems as $lineItem) {
            if ($lineItem['isEditing']) {
                continue;
            }
            $price = $lineItem['price'];
            $quantity = $lineItem['quantity'];
            $tva = $lineItem['tva'];
            $discount = $lineItem['discount'];

            $totalHT = $price * $quantity;
            $totalTva = $totalHT * ($tva / 100);
            $totalDiscount = $totalHT * ($discount / 100);

            $total += $totalHT + $totalTva - $totalDiscount;
        }
        return $total;
    }

    private function refreshTotal(): void
    {
        $this->total = $this->_getTotal();
        $this->totalDiscount = $this->total * ($this->discount / 100);
        $this->totalWithDiscount = $this->total - $this->totalDiscount;
    }
}
