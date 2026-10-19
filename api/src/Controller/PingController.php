<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API REST « faite main » pour les jeux — EXEMPLE COMMENTÉ, à lire puis à imiter pour Team et Player.
 *
 *   GET    /api/ping        ping 
 *   GET    /api/ping/{id}   ping un joueur                       (404 s'il n'existe pas)                    (204, sans contenu)
 *
 * Le #[Route] de la classe s'applique à toutes les méthodes :
 *   - préfixe d'URL « /api/ping » et préfixe de nom de route « api_ping_ » ;
 *   - format: 'json' → les erreurs (404, 422...) sont, elles aussi, renvoyées en JSON.
 *
 * Les attributs #[OA\...] (OpenApi) sont FACULTATIFS : ils enrichissent la Swagger UI (http://localhost:8000/api).
 * Sans eux, la route apparaît quand même, avec ce que Symfony sait d'elle (chemin, méthode, paramètres).
 */
#[Route('/api', name: 'api_', format: 'json')]
#[OA\Tag(name: 'Ping', description: 'Exemple fourni : le ping du serveur')]
final class PingController extends AbstractController
{

    /** GET /api/ping → 200 + tableau JSON. */
    #[Route('/ping', name: 'ping', methods: ['GET'])]
    public function ping(): JsonResponse
    {
        return $this->json([
            "status" => "ok",
            "league" => "GG League"
        ]);
    }

        /**
     * GET /api/ping/{id} → 200, ou 404 si le joueur n'existe pas.
     *
     * Le paramètre « Game $game » est rempli automatiquement : Symfony lit {id} dans l'URL
     * et charge le jeu en base (EntityValueResolver). S'il n'existe pas → 404, sans une ligne de code.
     * La « requirement » DIGITS refuse /api/games/abc (404 aussi : aucune route ne correspond).
     */
    #[Route('/hello/{player}', name: 'show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Le jeu demandé', content: new Model(type: Game::class))]
    #[OA\Response(response: 404, description: 'Aucun jeu avec cet identifiant')]
    public function show(String $player): JsonResponse
    {
        return $this->json([
            "status" => "ok",
            "league" => "GG " . $player . " !"
        ]);
    }
}
