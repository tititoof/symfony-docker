<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_REFERENCE = 'user_';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // 1 admin
        $admin = new User();
        $admin->setLastName('Admin');
        $admin->setFirstName('Super');
        $admin->setEmail('admin@test.com');
        $admin->setUsername('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'password123'));
        $manager->persist($admin);
        $this->addReference(self::USER_REFERENCE . 'admin', $admin);

        // 1 writer
        $writer = new User();
        $writer->setLastName('Writer');
        $writer->setFirstName('John');
        $writer->setEmail('writer@test.com');
        $writer->setUsername('writer');
        $writer->setRoles(['ROLE_WRITER']);
        $writer->setPassword($this->passwordHasher->hashPassword($writer, 'password123'));
        $manager->persist($writer);
        $this->addReference(self::USER_REFERENCE . 'writer', $writer);

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setLastName($faker->lastName());
            $user->setFirstName($faker->firstName());
            $user->setEmail($faker->unique()->safeEmail());
            $user->setUsername($faker->unique()->userName());
            $user->setBio($faker->optional()->paragraph());
            $user->setAvatar($faker->optional()->imageUrl(100, 100, 'people'));
            $user->setPassword($this->passwordHasher->hashPassword($writer, 'password123'));
            $writer->setRoles(['ROLE_WRITER']);
            $manager->persist($user);

            // ✅ Référence pour les autres fixtures
            $this->addReference(self::USER_REFERENCE . $i, $user);
        }

        $manager->flush();
    }
}
