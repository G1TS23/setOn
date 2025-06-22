<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Enums\InvitationStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for managing user invitations.
 *
 * This controller handles all invitation-related operations for regular users including:
 * - Viewing invitations
 * - Creating new invitations
 * - Accepting/declining invitations
 * - Managing invitation status
 * - Deleting invitations
 */
#[Route('/invitation')]
final class UserInvitationController extends AbstractController
{
    /**
     * Displays all invitations for the current user.
     *
     * Lists all invitations with a focus on pending requests.
     *
     * @param Security $security The security service for user authentication
     * @return Response The rendered invitation index view
     */
    #[Route('', name: 'app_user_invitation')]
    public function index( Security $security): Response
    {
        $user = $security->getUser();
        $pendingRequests = $user->getRequests()->filter(function (Invitation $invitation) {
            return $invitation->getStatus() === InvitationStatusEnum::PENDING;
        });
        $noteId = "0";
        $firstNote = $user->getNotes()->first();
        if ($firstNote) {
            $noteId = $firstNote->getId();
        }
        $noteUrl = '/notes/' . $noteId;
        return $this->render('user_invitation/index.html.twig', [
            'noteUrl' => $noteUrl,
            'user' => $user,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    /**
     * Displays the form to create a new invitation.
     *
     * @param Security $security The security service
     * @return Response The rendered new invitation form
     */
    #[Route('/new', name: 'app_user_invitation_new')]
    public function new(Security $security): Response
    {
        $user = $security->getUser();
        $noteId = "0";
        $firstNote = $user->getNotes()->first();
        if ($firstNote) {
            $noteId = $firstNote->getId();
        }
        $noteUrl = '/notes/' . $noteId;
        return $this->render('user_invitation/new.html.twig', [
            'noteUrl' => $noteUrl,
            'user' => $user
        ]);
    }

    /**
     * Displays the form to edit an existing invitation.
     *
     * @param Security $security The security service
     * @return Response The rendered edit invitation form
     */
    #[Route('/{id}/edit', name: 'app_user_invitation_edit')]
    public function edit(Security $security): Response
    {
        $user = $security->getUser();
        $noteId = "0";
        $firstNote = $user->getNotes()->first();
        if ($firstNote) {
            $noteId = $firstNote->getId();
        }
        $noteUrl = '/notes/' . $noteId;
        return $this->render('user_invitation/edit.html.twig', [
            'noteUrl' => $noteUrl,
            'user' => $user
        ]);
    }

    /**
     * Handles the acceptance of an invitation.
     *
     * This method:
     * - Verifies the user is the intended recipient
     * - Adds the user as an editor to the shared note
     * - Updates the invitation status to APPROVED
     *
     * @param Invitation $invitation The invitation to accept
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @return Response A redirect to the invitation index
     *
     * @throws AccessDeniedException If the user is not the intended recipient
     * @throws NotFoundHttpException If the associated note doesn't exist
     */
    #[Route('/{id}/accept', name: 'app_user_invitation_accept')]
    public function accept(Invitation $invitation, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if ($invitation->getReceiver() !== $user) {
            throw $this->createAccessDeniedException('Something went wrong');
        }
        $note = $invitation->getNote();
        if (!$note) {
            throw $this->createNotFoundException('Something went wrong');
        }
        $note->addEditor($invitation->getReceiver());
        $invitation->setStatus(InvitationStatusEnum::APPROVED);
        $entityManager->persist($note);
        $entityManager->persist($invitation);
        $entityManager->flush();
        return $this->redirectToRoute('app_user_invitation');
    }

    /**
     * Handles the decline of an invitation.
     *
     * This method:
     * - Verifies the user is the intended recipient
     * - Updates the invitation status to REJECTED
     *
     * @param Invitation $invitation The invitation to decline
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @return Response A redirect to the invitation index
     *
     * @throws AccessDeniedException If the user is not the intended recipient
     */
    #[Route('/{id}/decline', name: 'app_user_invitation_decline')]
    public function decline(Invitation $invitation, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if ($invitation->getReceiver() !== $user) {
            throw $this->createAccessDeniedException('Something went wrong');
        }
        $invitation->setStatus(InvitationStatusEnum::REJECTED);
        $entityManager->persist($invitation);
        $entityManager->flush();
        return $this->redirectToRoute('app_user_invitation');
    }

    /**
     * Handles the deletion of an invitation.
     *
     * This method:
     * - Verifies the user is the sender of the invitation
     * - Validates the CSRF token
     * - Removes the invitation from the database
     *
     * @param Request $request The current request
     * @param Invitation $invitation The invitation to delete
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @return Response A redirect to the invitation index
     *
     * @throws AccessDeniedException If the user is not the sender of the invitation
     */
    #[Route('/{id}', name: 'user_invitation_delete', methods: ['POST'])]
    public function delete(Request $request, Invitation $invitation, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if ($invitation->getSender() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to delete this note.');
        }
        if ($this->isCsrfTokenValid('delete' . $invitation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($invitation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_invitation', [], Response::HTTP_SEE_OTHER);
    }

}
