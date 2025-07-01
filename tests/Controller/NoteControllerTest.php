<?php

namespace App\Tests\Controller;

use App\Entity\Note;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class NoteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $noteRepository;
    private string $path = '/admin/note/';
    private User $user;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->noteRepository = $this->manager->getRepository(Note::class);

        foreach ($this->noteRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $existingUser = $this->manager->getRepository(User::class)->findOneByEmail('test@example.com');
        if($existingUser) {
            $this->manager->remove($existingUser);;
            $this->manager->flush();
        }
        // Créer ou récupérer un utilisateur (mock ou fixture)

        $this->user = new User();
        $this->user->setEmail('test@example.com');
        $this->user->setPassword('dummy'); // le mot de passe est ignoré ici
        $this->user->setName('Test User');
        $this->user->setRoles(['ROLE_ADMIN']);

        $this->manager->persist($this->user);

        $this->manager->flush();
        $this->client->loginUser($this->user);

    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Note index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        // $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'note[title]' => 'Testing',
            'note[content]' => 'Testing',
            'note[owner]' => $this->user->getId(),
        ]);

        self::assertResponseRedirects('/admin/note');

        self::assertSame(1, $this->noteRepository->count([]));
    }

    public function testShow(): void
    {
        // $this->markTestIncomplete();
        $fixture = new Note();
        $fixture->setTitle('My Title');
        $fixture->setContent('My Title');
        $fixture->setCreatedAt(new \DateTimeImmutable());
        $fixture->setUpdatedAt(new \DateTimeImmutable());
        $fixture->setOwner($this->user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Note');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        // $this->markTestIncomplete();
        $fixture = new Note();
        $fixture->setTitle('Value');
        $fixture->setContent('Value');
        $createDate = new \DateTimeImmutable();
        $fixture->setCreatedAt($createDate);
        $fixture->setUpdatedAt(new \DateTimeImmutable());
        $fixture->setOwner($this->user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));


        $this->client->submitForm('Update', [
            'note[title]' => 'Something New',
            'note[content]' => 'Something New',
        ]);

        self::assertResponseRedirects('/admin/note');

        $fixture = $this->noteRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitle());
        self::assertSame('Something New', $fixture[0]->getContent());
        self::assertEquals(
            $createDate->format('Y-m-d H:i:s'),
            $fixture[0]->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertSame($this->user->getId(), $fixture[0]->getOwner()->getId());
    }

    public function testRemove(): void
    {
        // $this->markTestIncomplete();
        $fixture = new Note();
        $fixture->setTitle('Value');
        $fixture->setContent('Value');
        $fixture->setCreatedAt(new \DateTimeImmutable());
        $fixture->setUpdatedAt(new \DateTimeImmutable());
        $fixture->setOwner($this->user);

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/admin/note');
        self::assertSame(0, $this->noteRepository->count([]));
    }
}
