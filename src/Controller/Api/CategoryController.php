<?php

namespace App\Controller\Api;

use App\DTO\CategoryDTO;
use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories', name: 'api_category_')]
class CategoryController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    // ────────────────────────────────────────────
    // GET /api/categories
    // ────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $categories = $this->categoryRepository->findAll();

        return $this->json($categories, Response::HTTP_OK, ['groups' => ['category:read']]);
    }

    // ────────────────────────────────────────────
    // GET /api/categories/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json(
                ['error' => 'Catégorie non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        return $this->json($category, Response::HTTP_OK, ['groups' => ['category:read']]);
    }

    // ────────────────────────────────────────────
    // POST /api/categories
    // ────────────────────────────────────────────
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CategoryDTO::class,
            'json'
        );

        $category = new Category();
        $category->setName($dto->name);
        $category->setDescription($dto->description);

        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->em->persist($category);
        $this->em->flush();

        return $this->json($category, Response::HTTP_CREATED, ['groups' => ['category:read']]);
    }

    // ────────────────────────────────────────────
    // PUT /api/categories/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json(
                ['error' => 'Catégorie non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CategoryDTO::class,
            'json'
        );

        $category->setName($dto->name);
        $category->setDescription($dto->description);

        $errors = $this->validator->validate(category);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->em->flush();

        return $this->json($category, Response::HTTP_OK, ['groups' => ['category:read']]);
    }

    // ────────────────────────────────────────────
    // PATCH /api/categories/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(int $id, Request $request): JsonResponse
    {
        $category = $this->categoryRepository->find($id);
        if (!$category) {
            return $this->json(
                ['error' => 'Catégorie non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        // ✅ On utilise maintenant le DTO dédié PATCH
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CategoryPatchDTO::class,
            'json'
        );

        // Valider le DTO
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Appliquer uniquement les champs présents dans le body
        $data = json_decode($request->getContent(), true);

        if (array_key_exists('name', $data)) {
            $category->setName($dto->name);
        }
        if (array_key_exists('description', $data)) {
            $category->setDescription($dto->description);
        }

        // Valider l'entité (UniqueEntity)
        $errors = $this->validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->flush();

        return $this->json($category, Response::HTTP_OK, [], ['groups' => ['category:read']]);
    }

    // ────────────────────────────────────────────
    // DELETE /api/categories/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            return $this->json(
                ['error' => 'Catégorie non trouvée'],
                Response::HTTP_NOT_FOUND
            );
        }

        $this->em->remove($category);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // ────────────────────────────────────────────
    // Helper : formatage des erreurs de validation
    // ────────────────────────────────────────────
    private function formatErrors($errors): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[$error->getPropertyPath()] = $error->getMessage();
        }
        return $formatted;
    }
}
