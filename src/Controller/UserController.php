<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
class UserController extends AbstractController
{
    // ─────────────────────────────────────────────
    //  Changer son propre mot de passe
    // ─────────────────────────────────────────────
    #[Route('/change-password', name: 'app_change_password', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword  = $request->request->get('current_password', '');
            $newPassword      = $request->request->get('new_password', '');
            $confirmPassword  = $request->request->get('confirm_password', '');
            $token            = $request->request->get('_csrf_token');

            // Vérification CSRF
            if (!$this->isCsrfTokenValid('change_password', $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_change_password');
            }

            // Vérification mot de passe actuel
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_change_password');
            }

            // Vérification longueur
            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_change_password');
            }

            // Vérification confirmation
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', "Les deux mots de passe ne correspondent pas.");
                return $this->redirectToRoute('app_change_password');
            }

            // Hash et sauvegarde
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('user/change_password.html.twig');
    }

    // ─────────────────────────────────────────────
    //  Créer un nouvel utilisateur (admin seulement)
    // ─────────────────────────────────────────────
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newUser(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            $email     = trim($request->request->get('email', ''));
            $firstName = trim($request->request->get('first_name', ''));
            $lastName  = trim($request->request->get('last_name', ''));
            $password  = $request->request->get('password', '');
            $confirm   = $request->request->get('confirm_password', '');
            $isAdmin   = $request->request->getBoolean('is_admin');
            $token     = $request->request->get('_csrf_token');

            // CSRF
            if (!$this->isCsrfTokenValid('new_user', $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_user_new');
            }

            // Champs obligatoires
            if (!$email || !$firstName || !$lastName || !$password) {
                $this->addFlash('error', 'Tous les champs sont obligatoires.');
                return $this->redirectToRoute('app_user_new');
            }

            // Email déjà utilisé ?
            if ($userRepository->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà.');
                return $this->redirectToRoute('app_user_new');
            }

            // Longueur mot de passe
            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_user_new');
            }

            // Confirmation
            if ($password !== $confirm) {
                $this->addFlash('error', "Les deux mots de passe ne correspondent pas.");
                return $this->redirectToRoute('app_user_new');
            }

            // Création
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setRoles($isAdmin ? ['ROLE_USER', 'ROLE_ADMIN'] : ['ROLE_USER']);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setIsActive(true);

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', sprintf(
                "L'utilisateur %s %s (%s) a été créé avec succès.",
                $firstName, $lastName, $email
            ));

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('user/new_user.html.twig');
    }
}
