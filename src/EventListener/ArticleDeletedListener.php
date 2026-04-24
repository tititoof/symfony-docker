<?php

namespace App\EventListener;

use App\Event\ArticleDeletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ArticleDeletedEvent::NAME)]
class ArticleDeletedListener
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(ArticleDeletedEvent $event): void
    {
        $this->logger->info('🗑️ Article supprimé', [
            'id'    => $event->getArticleId(),
            'title' => $event->getArticleTitle(),
        ]);

        // Ici on pourrait :
        // - Archiver l'article
        // - Notifier l'auteur
        // - Nettoyer des fichiers liés
    }
}
