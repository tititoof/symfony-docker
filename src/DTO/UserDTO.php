<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDTO
{
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(max: 255)]
    public ?string $lastName = null;

    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(max: 255)]
    public ?string $firstName = null;

    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire')]
    #[Assert\Length(max: 255)]
    public ?string $username = null;

    public ?string $bio = null;

    public ?string $avatar = null;
}
