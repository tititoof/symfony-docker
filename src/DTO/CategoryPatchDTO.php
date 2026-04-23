<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryPatchDTO
{
    // Pas de NotBlank → le champ est optionnel
    #[Assert\Length(max: 150, maxMessage: 'Le nom ne peut pas dépasser 150 caractères')]
    public ?string $name = null;

    public ?string $description = null;
}
