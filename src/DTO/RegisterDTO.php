<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterDTO
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    public ?string $lastName = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire')]
    public ?string $username = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
    #[Assert\Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins 8 caractères')]
    public ?string $password = null;

    public ?string $bio = null;
    public ?string $avatar = null;
}
