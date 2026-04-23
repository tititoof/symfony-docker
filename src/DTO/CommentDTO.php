<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CommentDTO
{
    private ?string $content = null;

    private ?\DateTime $createdAt = null;

   #[Assert\NotBlank(message: 'L\'utilisateur est obligatoire')]
    private ?User $authorId = null;

    #[Assert\NotBlank(message: 'L\'article est obligatoire')]
    private ?Article $articleId = null;
}
