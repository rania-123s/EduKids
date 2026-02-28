<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create or promote an admin user',
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Admin email')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password (leave empty to type it interactively)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $email = trim((string) $input->getArgument('email'));
        $password = (string) ($input->getArgument('password') ?? '');

        if ($email === '') {
            $output->writeln('<error>Email cannot be empty.</error>');
            return Command::FAILURE;
        }

        if ($password === '') {
            $question = new Question('Admin password: ');
            $question->setHidden(true);
            $question->setHiddenFallback(false);

            $password = (string) $helper->ask($input, $output, $question);

            if ($password === '') {
                $output->writeln('<error>Password cannot be empty.</error>');
                return Command::FAILURE;
            }
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if ($user instanceof User) {
            $output->writeln(sprintf('<comment>User "%s" already exists, updating roles and password…</comment>', $email));
        } else {
            $user = new User();
            $user->setEmail($email);
            $output->writeln(sprintf('<info>Creating new admin user "%s"…</info>', $email));
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Ensure ROLE_ADMIN (and ROLE_USER via getRoles()) is set
        $roles = $user->getRoles();
        if (!in_array('ROLE_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_ADMIN';
        }
        $user->setRoles($roles);

        // Make sure the account is active
        $user->setIsActive(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Admin user saved successfully.</info>');

        return Command::SUCCESS;
    }
}

