<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ArticleDTO
{
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\NotBlank(message: 'Le contenu est obligatoire')]
    public ?string $content = null;

    #[Assert\NotBlank(message: 'La date de publication est obligatoire')]
    public ?string $publishedAt = null;

    public ?string $updatedAt = null;

    #[Assert\NotBlank(message: 'Le slug est obligatoire')]
    #[Assert\Length(max: 255)]
    public ?string $slug = null;

    public ?string $image = null;

    #[Assert\NotBlank(message: 'Le statut est obligatoire')]
    #[Assert\Choice(choices: ['draft', 'published'], message: 'Le statut doit être draft ou published')]
    public ?string $status = null;

    #[Assert\NotBlank(message: 'L\'auteur est obligatoire')]
    public ?int $authorId = null;

    public ?int $categoryId = null;
}
