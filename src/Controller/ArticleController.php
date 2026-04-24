<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/articles', name: 'app_article_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepository,
    ) {}

    // ── GET /articles ──────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $articles = $this->articleRepository->findAll();

        return $this->render('article/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    // ── GET|POST /articles/new ─────────────────────
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_WRITER')]
    public function new(Request $request): Response
    {
        $article = new Article();
        $form    = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($article);
            $this->em->flush();

            $this->addFlash('success', 'Article créé avec succès !');
            return $this->redirectToRoute('app_article_index');
        }

        return $this->render('article/new.html.twig', [
            'form' => $form,
        ]);
    }

    // ── GET /articles/{id} ─────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }

    // ── GET|POST /articles/{id}/edit ───────────────
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_WRITER')]
    public function edit(Request $request, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setUpdatedAt(new \DateTime());
            $this->em->flush();

            $this->addFlash('success', 'Article modifié avec succès !');
            return $this->redirectToRoute('app_article_index');
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form'    => $form,
        ]);
    }

    // ── POST /articles/{id}/delete ─────────────────
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Article $article): Response
    {
        // ✅ Vérification du token CSRF
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            $this->em->remove($article);
            $this->em->flush();
            $this->addFlash('success', 'Article supprimé avec succès !');
        }

        return $this->redirectToRoute('app_article_index');
    }
}
