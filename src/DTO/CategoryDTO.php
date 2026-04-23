<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryDTO
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 150, maxMessage: 'Le nom ne peut pas dépasser 150 caractères')]
    public ?string $name = null;

    public ?string $description = null;
}
