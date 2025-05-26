<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Enums\InvitationStatusEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route(['/home'], name: 'app_home')]
    public function index(Security $security): Response
    {
        $user = $security->getUser();

        $pendingRequests = $user->getRequests()->filter(function (Invitation $invitation) {
            return $invitation->getStatus() === InvitationStatusEnum::PENDING;
        });

        $firstNote = $user->getNotes()->first();
        $noteUrl = '/notes/' . $firstNote->getId();
        return $this->render('home/home.html.twig', [
            'controller_name' => 'HomeController',
            'noteUrl' => $noteUrl,
            'user' => $user,
            'pendingRequests' => $pendingRequests
        ]);
    }
}
