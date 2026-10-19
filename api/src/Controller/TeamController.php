<?php

namespace App\Controller;

#[Route('/api/teams', name: 'api_team_', format: 'json')]
#[OA\Tag(name: 'Équipes', description: 'Exemple fourni : les équipes de la ligue')]
final class TeamController extends AbstractController
{
    /**
     * Injection de dépendances : Symfony fournit automatiquement les services demandés
     * dans le constructeur (ou en argument d'une méthode de contrôleur) grâce à leur type.
     */
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }
    /** GET /api/teams → 200 + tableau JSON. */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(TeamRepository $teams): JsonResponse
    {
        // findBy(critères, tri) : SELECT * FROM team ORDER BY name ASC
        $list = $teams->findBy([], ['name' => 'ASC']);

        // $this->json() transforme les objets en JSON (composant Serializer) : un champ par getter.
        return $this->json($list);
    }
    /**
     * GET /api/teams/{id} → 200, ou 404 si l'équipe n'existe pas.
     *
     * Le paramètre « Team $team » est rempli automatiquement : Symfony lit {id} dans l'URL
     * et charge l'équipe en base (EntityValueResolver). S'il n'existe pas → 404, sans une ligne de code.
     * La « requirement » DIGITS refuse /api/teams/abc (404 aussi : aucune route ne correspond).
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[OA\Response(response: 200, description: 'L\'équipe demandée', content: new Model(type: Team::class))]
    #[OA\Response(response: 404, description: 'Aucune équipe avec cet identifiant')]
    public function show(Team $team): JsonResponse
    {
        return $this->json($team);
    }

    /**
     * POST /api/teams → 201 + Location, 422 si invalide, 415 si le corps n'est pas du JSON.
     *
     * #[MapRequestPayload] fait 3 choses avant même d'entrer dans la méthode :
     *   1. vérifie le Content-Type (application/json attendu, sinon 415) ;
     *   2. transforme le JSON en objet Team (désérialisation ; un « id » envoyé est ignoré : pas de setId()) ;
     *   3. valide l'objet avec les contraintes #[Assert\...] de l'entité → 422 + liste des erreurs.
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Response(response: 201, description: 'Équipe créée ; son URL est dans l\'en-tête Location')]
    #[OA\Response(response: 422, description: 'Données invalides : la liste des violations est dans la réponse')]
    public function create(#[MapRequestPayload] Team $team): JsonResponse
    {
        $this->em->persist($team); // « à enregistrer »...
        $this->em->flush();        // ... INSERT exécuté ici (l'id est alors connu)

        return $this->json($team, Response::HTTP_CREATED, [
            // Bonne pratique REST : l'URL de la ressource créée
            'Location' => $this->generateUrl('api_team_show', ['id' => $team->getId()]),
        ]);
    }

    /**
     * PATCH /api/teams/{id} → 200, 400 si le JSON est illisible, 422 si le résultat est invalide.
     *
     * Ici on ne crée pas un objet : on COMPLÈTE l'objet existant avec les champs reçus
     * (option OBJECT_TO_POPULATE du Serializer), puis on le valide nous-mêmes.
     */
    #[Route('/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => Requirement::DIGITS])]
    public function update(Team $team, Request $request, SerializerInterface $serializer, ValidatorInterface $validator): JsonResponse
    {
        try {
            $serializer->deserialize($request->getContent(), Team::class, 'json', [
                AbstractNormalizer::OBJECT_TO_POPULATE => $team,
            ]);
        } catch (SerializerException $e) {
            throw new BadRequestHttpException('Corps de requête invalide : '.$e->getMessage(), $e);
        }

        $violations = $validator->validate($team);
        if (\count($violations) > 0) {
            // Même format d'erreur que #[MapRequestPayload] : 422 + liste des violations
            throw new UnprocessableEntityHttpException((string) $violations, new ValidationFailedException($team, $violations));
        }

        $this->em->flush(); // UPDATE (Doctrine sait déjà que l'objet a changé)

        return $this->json($team);
    }

    /** DELETE /api/teams/{id} → 204 No Content. */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => Requirement::DIGITS])]
    public function delete(Team $team): Response
    {
        $this->em->remove($team);
        $this->em->flush();

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

}