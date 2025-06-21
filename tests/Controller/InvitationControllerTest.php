<?php

namespace App\Tests\Controller;

use App\Entity\Invitation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InvitationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $invitationRepository;
    private string $path = '/invitation/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->invitationRepository = $this->manager->getRepository(Invitation::class);

        foreach ($this->invitationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Invitation index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'invitation[description]' => 'Testing',
            'invitation[status]' => 'Testing',
            'invitation[createdAt]' => 'Testing',
            'invitation[updatedAt]' => 'Testing',
            'invitation[sender]' => 'Testing',
            'invitation[receiver]' => 'Testing',
            'invitation[note]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->invitationRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Invitation();
        $fixture->setDescription('My Title');
        $fixture->setStatus('My Title');
        $fixture->setCreatedAt('My Title');
        $fixture->setUpdatedAt('My Title');
        $fixture->setSender('My Title');
        $fixture->setReceiver('My Title');
        $fixture->setNote('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Invitation');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Invitation();
        $fixture->setDescription('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setSender('Value');
        $fixture->setReceiver('Value');
        $fixture->setNote('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'invitation[description]' => 'Something New',
            'invitation[status]' => 'Something New',
            'invitation[createdAt]' => 'Something New',
            'invitation[updatedAt]' => 'Something New',
            'invitation[sender]' => 'Something New',
            'invitation[receiver]' => 'Something New',
            'invitation[note]' => 'Something New',
        ]);

        self::assertResponseRedirects('/invitation/');

        $fixture = $this->invitationRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getDescription());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getCreatedAt());
        self::assertSame('Something New', $fixture[0]->getUpdatedAt());
        self::assertSame('Something New', $fixture[0]->getSender());
        self::assertSame('Something New', $fixture[0]->getReceiver());
        self::assertSame('Something New', $fixture[0]->getNote());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Invitation();
        $fixture->setDescription('Value');
        $fixture->setStatus('Value');
        $fixture->setCreatedAt('Value');
        $fixture->setUpdatedAt('Value');
        $fixture->setSender('Value');
        $fixture->setReceiver('Value');
        $fixture->setNote('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/invitation/');
        self::assertSame(0, $this->invitationRepository->count([]));
    }
}
