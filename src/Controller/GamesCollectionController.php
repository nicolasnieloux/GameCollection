<?php

namespace App\Controller;

use App\Entity\GamesCollection;
use App\Form\GamesCollectionType;
use App\Repository\GamesCollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            'games_collections' => $gamesCollectionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_games_collection_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $gamesCollection = new GamesCollection();
        $form = $this->createForm(GamesCollectionType::class, $gamesCollection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($gamesCollection);
            $entityManager->flush();

            return $this->redirectToRoute('app_games_collection_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('games_collection/new.html.twig', [
            'games_collection' => $gamesCollection,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_games_collection_show', methods: ['GET'])]
    public function show(GamesCollection $gamesCollection): Response
    {
        return $this->render('games_collection/show.html.twig', [
            'games_collection' => $gamesCollection,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_games_collection_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, GamesCollection $gamesCollection, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(GamesCollectionType::class, $gamesCollection);
        $form->handleRequest($request);

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
        if ($this->isCsrfTokenValid('delete'.$gamesCollection->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($gamesCollection);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_games_collection_index', [], Response::HTTP_SEE_OTHER);
    }
}
