<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Création d\'un utilisateur administrateur');

        // Demander les informations
        $email = $io->ask('Email', 'admin@orslabs.fr');
        $firstName = $io->ask('Prénom', 'Admin');
        $lastName = $io->ask('Nom', 'ORS Labs');
        $password = $io->askHidden('Mot de passe (laissez vide pour "admin123")') ?: 'admin123';

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser) {
            $io->error('Un utilisateur avec cet email existe déjà !');
            return Command::FAILURE;
        }

        // Créer l'utilisateur
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        
        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $user->setIsActive(true);

        // Sauvegarder en base
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Utilisateur créé avec succès !');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Email', $user->getEmail()],
                ['Nom complet', $user->getFullName()],
                ['Rôles', implode(', ', $user->getRoles())],
                ['Créé le', $user->getCreatedAt()->format('d/m/Y H:i:s')],
            ]
        );

        $io->note('Vous pouvez maintenant vous connecter avec ces identifiants.');

        return Command::SUCCESS;
    }
}
