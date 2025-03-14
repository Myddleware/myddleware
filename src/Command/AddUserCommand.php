<?php

namespace App\Command;

use App\Entity\Config;
use App\Entity\User;
use App\Repository\ConfigRepository;
use App\Repository\UserRepository;
use App\Utils\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use function Symfony\Component\String\u;

/**
 * A console command that creates users and stores them in the database.
 *
 * To use this command, open a terminal window, enter into your project
 * directory and execute the following:
 *
 *     $ php bin/console myddleware:add-user
 *
 * To output detailed information, increase the command verbosity:
 *
 *     $ php bin/console myddleware:add-user -vv
 *
 * See https://symfony.com/doc/current/console.html
 *
 * We use the default services.yaml configuration, so command classes are registered as services.
 * See https://symfony.com/doc/current/console/commands_as_services.html
 */
class AddUserCommand extends Command
{
    // to make your command lazily loaded, configure the $defaultName static property,
    // so it will be instantiated only when the command is actually called.
    protected static $defaultName = 'myddleware:add-user';

    /**
     * @var SymfonyStyle
     */
    private $io;

    private $entityManager;
    private $passwordHasher;
    private $validator;
    private $users;
    private $configRepository;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, Validator $validator, UserRepository $users, ConfigRepository $configRepository)
    {
        parent::__construct();

        $this->entityManager = $em;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->users = $users;
        $this->configRepository = $configRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Creates Myddleware users and stores them in the database')
            ->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the new user')
            ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
            ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
            ->addOption('superadmin', null, InputOption::VALUE_NONE, 'If set, the user is created as a super administrator')
        ;
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (null !== $input->getArgument('username') && null !== $input->getArgument('password') && null !== $input->getArgument('email')) {
            return;
        }

        $this->io->title('Add Myddleware User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console myddleware:add-user username password email@example.com',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the username if it's not defined
        $username = $input->getArgument('username');
        if (null !== $username) {
            $this->io->text(' > <info>Username</info>: '.$username);
        } else {
            $username = $this->io->ask('Username', null, [$this->validator, 'validateUsername']);
            $input->setArgument('username', $username);
        }

        // Ask for the password if it's not defined
        $password = $input->getArgument('password');
        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $this->io->askHidden('Password (what you type will be hidden)', [$this->validator, 'validatePassword']);
            $input->setArgument('password', $password);
        }

        // Ask for the email if it's not defined
        $email = $input->getArgument('email');
        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, [$this->validator, 'validateEmail']);
            $input->setArgument('email', $email);
        }
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        $username = $input->getArgument('username');
        $plainPassword = $input->getArgument('password');
        $email = $input->getArgument('email');

        $isSuperAdmin = $input->getOption('superadmin');

        // make sure to validate the user data is correct
        $this->validateUserData($username, $plainPassword, $email);

        // create the user and encode its password
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles($isSuperAdmin ? ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'] : ['ROLE_ADMIN']);
        $user->setEnabled(true);
        $user->setUsernameCanonical($username);
        $user->setEmailCanonical($email);
        $user->setTimezone('UTC');

        // See https://symfony.com/doc/current/security.html#c-encoding-passwords
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        // prevent user from accessing installation process from browser (using Voter)
        $allowInstalls = $this->configRepository->findBy(['name' => 'allow_install']);
        if (!empty($allowInstalls)) {
            foreach ($allowInstalls as $allowInstall) {
                $allowInstall->setValue('false');
                $this->entityManager->persist($allowInstall);
            }
        } else {
            $blockInstall = new Config();
            $blockInstall->setName('allow_install');
            $blockInstall->setValue('false');
            $this->entityManager->persist($blockInstall);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s (%s)', $isSuperAdmin ? 'Super Administrator user' : 'User', $user->getUsername(), $user->getEmail()));

        $event = $stopwatch->stop('add-user-command');
        if ($output->isVerbose()) {
            $this->io->comment(sprintf('New user database id: %d / Elapsed time: %.2f ms / Consumed memory: %.2f MB', $user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return 0;
    }

    private function validateUserData($username, $plainPassword, $email): void
    {
        // first check if a user with the same username already exists.
        $existingUser = $this->users->findOneBy(['username' => $username]);

        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" username.', $username));
        }

        // validate password and email if is not this input means interactive.
        $this->validator->validatePassword($plainPassword);
        $this->validator->validateEmail($email);

        // check if a user with the same email already exists.
        $existingEmail = $this->users->findOneBy(['email' => $email]);

        if (null !== $existingEmail) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
                The <info>%command.name%</info> command creates new users and saves them in the database:
                <info>php %command.full_name%</info> <comment>username password email</comment>
                By default the command creates admin users. If you want to create Super Admin users, 
                add the --superadmin option:
                <info> php %command.full_name%</info> <comment>--superadmin</comment>
                If you omit any of the three required arguments, the command will ask you to
                provide the missing values:
                # command will ask you for the email
                <info>php %command.full_name%</info> <comment>username password</comment>
                # command will ask you for the email and password
                <info>php %command.full_name%</info> <comment>username</comment>
                # command will ask you for all arguments
                <info>php %command.full_name%</info>
                HELP;
    }
}