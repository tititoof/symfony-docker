<?php

namespace App\Controller\Api;

use App\DTO\CommentDTO;
use App\Entity\Comment;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/comments', name: 'api_comment_')]
class CommentController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CommentRepository $commentRepository,
        private UserRepository $userRepository,
        private ArticleRepository $articleRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}


    // ────────────────────────────────────────────
    // GET /api/comments
    // ────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index()
    {
        $comments = $this->commentRepository->findAll();

        return $this->json($comments, Response::HTTP_OK);
    }

    public function create(Request $request)
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            CommentDTO::class,
            'json'
        );
        
        $comment = new Comment();

        $comment->setContent($dto->content);
        $comment->setCreatedAt(new DateTime());

        $author = $this->userRepository->find($dto->authorId);

        $comment->setAuthor($author);

        $article = $this->articleRepository->find($dto->articleId);

        $comment->setArticle($article);

        $errors = $this->validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json($comment, Response::HTTP_OK);
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
