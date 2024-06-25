<?php

namespace App\Controller;

use App\Entity\GamesCollection;
use App\Form\GamesCollectionType;
use App\Repository\GamesCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/games/collection')]
class GamesCollectionController extends AbstractController
{
    #[Route('/', name: 'app_games_collection_index', methods: ['GET'])]
    public function index(GamesCollectionRepository $gamesCollectionRepository): Response
    {
        return $this->render('games_collection/index.html.twig', [
            'games_collections' => $gamesCollectionRepository->findBy(['user' => $this->getUser()]),
        ]);
    }

    #[Route('/new', name: 'app_games_collection_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, GamesCollectionRepository $gamesCollectionRepository): Response
    {
        $gamesCollection = new GamesCollection();
        $form = $this->createForm(GamesCollectionType::class, $gamesCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $gamesCollection->setUser($this->getUser());
            $gamesCollection->setCreatedAt(new \DateTimeImmutable());

            $existingCollection = $gamesCollectionRepository->findOneBy([
                'name' => $gamesCollection->getName(),
                'user' => $this->getUser()
            ]);
            if ($existingCollection) {
                $form->get('name')->addError(new FormError('nom de la collection déjà exitante'));
            } else {
                $entityManager->persist($gamesCollection);
                $entityManager->flush();

                return $this->redirectToRoute('app_games_collection_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('games_collection/new.html.twig', [
            'games_collection' => $gamesCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_games_collection_show', methods: ['GET'])]
    public function show(GamesCollection $gamesCollection): Response
    {
        $currentUser = $this->getUser();
        if ($gamesCollection->getUser() !== $currentUser) {
            return $this->render('accessDenied.html.twig');
        }
        return $this->render('games_collection/show.html.twig', [
            'games_collection' => $gamesCollection,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_games_collection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GamesCollection $gamesCollection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GamesCollectionType::class, $gamesCollection);
        $form->handleRequest($request);

        $currentUser = $this->getUser();
        if ($gamesCollection->getUser() !== $currentUser) {
            return $this->render('accessDenied.html.twig');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_games_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('games_collection/edit.html.twig', [
            'games_collection' => $gamesCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_games_collection_delete', methods: ['POST'])]
    public function delete(Request $request, GamesCollection $gamesCollection, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $gamesCollection->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($gamesCollection);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_games_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
