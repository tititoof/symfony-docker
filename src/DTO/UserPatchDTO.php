<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserPatchDTO
{
    #[Assert\Length(max: 255)]
    public ?string $lastName = null;

    #[Assert\Length(max: 255)]
    public ?string $firstName = null;

    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    public ?string $email = null;

    #[Assert\Length(max: 255)]
    public ?string $username = null;

    public ?string $bio = null;

    public ?string $avatar = null;
}
