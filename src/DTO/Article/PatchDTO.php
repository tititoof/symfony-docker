<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ArticlePatchDTO
{
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?string $content = null;

    public ?string $publishedAt = null;

    public ?string $updatedAt = null;

    #[Assert\Length(max: 255)]
    public ?string $slug = null;

    public ?string $image = null;

    #[Assert\Choice(choices: ['draft', 'published'], message: 'Le statut doit être draft ou published')]
    public ?string $status = null;

    public ?int $authorId = null;

    public ?int $categoryId = null;
}
