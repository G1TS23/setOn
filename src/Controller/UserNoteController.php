<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Note;
use App\Enums\InvitationStatusEnum;
use App\Repository\UserRepository;
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

/**
 * Controller for managing user notes operations.
 *
 * This controller handles all note-related operations including:
 * - Viewing notes
 * - Creating new notes
 * - Duplicating notes
 * - Deleting notes
 * - Autosaving notes
 * - Sharing notes with other users
 */
#[Route('/note')]
final class UserNoteController extends AbstractController
{
    /**
     * Displays a specific note.
     *
     * @param Note $note The note to display
     * @param Security $security The security service for user authentication
     * @return Response The rendered note view
     *
     * @throws AccessDeniedException If the user doesn't have permission to view the note
     */
    #[Route('/{id<\d+>}', name: 'user_note', methods: ['GET'])]
    public function show(Note $note, Security $security): Response
    {
        $noteUrl = '/notes/' . $note->getId();
        $userNotesUrl = '/user/' . $note->getOwner()->getId() . '/notes/';
        $user = $security->getUser();
        $pendingRequests = $user->getRequests()->filter(function (Invitation $invitation) {
            return $invitation->getStatus() === InvitationStatusEnum::PENDING;
        });
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
            'pendingRequests' => $pendingRequests,
        ]);
    }

    /**
     * Creates a new empty note for the current user.
     *
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @return Response A redirect to the newly created note
     */
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

    /**
     * Creates a duplicate of an existing note.
     *
     * @param Note $note The note to duplicate
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @return Response A redirect to the newly duplicated note
     *
     * @throws AccessDeniedException If the user doesn't have permission to duplicate the note
     */
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

    /**
     * Deletes a note.
     *
     * @param Request $request The current request
     * @param Note $note The note to delete
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @return Response A redirect to the home page
     *
     * @throws AccessDeniedException If the user doesn't have permission to delete the note
     */
    #[Route('/{id}', name: 'user_note_delete', methods: ['POST'])]
    public function delete(Request $request, Note $note, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser();
        if ($note->getOwner() !== $user) {
            throw $this->createAccessDeniedException('You do not have permission to delete this note.');
        }
        if ($this->isCsrfTokenValid('delete' . $note->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($note);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Handles autosave functionality for notes.
     *
     * Processes AJAX requests to save note changes automatically and broadcasts updates
     * via Mercure to all connected clients.
     *
     * @param Request $request The current request
     * @param Note $note The note being edited
     * @param EntityManagerInterface $entityManager The entity manager
     * @param MessageBusInterface $bus The message bus for broadcasting updates
     * @param Security $security The security service
     * @return JsonResponse The status of the autosave operation
     *
     * @throws AccessDeniedException If the user doesn't have permission to edit the note
     */
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

    /**
     * Shares a note with another user by creating an invitation.
     *
     * @param Note $note The note to share
     * @param EntityManagerInterface $entityManager The entity manager
     * @param Security $security The security service
     * @param Request $request The current request
     * @param UserRepository $userRepository The user repository
     * @return JsonResponse The status of the share operation
     *
     * @throws AccessDeniedException If the user doesn't have permission to share the note
     */
    #[Route('/{id}/share', name: 'user_note_invite', methods: ['POST'])]
    public function share(Note $note, EntityManagerInterface $entityManager, Security $security, Request $request, UserRepository $userRepository): JsonResponse
    {
        $sender = $security->getUser();

        if ($request->request->has('userEmail') !== null && $userRepository->findOneByEmail($request->request->get('userEmail')) !== null) {
            $receiver = $userRepository->findOneByEmail($request->request->get('userEmail'));
            if ($note->getOwner() !== $sender && !$note->getEditors()->contains($receiver)) {
                return new JsonResponse(['error' => 'You do not have permission to share this note.'], Response::HTTP_FORBIDDEN);
            }
            if ($request->request->get('message') !== null) {
                $description = $request->request->get('message');
            }
            $invitation = new Invitation();
            $invitation->setSender($sender);
            $invitation->setReceiver($receiver);
            $invitation->setNote($note);
            $invitation->setStatus(InvitationStatusEnum::PENDING);
            $invitation->setCreatedAt(new \DateTimeImmutable());
            $invitation->setUpdatedAt(new \DateTimeImmutable());
            if (isset($description)) {
                $invitation->setDescription($description);
            } else {
                $invitation->setDescription('Invitation to collaborate on note: ' . $note->getTitle());
            }
            $entityManager->persist($invitation);
            $entityManager->flush();
        } else {
            return new JsonResponse(['error' => 'Invalid user email.' . $request->request->get('userEmail')], Response::HTTP_BAD_REQUEST);
        }
        // Logic to share the note (e.g., send an invitation)
        // This is a placeholder for actual sharing logic
        // You can implement your own sharing logic here

        return new JsonResponse(['success' => true, 'message' => 'User invited successfully.']);
    }
}
