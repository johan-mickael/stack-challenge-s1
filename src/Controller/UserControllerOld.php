<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EmployeeType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;


class UserControllerOld extends AbstractController
{
    #[Route('/add_user', name: 'add_user')]
    public function addUser(Request $request, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $company = $security->getUser()->getCompany();

        $form = $this->createForm(EmployeeType::class, $user, ['company' => $company]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('add_user'); 
        }

        return $this->render('user/employee/add.html.twig', [
            'form' => $form,
        ]);
    }
}
