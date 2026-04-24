<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ArticleDeletedEvent extends Event
{
    public const NAME = 'article.deleted';

    public function __construct(
        private int $articleId,
        private string $articleTitle,
    ) {}

    public function getArticleId(): int { return $this->articleId; }
    public function getArticleTitle(): string { return $this->articleTitle; }
}
