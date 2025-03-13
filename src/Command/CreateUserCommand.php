<?php

namespace App\Command;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\{
    Attribute\AsCommand,
    Command\Command,
    Input\InputInterface,
    Output\OutputInterface,
    Style\SymfonyStyle,
};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', null, 'User email')
            ->addArgument('password', null, 'User password');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $this->checkUserExistence($io, $email);

        $user = new User();
        $user->setEmail($input->getArgument('email'));
        $user->setPassword($this->passwordHasher->hashPassword($user, $input->getArgument('password')));
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setUpdatedAt(new DateTimeImmutable());

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $io->error((string) $errors);
            return Command::FAILURE;
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('User successfully created!');

        return Command::SUCCESS;
    }

    private function checkUserExistence(SymfonyStyle $io, string $email): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        $io->info("User $email already created!");
        exit(Command::FAILURE);
    }
}
