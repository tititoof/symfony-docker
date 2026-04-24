<?php

namespace App\Event;

use App\Entity\Article;
use Symfony\Contracts\EventDispatcher\Event;

class ArticleUpdatedEvent extends Event
{
    public const NAME = 'article.updated';

    public function __construct(
        private Article $article,
        private array $changedFields = [], // ✅ champs modifiés
    ) {}

    public function getArticle(): Article { return $this->article; }
    public function getChangedFields(): array { return $this->changedFields; }
}
