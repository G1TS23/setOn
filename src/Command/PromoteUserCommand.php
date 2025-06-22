<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(name: 'app:promote-user')]
class PromoteUserCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Promote a user to admin.')
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the user to promote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');

// Récupérer l'utilisateur depuis la base de données
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $output->writeln('<error>Utilisateur non trouvé.</error>');
            return Command::FAILURE;
        }

// Ajouter le rôle ROLE_ADMIN
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'L\'utilisateur est déjà administrateur. Voulez-vous lui retirer ce rôle ? (y/n) ',
                false
            );

            if ($helper->ask($input, $output, $question)) {
                $user->removeRole('ROLE_ADMIN');
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $output->writeln('<info>Le rôle administrateur a été retiré.</info>');
                return Command::SUCCESS;
            }

            $output->writeln('<comment>Opération annulée.</comment>');
            return Command::SUCCESS;

        }
        $user->addRole('ROLE_ADMIN');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>L\'utilisateur a été promu administrateur.</info>');

        return Command::SUCCESS;
    }
}

