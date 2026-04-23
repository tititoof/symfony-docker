<?php

namespace App\EventListener;

use App\Event\ArticleCreatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ArticleCreatedEvent::NAME)]
class ArticleCreatedListener
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ArticleCreatedEvent $event): void
    {
        $article = $event->getArticle();

        $this->logger->info('Nouvel article créé', [
            'id'        => $article->getId(),
            'title'     => $article->getTitle(),
            'author'    => $article->getAuthor()?->getUsername(),
            'status'    => $article->getStatus(),
            'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
