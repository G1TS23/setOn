<?php

namespace App\Tests\Controller;

use App\Entity\Note;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


final class UserNoteControllerTest extends WebTestCase
{

    private $client;
    private $entityManager;
    private $userRepository;
    private $noteRepository;
    private $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->noteRepository = $this->entityManager->getRepository(Note::class);

        // Créer un utilisateur de test
        $this->testUser = new User();
        $this->testUser->setEmail('test@example.com');
        $this->testUser->setName('Test User');
        $this->testUser->setPassword('hashedPassword123');
        $this->entityManager->persist($this->testUser);
        $this->entityManager->flush();
    }

    public function testShowNote(): void
    {
        // Créer une note de test
        $note = new Note();
        $note->setTitle('Test Note');
        $note->setContent('Test Content');
        $note->setOwner($this->testUser);
        $note->setCreatedAt(new \DateTimeImmutable());
        $note->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        // Authentifier l'utilisateur
        $this->client->loginUser($this->testUser);

        // Tester l'accès à la note
        $this->client->request('GET', '/note/' . $note->getId());
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Test Note');
    }

    public function testCreateNewNote(): void
    {
        $this->client->loginUser($this->testUser);

        $this->client->request('GET', '/note/new');
        $this->assertResponseIsSuccessful();

        // Vérifier que la note a été créée
        $newNote = $this->noteRepository->findOneBy(['owner' => $this->testUser]);
        $this->assertNotNull($newNote);
        $this->assertEmpty($newNote->getTitle());
    }

    public function testAutosaveNote(): void
    {
        $note = new Note();
        $note->setTitle('Original Title');
        $note->setContent('Original Content');
        $note->setOwner($this->testUser);
        $note->setCreatedAt(new \DateTimeImmutable());
        $note->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($note);
        $this->entityManager->flush();

        $this->client->loginUser($this->testUser);

        // Test autosave
        $this->client->request('POST', '/note/' . $note->getId() . '/autosave', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'field' => 'content',
            'value' => 'Updated Content'
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertJson($this->client->getResponse()->getContent());

        // Vérifier que le contenu a été mis à jour
        $updatedNote = $this->noteRepository->find($note->getId());
        $this->assertEquals('Updated Content', $updatedNote->getContent());
    }

    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/user/note');

        self::assertResponseIsSuccessful();
    }
}
