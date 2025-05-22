<?php

namespace App\Controller;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/note')]
final class UserNoteController extends AbstractController
{
    #[Route('/{id<\d+>}', name: 'user_note', methods: ['GET'])]
    public function show(Note $note, Security $security): Response
    {
        $noteUrl = '/notes/' . $note->getId();
        $userNotesUrl = '/user/' . $note->getOwner()->getId() . '/notes/';
        $user = $security->getUser();
        if ($note->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to view this note.');
        }
        $notes = $user->getNotes();
        return $this->render('user_note/index.html.twig', [
            'notes' => $notes,
            'note' => $note,
            'noteUrl' => $noteUrl,
            'userNotesUrl' => $userNotesUrl,
            'user' => $user,
        ]);
    }

    #[Route('/new', name: 'user_note_add', methods: ['GET', 'POST'])]
    public function add(EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        $note = new Note();
        $note->setTitle('');
        $note->setContent('');
        $note->setOwner($user);
        $note->setCreatedAt(new \DateTimeImmutable());
        $note->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($note);
        $entityManager->flush();
        return $this->redirectToRoute('user_note', ['id' => $note->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/duplicate/{id}', name: 'user_note_duplicate', methods: ['GET', 'POST'])]
    public function duplicate(Note $note, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if ($note->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to duplicate this note.');
        }
        $duplicateNote = new Note();
        $duplicateNote->setTitle($note->getTitle());
        $duplicateNote->setContent($note->getContent());
        $duplicateNote->setOwner($user);
        $duplicateNote->setCreatedAt(new \DateTimeImmutable());
        $duplicateNote->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($duplicateNote);
        $entityManager->flush();
        return $this->redirectToRoute('user_note', ['id' => $duplicateNote->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'user_note_delete', methods: ['POST'])]
    public function delete(Request $request, Note $note, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if ($note->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to delete this note.');
        }
        if ($this->isCsrfTokenValid('delete'.$note->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($note);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }
}
