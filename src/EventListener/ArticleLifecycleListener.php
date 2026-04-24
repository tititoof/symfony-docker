<?php

namespace App\EventListener;

use App\Entity\Article;
use App\Event\ArticleCreatedEvent;
use App\Event\ArticleDeletedEvent;
use App\Event\ArticleUpdatedEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// ✅ Symfony enregistre automatiquement ce listener pour l'entité Article
#[AsEntityListener(event: Events::postPersist, entity: Article::class)]
#[AsEntityListener(event: Events::postUpdate,  entity: Article::class)]
#[AsEntityListener(event: Events::preRemove,   entity: Article::class)]
class ArticleLifecycleListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    // ────────────────────────────────────────────
    // Après la création (INSERT)
    // ────────────────────────────────────────────
    public function postPersist(Article $article, PostPersistEventArgs $args): void
    {
        $this->eventDispatcher->dispatch(
            new ArticleCreatedEvent($article),
            ArticleCreatedEvent::NAME
        );
    }

    // ────────────────────────────────────────────
    // Après la modification (UPDATE)
    // ────────────────────────────────────────────
    public function postUpdate(Article $article, PostUpdateEventArgs $args): void
    {
        // ✅ Récupérer les champs modifiés via UnitOfWork
        $uow            = $args->getObjectManager()->getUnitOfWork();
        $changeSet      = $uow->getEntityChangeSet($article);
        $changedFields  = array_keys($changeSet);

        $this->eventDispatcher->dispatch(
            new ArticleUpdatedEvent($article, $changedFields),
            ArticleUpdatedEvent::NAME
        );
    }

    // ────────────────────────────────────────────
    // Avant la suppression (DELETE)
    // ⚠️ preRemove car après la suppression l'entité n'existe plus
    // ────────────────────────────────────────────
    public function preRemove(Article $article, PreRemoveEventArgs $args): void
    {
        $this->eventDispatcher->dispatch(
            new ArticleDeletedEvent($article->getId(), $article->getTitle()),
            ArticleDeletedEvent::NAME
        );
    }
}
