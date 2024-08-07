<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\GamesCollectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, GamesCollectionRepository $gamesCollectionRepository): Response
    {

        $user = $this->security->getUser();
        $collections = $gamesCollectionRepository->findBy(['user' => $user]);

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir cette page.');
        }
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'collection' => $collections,
            'user' => $user
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(UserRepository $userRepository, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);
            $pseudoNewUser = $form->get('pseudo')->getData();
            $existingPseudo = $userRepository->findOneBy(['pseudo' => $pseudoNewUser]);
            $emailNewUser = $form->get('email')->getData();
            $existingUser = $userRepository->findOneBy(['email' => $emailNewUser]);

            if ($existingPseudo) {
                $form->get('pseudo')->addError(new FormError('Pseudo already exist'));
            }
            elseif ($existingUser) {
                $form->get('email')->addError(new FormError('User already exists'));

            } else {
                $entityManager->persist($user);
                $entityManager->flush();

                return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response

    {
        $currentUser = $this->security->getUser();

        if (!$currentUser || $currentUser->getId() !== $user->getId()) {
            return $this->render('accessDenied.html.twig');
            }
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {


        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $loggedInUser = $this->getUser();
//
//        if (!$this->isGranted('ROLE_ADMIN')) {
//            throw $this->createAccessDeniedException();
//        }
        if ($loggedInUser !== $user) {
            return $this->render('accessDenied.html.twig');
        }

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, Security $security): Response
    {
        $currentUser = $security->getUser();

        if ($currentUser && $currentUser->getId() == $user->getId()) {
            // Logout current user before delete
            session_destroy();
        }
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
    }
}
