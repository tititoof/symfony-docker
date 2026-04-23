<?php

namespace App\Controller\Api;

use App\DTO\RegisterDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    // ────────────────────────────────────────────
    // POST /api/auth/login
    // Le corps est intercepté par Lexik JWT
    // mais la route DOIT exister dans Symfony
    // ────────────────────────────────────────────
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Ce code n'est jamais atteint
        // Lexik JWT intercepte avant
        throw new \Exception('Should not be reached');
    }

    // ────────────────────────────────────────────
    // POST /api/auth/register
    // ────────────────────────────────────────────
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            RegisterDTO::class,
            'json'
        );

        // 1. Valider le DTO
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->formatErrors($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Vérifier unicité email et username
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            return $this->json(['errors' => ['email' => 'Cet email est déjà utilisé']], Response::HTTP_CONFLICT);
        }
        if ($this->userRepository->findOneBy(['username' => $dto->username])) {
            return $this->json(['errors' => ['username' => 'Ce nom d\'utilisateur est déjà utilisé']], Response::HTTP_CONFLICT);
        }

        // 3. Créer l'utilisateur
        $user = new User();
        $user->setLastName($dto->lastName);
        $user->setFirstName($dto->firstName);
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setBio($dto->bio);
        $user->setAvatar($dto->avatar);
        $user->setRoles(['ROLE_USER']);

        // 4. Hasher le mot de passe
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $dto->password)
        );

        $this->em->persist($user);
        $this->em->flush();

        return $this->json(
            ['message' => 'Inscription réussie', 'user' => $user],
            Response::HTTP_CREATED,
            [],
            ['groups' => ['user:read']]
        );
    }

    // ────────────────────────────────────────────
    // GET /api/auth/me
    // ────────────────────────────────────────────
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    private function formatErrors($errors, int $status): JsonResponse
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[$error->getPropertyPath()] = $error->getMessage();
        }
        return $this->json(['errors' => $formatted], $status);
    }
}
