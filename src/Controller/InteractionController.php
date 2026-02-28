<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\UserEvenementInteractionRepository;
use App\Service\InteractionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/interaction')]
class InteractionController extends AbstractController
{
    public function __construct(private InteractionService $interactionService)
    {
    }

    #[Route('/like/{id}', name: 'app_interaction_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function like(Evenement $evenement): JsonResponse
    {
        $result = $this->interactionService->toggleLike($this->getUser(), $evenement);

        return $this->json([
            'success' => true,
            'likes' => $evenement->getLikesCount(),
            'dislikes' => $evenement->getDislikesCount(),
            'userReaction' => $result['action'] === 'removed' ? null : 'like',
        ]);
    }

    #[Route('/dislike/{id}', name: 'app_interaction_dislike', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function dislike(Evenement $evenement): JsonResponse
    {
        $result = $this->interactionService->toggleDislike($this->getUser(), $evenement);

        return $this->json([
            'success' => true,
            'likes' => $evenement->getLikesCount(),
            'dislikes' => $evenement->getDislikesCount(),
            'userReaction' => $result['action'] === 'removed' ? null : 'dislike',
        ]);
    }

    #[Route('/favorite/{id}', name: 'app_interaction_favorite', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function favorite(Evenement $evenement, UserEvenementInteractionRepository $repo): JsonResponse
    {
        $result = $this->interactionService->toggleFavorite($this->getUser(), $evenement);
        $favorites = $repo->getUserFavorites($this->getUser());

        return $this->json([
            'success' => true,
            'isFavorite' => $result['action'] === 'added',
            'totalUserFavorites' => \count($favorites),
        ]);
    }

    #[Route('/favorites', name: 'app_my_favorites')]
    #[IsGranted('ROLE_USER')]
    public function myFavorites(UserEvenementInteractionRepository $repo): Response
    {
        $favorites = $repo->getUserFavorites($this->getUser());
        return $this->render('interaction/favorites.html.twig', [
            'favorites' => $favorites,
        ]);
    }
}
