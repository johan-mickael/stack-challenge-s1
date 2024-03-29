<?php

namespace App\Controller\Quote;

use App\Entity\Bill;
use App\Entity\Quote;
use App\Form\QuoteType;
use App\Entity\ProductBill;
use App\Service\PdfService;
use App\Repository\BillRepository;
use App\Repository\QuoteRepository;
use App\Controller\MailerController;
use App\Service\Bill\BillCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Quote\QuotePdfController;
use App\Service\Request\RequestQueryService;
use App\Service\Quote\AccessibleQuoteService;
use Symfony\Component\HttpFoundation\Request;
use App\Twig\Helper\Paginator\PaginatorHelper;
use Symfony\Component\HttpFoundation\Response;
use App\Service\Request\PageFromRequestService;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/quote', name: 'app_quote_')]
class QuoteController extends AbstractController
{
    private ?string $searchTerm;
    private ?int $page;

    public const SEARCH_FORM_NAME = 'search';

    const PAGE_PARAM_NAME = 'page';
    const LIMIT = 10;

    public function __construct(
        RequestQueryService $requestQueryService,
        PageFromRequestService $pageFromRequestService,
        private readonly BillCreatorService $billCreatorService,
        PdfService $pdfService, 
        QuotePdfController $quotePdfController,
        MailerController $mailer
    ) {
        $this->searchTerm = $requestQueryService->get(self::SEARCH_FORM_NAME);
        $this->page = $pageFromRequestService->get(self::PAGE_PARAM_NAME);
        $this->pdfService = $pdfService;
        $this->quotePdfController = $quotePdfController;
        $this->mailer = $mailer;
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    #[IsGranted('view')]
    public function index(AccessibleQuoteService $accessibleQuoteService): Response
    {
        $quotes = $accessibleQuoteService->findAllOverPages($this->page, self::LIMIT);
        $paginatorHelper = new PaginatorHelper($this->page, count($quotes), self::LIMIT);

        
        return $this->render('quote/index.html.twig', [
            'quotes' => $quotes,
            'paginatorHelper' => $paginatorHelper,
        ]);
        
    }
    private function search(string $searchTerm, QuoteRepository $quoteRepository): Response
    {
        $quotes =  $quoteRepository->search($searchTerm, $this->page, self::LIMIT);
        $paginatorHelper = new PaginatorHelper($this->page, count($quotes), self::LIMIT);

        return $this->render('quote/index.html.twig', [
            'searchTerm' => $searchTerm,
            'quotes' => $quotes,
            'paginatorHelper' => $paginatorHelper,
        ]);
    }

    #[Route('/{id}', name: 'to_bill', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function transformQuoteToBill(Request $request, $id,BillRepository $billRepository, QuoteRepository $quoteRepository,EntityManagerInterface $entityManager): Response
    {
        $quote = $quoteRepository->find($id);
        $existingBill = $billRepository->findOneBy(['quote' => $quote]);

        if (!$quote) {
            throw $this->createNotFoundException('Devis non trouvé');
        }

        $expiryDate = $quote->getExpiryDate();
        $currentDate = new \DateTime();

        if ($expiryDate < $currentDate) {
            $this->addFlash('warning', 'La date d\'expiration de ce devis est dépassée.');
            return $this->redirectToRoute('app_quote_index');
        }else{
            if ($existingBill) {
                $this->addFlash('warning', 'Ce devis a déjà été transformé en facture.'); // Si un enregistrement existe déjà, afficher un message et rediriger
                return $this->redirectToRoute('app_quote_index'); // Rediriger vers la page des devis par exemple
            }else{ //Créer une nouvelle facture
                
                $bill = new Bill();

                $user = $this->getUser();

                $bill->setBillNumber($this->billCreatorService->getDefaultBillNumber());
                $bill->setCustomer($quote->getCustomer());
                $bill->setDiscount($quote->getDiscount());
                $bill->setBillIssuanceDate(new \DateTime());
                $bill->setStatus(Bill::STATUS_DRAFT);
                $bill->setQuote($quote);

                foreach ($quote->getProductQuotes() as $productQuote) {
                    $productBill = new ProductBill();
                    $productBill->setProduct($productQuote->getProduct());
                    $productBill->setPrice($productQuote->getPrice());
                    $productBill->setQuantity($productQuote->getQuantity());
                    $productBill->setTva($productQuote->getTva());
                    $productBill->setDiscount($productQuote->getDiscount());
                    $productBill->setBill($bill); // Associer le produit à la nouvelle facture
                    $bill->addProductBill($productBill); // Ajouter le produit à la collection de produits de la facture
                }

                // Enregistrer la facture
                $entityManager->persist($bill);
                $entityManager->flush();

                // Rediriger vers la page de la nouvelle facture
                $this->addFlash('success', 'Le devis a été transformé en facture.');

                return $this->redirectToRoute('app_bill_show', ['id' => $bill->getId()]);
            }
        }


    }
    
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('add')]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerController $mailer, PdfService $pdfService): Response
    {
        $user = $this->getUser();
        $company = $user->getCompany();
        $quote = new Quote();

        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($quote);
            $entityManager->flush();

            $this->addFlash('success', 'Votre devis a été créé avec succès.');

            
            $formData = $form->getData();
            $customer = $formData->getCustomer();

            $mailer->newQuoteCreateEmail($customer, $quote, $pdfService, $this);
        
            return $this->redirectToRoute('app_quote_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quote/new.html.twig', [
            'quote' => $quote,
            'company' => $company, 
            'form' => $form,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('edit', 'quote')]
    public function edit(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        $originalStatus = $quote->getStatus();
        $form = $this->createForm(QuoteType::class, $quote);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $data = $form->getData();
            $updatedStatus = $data->getStatus();
            if($originalStatus === Quote::STATUS_DRAFT && ($updatedStatus != Quote::STATUS_DRAFT || $updatedStatus != Quote::STATUS_REFUSED)){
                $this->mailer->newQuoteCreateEmail($data->getCustomer(), $data, $this->pdfService, $this->quotePdfController);
            }
    
            return $this->redirectToRoute('app_quote_index', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('quote/edit.html.twig', [
            'quote' => $quote,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete', name: 'deleteAll', methods: ['POST'])]
    #[IsGranted('add')]
    public function deleteMany(Request $request, QuoteRepository $quoteRepository, EntityManagerInterface $entityManager): Response
    {
        $quotes = $request->request->all()['quotes'];
        $count = 0;
        foreach($quotes as $id => $token){
            $quote = $quoteRepository->find($id);
            if($quote && $this->isCsrfTokenValid('delete'.$id, $token)){
                $entityManager->remove($quote);
                $count++;
            }
        }
        $this->addFlash('success', $count.' devis supprimé(s) avec succès.');
        $entityManager->flush();
        return $this->redirectToRoute('app_quote_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/show', name: 'show', methods: ['GET'])]
    #[IsGranted('read', 'quote')]
    public function show(Quote $quote): Response
    {
        return $this->render('quote/show.html.twig', [
            'quote' => $quote,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    #[IsGranted('delete', 'quote')]
    public function delete(Request $request, Quote $quote, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$quote->getId(), $request->request->get('_token'))) {
            $entityManager->remove($quote);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_quote_index', [], Response::HTTP_SEE_OTHER);
    }
}
