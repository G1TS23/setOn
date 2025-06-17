<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Enums\InvitationStatusEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Home controller handling the main dashboard page of the application.
 *
 * This controller is responsible for:
 * - Displaying the user's dashboard
 * - Showing pending invitations
 * - Managing the user's initial navigation
 * - Providing access to the user's first note
 */
final class HomeController extends AbstractController
{
    /**
     * Renders the home page dashboard for authenticated users.
     *
     * This method:
     * - Retrieves the current authenticated user
     * - Filters pending invitations for the user
     * - Gets the user's first note for quick access
     * - Renders the dashboard with relevant user information
     *
     * @param Security $security The security service for user authentication
     * @return Response The rendered homepage view
     *
     * @throws \LogicException If no user is authenticated
     * @throws \RuntimeException If the user has no notes (first note access)
     */
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
