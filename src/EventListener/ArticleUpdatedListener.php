<?php

namespace App\EventListener;

use App\Event\ArticleUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: ArticleUpdatedEvent::NAME)]
class ArticleUpdatedListener
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(ArticleUpdatedEvent $event): void
    {
        $article       = $event->getArticle();
        $changedFields = $event->getChangedFields();

        $this->logger->info('✏️ Article modifié', [
            'id'            => $article->getId(),
            'title'         => $article->getTitle(),
            'changedFields' => $changedFields,
        ]);

        // Exemple : log spécifique si le statut a changé
        if (in_array('status', $changedFields)) {
            $this->logger->info('🔄 Statut de l\'article modifié', [
                'id'     => $article->getId(),
                'status' => $article->getStatus(),
            ]);
        }
    }
}
