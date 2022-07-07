<?php

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class DemoteUserCommand extends Command
{
    protected static $defaultName = 'myddleware:demote-user';

    protected static $defaultDescription = 'Demote an Myddleware existing user by removing a role';

    private UserRepository $userRepository;

    private SymfonyStyle $io;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
        ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    protected function configure()
    {
        $this
        ->addArgument('email', InputArgument::REQUIRED, 'The email')
        ->addArgument('role', InputArgument::REQUIRED, 'The new role')
        ->setHelp(implode("\n", [
            'The <info>%command.name%</info> command removes a role from a user:',
            '<info>php %command.full_name%</info>  <comment>janedoe@email.com </comment>',
            'This interactive shell will first ask you for a role.',
            'You can alternatively specify the role as a second argument:',
            '<info>php %command.full_name%</info><comment> janedoe@email.com ROLE_ADMIN</comment>',
        ]));
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = [];
        $this->io->title('Demote Myddleware User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as in the example below:',
            '',
            ' $ php bin/console myddleware:demote-user email@example.com ROLE_SUPER_ADMIN',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);
        if (!$input->getArgument('email')) {
            $question = new Question('Please give the email:');
            $question->setValidator(function ($email) {
                if (empty($email)) {
                    throw new \Exception('email cannot be empty');
                }

                if (!$this->userRepository->findOneByEmail($email)) {
                    throw new \Exception('No user found with this email');
                }

                return $email;
            });
            $questions['email'] = $question;
        }

        if (!$input->getArgument('role')) {
            $question = new Question('Please enter the role to remove:');
            $question->setValidator(function ($role) {
                if (empty($role)) {
                    throw new \Exception('role cannot be empty');
                }

                return $role;
            });
            $questions['role'] = $question;
        }

        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        $role = $input->getArgument('role');
        $user = $this->userRepository->findOneBy(['email' => $email]);

        $roles = $user->getRoles();

        if (!in_array($role, $roles)) {
            $io->error(sprintf('The user %s has not role %s.', $email, $role));

            return Command::FAILURE;
        } else {
            array_splice($roles, array_search($role, $roles), 1);
            $user->setRoles($roles);
            $this->entityManager->flush();
            $io->success(sprintf('The role %s has been removed from user %s.', $role, $email));

            return Command::SUCCESS;
        }
    }
}
