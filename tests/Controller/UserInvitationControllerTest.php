<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class UserInvitationControllerTest extends WebTestCase
{
    public function testIndex(): void
    {

        $client = UserInvitationControllerTest::createClient();

        // Récupérer l'EntityManager
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        if($user = $entityManager->getRepository(User::class)->findOneByEmail('test@example.com')) {
            $entityManager->remove($user);
            $entityManager->flush();
        }
        // Créer ou récupérer un utilisateur (mock ou fixture)
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('dummy'); // le mot de passe est ignoré ici
        $user->setName('Test User');

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/invitation');

        self::assertResponseIsSuccessful();
    }
}
