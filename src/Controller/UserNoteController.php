<?php

namespace App\Controller;

use App\Entity\Note;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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
        if ($note->getOwner() !== $user && !$note->getEditors()->contains($user)) {
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

    #[Route('/{id}/autosave', name: 'app_note_autosave', methods: ['POST'])]
    public function autosave(Request $request, Note $note, EntityManagerInterface $entityManager, MessageBusInterface $bus, Security $security): JsonResponse
    {
        $user = $security->getUser();
        if ($note->getOwner() !== $user && !$note->getEditors()->contains($user)) {
            return new JsonResponse(['error' => 'You do not have permission to edit this note.'], Response::HTTP_FORBIDDEN);
        }
        $data = json_decode($request->getContent(), true);

        if (isset($data['field'], $data['value'])) {
            $field = $data['field'];
            $value = $data['value'];

            if ($field === 'title') {
                $note->setTitle($value);
            } elseif ($field === 'content') {
                // Décoder les retours à la ligne encodés
                $note->setContent(str_replace('\\n', "\n", $value));
            } else {
                return new JsonResponse(['error' => 'Invalid field'], Response::HTTP_BAD_REQUEST);
            }

            $note->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $noteUrl = '/notes/' . $note->getId();
            $userNotesUrl = '/user/' . $note->getOwner()->getId() . '/notes/';

            $updateNote = new Update($noteUrl, json_encode([
                'id' => $note->getId(),
                'content' => $note->getContent(),
                'title' => $note->getTitle()
            ]));
            $updateUserNotes = new Update($userNotesUrl, json_encode([
                ['id' => $note->getId(), 'title' => $note->getTitle()],
                ['id' => $note->getId(), 'title' => $note->getTitle()]
            ]));

            try {
                $bus->dispatch($updateNote);
                $bus->dispatch($updateUserNotes);
            } catch (ExceptionInterface $e) {
                return new JsonResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
            }

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
    }
}
