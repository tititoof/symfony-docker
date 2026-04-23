<?php

namespace App\Controller\Api;

use App\DTO\ArticleDTO;
use App\DTO\ArticlePatchDTO;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Security\Voter\ArticleVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/articles', name: 'api_article_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepository,
        private UserRepository $userRepository,
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    // ────────────────────────────────────────────
    // GET /api/articles
    // ────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): JsonResponse
    {
        $articles = $this->articleRepository->findAll();

        // Filtrer uniquement les articles que l'user peut voir
        $visibleArticles = array_filter(
            $articles,
            fn($article) => $this->isGranted(ArticleVoter::VIEW, $article)
        );

        return $this->json(
            array_values($visibleArticles),
            Response::HTTP_OK,
            [],
            ['groups' => ['article:read']]
        );
    }

    // ────────────────────────────────────────────
    // GET /api/articles/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function show(int $id): JsonResponse
    {
        $article = $this->articleRepository->find($id);

        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::VIEW, $article);

        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ────────────────────────────────────────────
    // POST /api/articles
    // ────────────────────────────────────────────
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_WRITER')]
    public function create(Request $request): JsonResponse
    {
        $article = new Article();

        $this->denyAccessUnlessGranted(ArticleVoter::CREATE, $article);

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            ArticleDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $author = $this->userRepository->find($dto->authorId);
        if (!$author) {
            return $this->json(
                ['errors' => ['authorId' => 'Auteur non trouvé']],
                Response::HTTP_NOT_FOUND
            );
        }

        $category = null;
        if ($dto->categoryId) {
            $category = $this->categoryRepository->find($dto->categoryId);
            if (!$category) {
                return $this->json(
                    ['errors' => ['categoryId' => 'Catégorie non trouvée']],
                    Response::HTTP_NOT_FOUND
                );
            }
        }
    
        $this->mapDtoToArticle($dto, $article, $author, $category);

        // 5. Valider l'entité (UniqueEntity sur slug)
        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->persist($article);
        $this->em->flush();

        return $this->json($article, Response::HTTP_CREATED, [], ['groups' => ['article:read']]);
    }

    // ────────────────────────────────────────────
    // PUT /api/articles/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_WRITER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            ArticleDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $author = $this->userRepository->find($dto->authorId);
        if (!$author) {
            return $this->json(
                ['errors' => ['authorId' => 'Auteur non trouvé']],
                Response::HTTP_NOT_FOUND
            );
        }

        $category = null;
        if ($dto->categoryId) {
            $category = $this->categoryRepository->find($dto->categoryId);
            if (!$category) {
                return $this->json(
                    ['errors' => ['categoryId' => 'Catégorie non trouvée']],
                    Response::HTTP_NOT_FOUND
                );
            }
        }

        $this->mapDtoToArticle($dto, $article, $author, $category);

        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->flush();

        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ────────────────────────────────────────────
    // PATCH /api/articles/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    #[IsGranted('ROLE_WRITER')]
    public function patch(int $id, Request $request): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            ArticlePatchDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $data = json_decode($request->getContent(), true);

        if (array_key_exists('title', $data))       $article->setTitle($dto->title);
        if (array_key_exists('content', $data))     $article->setContent($dto->content);
        if (array_key_exists('slug', $data))        $article->setSlug($dto->slug);
        if (array_key_exists('image', $data))       $article->setImage($dto->image);
        if (array_key_exists('status', $data))      $article->setStatus($dto->status);
        if (array_key_exists('updatedAt', $data))   $article->setUpdatedAt($dto->updatedAt ? new \DateTime($dto->updatedAt) : null);
        if (array_key_exists('publishedAt', $data)) $article->setPublishedAt(new \DateTime($dto->publishedAt));

        if (array_key_exists('authorId', $data)) {
            $author = $this->userRepository->find($dto->authorId);
            if (!$author) {
                return $this->json(['errors' => ['authorId' => 'Auteur non trouvé']], Response::HTTP_NOT_FOUND);
            }
            $article->setAuthor($author);
        }

        if (array_key_exists('categoryId', $data)) {
            $category = $dto->categoryId ? $this->categoryRepository->find($dto->categoryId) : null;
            if ($dto->categoryId && !$category) {
                return $this->json(['errors' => ['categoryId' => 'Catégorie non trouvée']], Response::HTTP_NOT_FOUND);
            }
            $article->setCategory($category);
        }

        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->flush();

        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ────────────────────────────────────────────
    // DELETE /api/articles/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::DELETE, $article);

        $this->em->remove($article);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // ────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────
    private function mapDtoToArticle(ArticleDTO $dto, Article $article, $author, $category): void
    {
        $article->setTitle($dto->title);
        $article->setContent($dto->content);
        $article->setPublishedAt(new \DateTime($dto->publishedAt));
        $article->setUpdatedAt($dto->updatedAt ? new \DateTime($dto->updatedAt) : null);
        $article->setSlug($dto->slug);
        $article->setImage($dto->image);
        $article->setStatus($dto->status);
        $article->setAuthor($author);
        $article->setCategory($category);
    }

    private function formatErrors($errors): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[$error->getPropertyPath()] = $error->getMessage();
        }
        return $formatted;
    }
}
