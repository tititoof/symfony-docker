<?php

namespace App\Controller\Api;

use App\DTO\UserDTO;
use App\DTO\UserPatchDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users', name: 'api_user_')]
class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    // ────────────────────────────────────────────
    // GET /api/users
    // ────────────────────────────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        return $this->json($users, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    // ────────────────────────────────────────────
    // GET /api/users/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    // ────────────────────────────────────────────
    // POST /api/users
    // ────────────────────────────────────────────
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            UserDTO::class,
            'json'
        );

        // 1. Valider le DTO
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // 2. Construire l'entité
        $user = new User();
        $this->mapDtoToUser($dto, $user);

        // 3. Valider l'entité (UniqueEntity sur username et email)
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ['user:read']]);
    }

    // ────────────────────────────────────────────
    // PUT /api/users/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            UserDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->mapDtoToUser($dto, $user);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->flush();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    // ────────────────────────────────────────────
    // PATCH /api/users/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            UserPatchDTO::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Appliquer uniquement les champs présents
        $data = json_decode($request->getContent(), true);

        if (array_key_exists('lastName', $data))  $user->setLastName($dto->lastName);
        if (array_key_exists('firstName', $data)) $user->setFirstName($dto->firstName);
        if (array_key_exists('email', $data))     $user->setEmail($dto->email);
        if (array_key_exists('username', $data))  $user->setUsername($dto->username);
        if (array_key_exists('bio', $data))       $user->setBio($dto->bio);
        if (array_key_exists('avatar', $data))    $user->setAvatar($dto->avatar);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return $this->json(
                ['errors' => $this->formatErrors($errors)],
                Response::HTTP_CONFLICT
            );
        }

        $this->em->flush();

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }

    // ────────────────────────────────────────────
    // DELETE /api/users/{id}
    // ────────────────────────────────────────────
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($user);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // ────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────
    private function mapDtoToUser(UserDTO $dto, User $user): void
    {
        $user->setLastName($dto->lastName);
        $user->setFirstName($dto->firstName);
        $user->setEmail($dto->email);
        $user->setUsername($dto->username);
        $user->setBio($dto->bio);
        $user->setAvatar($dto->avatar);
    }

    private function formatErrors($errors): array
    {
        $formatted = [];
        foreach ($errors as $error) {
            $formatted[$error->getPropertyPath()] = $error->getMessage();
        }
        return $formatted;
    }
}
