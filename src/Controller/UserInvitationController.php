<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invitation')]
final class UserInvitationController extends AbstractController
{
    #[Route('', name: 'app_user_invitation')]
    public function index(): Response
    {
        return $this->render('user_invitation/index.html.twig', [
            'controller_name' => 'UserInvitationController',
        ]);
    }
}
