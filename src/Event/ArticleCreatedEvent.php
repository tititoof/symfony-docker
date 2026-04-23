<?php

namespace App\Event;

use App\Entity\Article;
use Symfony\Contracts\EventDispatcher\Event;

class ArticleCreatedEvent extends Event
{
    public const NAME = 'article.created';

    public function __construct(
        private Article $article,
    ) {}

    public function getArticle(): Article
    {
        return $this->article;
    }
}
