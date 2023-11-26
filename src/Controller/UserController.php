<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AddUserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;


class UserController extends AbstractController
{
    #[Route('/add_user', name: 'add_user')]
    public function addUser(Request $request, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        // Récupérer l'entreprise de l'utilisateur connecté
        $entreprise = $security->getUser()->getEntreprise();

        echo($entreprise);

        $form = $this->createForm(AddUserFormType::class, $user, ['entreprise' => $entreprise]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('add_user'); 
        }

        return $this->render('user/add.html.twig', [
            'form' => $form,
        ]);
    }
}
