<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Enums\InvitationStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invitation')]
final class UserInvitationController extends AbstractController
{
    #[Route('', name: 'app_user_invitation')]
    public function index( Security $security): Response
    {
        $user = $security->getUser();
        $pendingRequests = $user->getRequests()->filter(function (Invitation $invitation) {
            return $invitation->getStatus() === InvitationStatusEnum::PENDING;
        });
        $firstNote = $user->getNotes()->first();
        $noteUrl = '/notes/' . $firstNote->getId();
        return $this->render('user_invitation/index.html.twig', [
            'noteUrl' => $noteUrl,
            'user' => $user,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    #[Route('/new', name: 'app_user_invitation_new')]
    public function new(Security $security): Response
    {
        $user = $security->getUser();
        $firstNote = $user->getNotes()->first();
        $noteUrl = '/notes/' . $firstNote->getId();
        return $this->render('user_invitation/new.html.twig', [
            'noteUrl' => $noteUrl,
            'user' => $user
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_invitation_edit')]
    public function edit(Security $security): Response
    {
        $user = $security->getUser();
        $firstNote = $user->getNotes()->first();
        $noteUrl = '/notes/' . $firstNote->getId();
        return $this->render('user_invitation/edit.html.twig', [
            'noteUrl' => $noteUrl,
            'user' => $user
        ]);
    }

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
}
