<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Add a short description for your command',
)]
class CreateUserCommand extends Command
{
	public function __construct(
		private userPasswordHasherInterface $passwordHasher,
		private EntityManagerInterface $em,
		private ValidatorInterface $validator,
	)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
	
        $io->title('Création d\'un utilisateur');

        $username = $io->ask('Username : ', null, function(?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('le username est obligatoire');
            }

            return $value;
        });

        $email = $io->ask('Email : ', null, function(?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('l\'email est obligatoire');
            }

            return $value;
        });

        $passwd = $io->ask('Mdp : ', null, function(?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('Le mot de passe est obligatoire');
            }

            return $value;
        });

        $firstname = $io->ask('firstname : ', null, function(?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('Le firstname est obligatoire');
            }

            return $value;
        });

        $lastname = $io->ask('lastname : ', null, function(?string $value): string {
            if (empty($value)) {
                throw new \RuntimeExeption('Le lastname est obligatoire');
            }

            return $value;
        });

        $user = new User();

        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $passwd),
        );
        $user->setRoles(['ROLE_USER']);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            throw new \RuntimeException($errors);
        }
        $this->em->persist($user);
        $this->em->flush();

        $io->success('création de l\'utilisateur effectué.');

        return Command::SUCCESS;
    }
}
