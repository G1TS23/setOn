<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route(['/', '/home'], name: 'app_home')]
    public function index(Security $security): Response
    {
        $user = $security->getUser();
        $firstNote = $user->getNotes()->first();
        $noteUrl = '/notes/' . $firstNote->getId();
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'noteUrl' => $noteUrl,
            'user' => $user
        ]);
    }
}
