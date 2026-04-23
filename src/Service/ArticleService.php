<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Event\ArticleCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ArticleService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepository,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function createArticle(
        string $title,
        string $content,
        string $slug,
        string $status,
        User $author,
        ?Category $category = null,
    ): Article {
        $this->logger->info('Création d\'un article', [
            'title'  => $title,
            'author' => $author->getUsername(),
        ]);

        $existing = $this->articleRepository->findOneBy(['slug' => $slug]);
        if ($existing) {
            $this->logger->warning('Slug déjà existant', ['slug' => $slug]);
            throw new \InvalidArgumentException("Le slug '{$slug}' existe déjà.");
        }

        $article = new Article();
        $article->setTitle($title);
        $article->setContent($content);
        $article->setSlug($slug);
        $article->setStatus($status);
        $article->setPublishedAt(new \DateTime());
        $article->setAuthor($author);
        $article->setCategory($category);

        $this->em->persist($article);
        $this->em->flush();

        $this->eventDispatcher->dispatch(
            new ArticleCreatedEvent($article),
            ArticleCreatedEvent::NAME
        );

        $this->logger->info('Article créé avec succès', [
            'id'   => $article->getId(),
            'slug' => $article->getSlug(),
        ]);

        return $article;
    }

    public function publishArticle(Article $article): Article
    {
        if ($article->getStatus() === 'published') {
            $this->logger->warning('Article déjà publié', [
                'id' => $article->getId()
            ]);
            throw new \LogicException("L'article est déjà publié.");
        }

        $article->setStatus('published');
        $article->setPublishedAt(new \DateTime());
        $this->em->flush();

        $this->logger->info('Article publié', [
            'id'    => $article->getId(),
            'title' => $article->getTitle(),
        ]);

        return $article;
    }

    public function deleteArticle(Article $article): void
    {
        $this->logger->info('Suppression de l\'article', [
            'id'    => $article->getId(),
            'title' => $article->getTitle(),
        ]);

        $this->em->remove($article);
        $this->em->flush();

        $this->logger->info('Article supprimé avec succès');
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    public function generateSlug(string $title): string
    {
        return $this->slugify($title) . '-' . uniqid();
    }
}
