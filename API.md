# 🚀 Guide complet — API REST Symfony avec JWT
## Exemple : Entité Article (from scratch)

---

## 📋 Ordre des étapes

```
1.  Installation du projet
2.  Installation des dépendances
3.  Configuration de la base de données
4.  Création de l'entité
5.  Migration
6.  Configuration JWT
7.  Mise à jour de l'entité User (UserInterface)
8.  Configuration security.yaml
9.  DTOs
10. Controller API
11. Fixtures (optionnel)
12. Tests
```

---

## Étape 1 — Installation du projet

```bash
composer create-project symfony/skeleton my-project
cd my-project
```

---

## Étape 2 — Installation des dépendances

```bash
# ORM + Base de données
composer require symfony/orm-pack

# Serializer (convertir entités en JSON)
composer require symfony/serializer-pack

# Validation
composer require symfony/validator

# Sécurité
composer require symfony/security-bundle

# JWT
composer require lexik/jwt-authentication-bundle

# Maker (génération de code)
composer require --dev symfony/maker-bundle

# Fixtures + Faker (données de test)
composer require --dev orm-fixtures
composer require --dev fakerphp/faker

# Tests
composer require --dev symfony/test-pack
```

---

## Étape 3 — Configuration de la base de données

### `.env`
```dotenv
DATABASE_URL="postgresql://user:password@localhost:5432/my_db"
```

```bash
# Créer la base de données
php bin/console doctrine:database:create
```

---

## Étape 4 — Création de l'entité User (obligatoire avant Article)

> ⚠️ User doit être créé AVANT Article car Article dépend de User.

```bash
php bin/console make:entity User
```

```
> lastName     | string  | 255 | no
> firstName    | string  | 255 | no
> email        | string  | 255 | no
> username     | string  | 255 | no  (unique: yes)
> bio          | text    | nullable: yes
> avatar       | string  | 255 | nullable: yes
> [Entrée]
```

### Mettre à jour l'entité User pour la sécurité

```php
// src/Entity/User.php
<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur existe déjà')]
#[UniqueEntity(fields: ['email'], message: 'Cet email existe déjà')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'article:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'article:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'article:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read', 'article:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['user:read', 'article:read'])]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null; // ❌ jamais exposé

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['user:read'])]
    private array $roles = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $avatar = null;

    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'author')]
    private Collection $articles; // ❌ pas de Groups → évite la référence circulaire

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->roles    = ['ROLE_USER'];
    }

    // ── UserInterface ──────────────────────────
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles   = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function eraseCredentials(): void {}

    // ── PasswordAuthenticatedUserInterface ─────
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    // ── Getters & Setters ──────────────────────
    public function getId(): ?int { return $this->id; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $v): static { $this->lastName = $v; return $this; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $v): static { $this->firstName = $v; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }

    public function getUsername(): ?string { return $this->username; }
    public function setUsername(string $v): static { $this->username = $v; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $v): static { $this->bio = $v; return $this; }

    public function getAvatar(): ?string { return $this->avatar; }
    public function setAvatar(?string $v): static { $this->avatar = $v; return $this; }

    public function getArticles(): Collection { return $this->articles; }
}
```

---

## Étape 5 — Création de l'entité Category

```bash
php bin/console make:entity Category
```

```
> name        | string | 150 | no  (unique: yes)
> description | text   | nullable: yes
> [Entrée]
```

```php
// src/Entity/Category.php
<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce nom de catégorie existe déjà')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read', 'article:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 150, unique: true)]
    #[Assert\NotBlank]
    #[Groups(['category:read', 'article:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['category:read', 'article:read'])]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'category')]
    private Collection $articles; // ❌ pas de Groups → évite la référence circulaire

    public function __construct() { $this->articles = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $v): static { $this->name = $v; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }

    public function getArticles(): Collection { return $this->articles; }
}
```

---

## Étape 6 — Création de l'entité Article

```bash
php bin/console make:entity Article
```

```
> title       | string   | 255 | no
> content     | text     | no
> publishedAt | datetime | no
> updatedAt   | datetime | nullable: yes
> slug        | string   | 255 | no  (unique: yes)
> image       | string   | 255 | nullable: yes
> status      | string   | 50  | no
> author      | ManyToOne → User     | nullable: no  | inverse: yes → articles
> category    | ManyToOne → Category | nullable: yes | inverse: yes → articles
> [Entrée]
```

```php
// src/Entity/Article.php
<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug existe déjà')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['article:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['article:read', 'article:write'])]
    private ?\DateTime $publishedAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $image = null;

    #[ORM\Column(length: 50)]
    #[Groups(['article:read', 'article:write'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[Groups(['article:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'articles')]
    #[Groups(['article:read'])]
    private ?Category $category = null;

    // ❌ pas de Groups sur comments → évite la référence circulaire
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'article', orphanRemoval: true)]
    private Collection $comments;

    public function __construct() { $this->comments = new ArrayCollection(); }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(string $v): static { $this->content = $v; return $this; }

    public function getPublishedAt(): ?\DateTime { return $this->publishedAt; }
    public function setPublishedAt(\DateTime $v): static { $this->publishedAt = $v; return $this; }

    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $v): static { $this->updatedAt = $v; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $v): static { $this->slug = $v; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $v): static { $this->image = $v; return $this; }

    public function getStatus(): ?string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }

    public function getAuthor(): ?User { return $this->author; }
    public function setAuthor(?User $v): static { $this->author = $v; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $v): static { $this->category = $v; return $this; }

    public function getComments(): Collection { return $this->comments; }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setArticle($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getArticle() === $this) {
                $comment->setArticle(null);
            }
        }
        return $this;
    }
}
```

---

## Étape 7 — Migration

```bash
# Générer la migration
php bin/console make:migration

# Appliquer la migration
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Étape 8 — Configuration JWT

### Générer les clés SSL
```bash
php bin/console lexik:jwt:generate-keypair
```

### `.env`
```dotenv
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase
JWT_TTL=3600
```

### `config/packages/lexik_jwt_authentication.yaml`
```yaml
lexik_jwt_authentication:
    secret_key: '%env(resolve:JWT_SECRET_KEY)%'
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    pass_phrase: '%env(JWT_PASSPHRASE)%'
    token_ttl: 3600
```

---

## Étape 9 — Configuration `security.yaml`

```yaml
# config/packages/security.yaml
security:

    password_hashers:
        App\Entity\User:
            algorithm: auto

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: username  # login par username

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            pattern: ^/api/auth/login
            stateless: true
            json_login:
                check_path: api_auth_login  # nom de la route
                username_path: email        # clé dans le body JSON
                password_path: password
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        register:
            pattern: ^/api/auth/register
            stateless: true
            security: false

        api:
            pattern: ^/api
            stateless: true
            jwt: ~

    access_control:
        - { path: ^/api/auth/login,    roles: PUBLIC_ACCESS }
        - { path: ^/api/auth/register, roles: PUBLIC_ACCESS }
        - { path: ^/api/articles,   methods: [GET],                      roles: IS_AUTHENTICATED_FULLY }
        - { path: ^/api/articles,   methods: [POST, PUT, PATCH],         roles: ROLE_WRITER }
        - { path: ^/api/articles,   methods: [DELETE],                   roles: ROLE_ADMIN }
        - { path: ^/api/categories, methods: [GET],                      roles: IS_AUTHENTICATED_FULLY }
        - { path: ^/api/categories, methods: [POST, PUT, PATCH, DELETE], roles: ROLE_ADMIN }

    role_hierarchy:
        ROLE_WRITER: ROLE_USER
        ROLE_ADMIN:  [ROLE_WRITER, ROLE_USER]
```

---

## Étape 10 — AuthController (login + register)

```php
// src/Controller/Api/AuthController.php
<?php

namespace App\Controller\Api;

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

    // POST /api/auth/login — géré par Lexik JWT via security.yaml
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Lexik JWT intercepte avant d'arriver ici
        throw new \LogicException('Géré par Lexik JWT.');
    }

    // POST /api/auth/register
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Vérifier unicité
        if ($this->userRepository->findOneBy(['email' => $data['email'] ?? ''])) {
            return $this->json(['errors' => ['email' => 'Email déjà utilisé']], Response::HTTP_CONFLICT);
        }
        if ($this->userRepository->findOneBy(['username' => $data['username'] ?? ''])) {
            return $this->json(['errors' => ['username' => 'Username déjà utilisé']], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setLastName($data['lastName'] ?? '');
        $user->setFirstName($data['firstName'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setUsername($data['username'] ?? '');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $data['password'] ?? '')
        );

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $formatted = [];
            foreach ($errors as $error) {
                $formatted[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $formatted], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, Response::HTTP_CREATED, [], ['groups' => ['user:read']]);
    }

    // GET /api/auth/me
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return $this->json($this->getUser(), Response::HTTP_OK, [], ['groups' => ['user:read']]);
    }
}
```

---

## Étape 11 — DTOs

### `ArticleDTO` (POST + PUT)
```php
// src/DTO/ArticleDTO.php
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
    #[Assert\Choice(choices: ['draft', 'published'], message: 'Statut invalide')]
    public ?string $status = null;

    #[Assert\NotBlank(message: 'L\'auteur est obligatoire')]
    public ?int $authorId = null;

    public ?int $categoryId = null;
}
```

### `ArticlePatchDTO` (PATCH)
```php
// src/DTO/ArticlePatchDTO.php
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

    #[Assert\Choice(choices: ['draft', 'published'], message: 'Statut invalide')]
    public ?string $status = null;

    public ?int $authorId = null;

    public ?int $categoryId = null;
}
```

---

## Étape 12 — Controller API Article

```php
// src/Controller/Api/ArticleController.php
<?php

namespace App\Controller\Api;

use App\DTO\ArticleDTO;
use App\DTO\ArticlePatchDTO;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/articles', name: 'api_article_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ArticleRepository $articleRepository,
        private UserRepository $userRepository,
        private CategoryRepository $categoryRepository,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    // ── GET /api/articles ──────────────────────
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $articles = $this->articleRepository->findAll();
        return $this->json($articles, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ── GET /api/articles/{id} ─────────────────
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }
        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ── POST /api/articles ─────────────────────
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize($request->getContent(), ArticleDTO::class, 'json');

        // 1. Valider le DTO
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 2. Vérifier l'auteur
        $author = $this->userRepository->find($dto->authorId);
        if (!$author) {
            return $this->json(['errors' => ['authorId' => 'Auteur non trouvé']], Response::HTTP_NOT_FOUND);
        }

        // 3. Vérifier la catégorie (optionnelle)
        $category = null;
        if ($dto->categoryId) {
            $category = $this->categoryRepository->find($dto->categoryId);
            if (!$category) {
                return $this->json(['errors' => ['categoryId' => 'Catégorie non trouvée']], Response::HTTP_NOT_FOUND);
            }
        }

        // 4. Construire l'entité
        $article = new Article();
        $this->mapDtoToArticle($dto, $article, $author, $category);

        // 5. Valider l'entité (UniqueEntity slug)
        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_CONFLICT);
        }

        $this->em->persist($article);
        $this->em->flush();

        return $this->json($article, Response::HTTP_CREATED, [], ['groups' => ['article:read']]);
    }

    // ── PUT /api/articles/{id} ─────────────────
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $dto    = $this->serializer->deserialize($request->getContent(), ArticleDTO::class, 'json');
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $author = $this->userRepository->find($dto->authorId);
        if (!$author) {
            return $this->json(['errors' => ['authorId' => 'Auteur non trouvé']], Response::HTTP_NOT_FOUND);
        }

        $category = $dto->categoryId ? $this->categoryRepository->find($dto->categoryId) : null;

        $this->mapDtoToArticle($dto, $article, $author, $category);

        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_CONFLICT);
        }

        $this->em->flush();
        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ── PATCH /api/articles/{id} ───────────────
    #[Route('/{id}', name: 'patch', methods: ['PATCH'])]
    public function patch(int $id, Request $request): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $dto  = $this->serializer->deserialize($request->getContent(), ArticlePatchDTO::class, 'json');
        $data = json_decode($request->getContent(), true);

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (array_key_exists('title', $data))       $article->setTitle($dto->title);
        if (array_key_exists('content', $data))     $article->setContent($dto->content);
        if (array_key_exists('slug', $data))        $article->setSlug($dto->slug);
        if (array_key_exists('image', $data))       $article->setImage($dto->image);
        if (array_key_exists('status', $data))      $article->setStatus($dto->status);
        if (array_key_exists('publishedAt', $data)) $article->setPublishedAt(new \DateTime($dto->publishedAt));
        if (array_key_exists('updatedAt', $data))   $article->setUpdatedAt($dto->updatedAt ? new \DateTime($dto->updatedAt) : null);

        if (array_key_exists('authorId', $data)) {
            $author = $this->userRepository->find($dto->authorId);
            if (!$author) {
                return $this->json(['errors' => ['authorId' => 'Auteur non trouvé']], Response::HTTP_NOT_FOUND);
            }
            $article->setAuthor($author);
        }

        if (array_key_exists('categoryId', $data)) {
            $category = $dto->categoryId ? $this->categoryRepository->find($dto->categoryId) : null;
            if ($dto->categoryId && !$category) {
                return $this->json(['errors' => ['categoryId' => 'Catégorie non trouvée']], Response::HTTP_NOT_FOUND);
            }
            $article->setCategory($category);
        }

        $errors = $this->validator->validate($article);
        if (count($errors) > 0) {
            return $this->json(['errors' => $this->formatErrors($errors)], Response::HTTP_CONFLICT);
        }

        $this->em->flush();
        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    // ── DELETE /api/articles/{id} ──────────────
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $article = $this->articleRepository->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $this->em->remove($article);
        $this->em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    // ── Helpers ────────────────────────────────
    private function mapDtoToArticle(ArticleDTO $dto, Article $article, $author, $category): void
    {
        $article->setTitle($dto->title);
        $article->setContent($dto->content);
        $article->setPublishedAt(new \DateTime($dto->publishedAt));
        $article->setUpdatedAt($dto->updatedAt ? new \DateTime($dto->updatedAt) : null);
        $article->setSlug($dto->slug);
        $article->setImage($dto->image);
        $article->setStatus($dto->status);
        $article->setAuthor($author);
        $article->setCategory($category);
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
```

---

## Étape 13 — Tester avec Curl

```bash
# 1. S'inscrire
curl -k -X POST https://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "lastName": "Admin",
    "firstName": "Super",
    "email": "admin@test.com",
    "username": "admin",
    "password": "password123"
  }'

# 2. Se connecter → récupérer le token
curl -k -X POST https://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin", "password": "password123"}'

# 3. Stocker le token
TOKEN="eyJ0eXAiOiJKV1Qi..."

# 4. Lister les articles
curl -k -X GET https://localhost/api/articles \
  -H "Authorization: Bearer $TOKEN"

# 5. Créer un article
curl -k -X POST https://localhost/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mon premier article",
    "content": "Contenu de l article...",
    "publishedAt": "2026-04-24 10:00:00",
    "slug": "mon-premier-article",
    "status": "draft",
    "authorId": 1,
    "categoryId": null
  }'

# 6. Modifier partiellement
curl -k -X PATCH https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "published"}'

# 7. Supprimer
curl -k -X DELETE https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN"
```

---

## Récapitulatif des codes HTTP

| Code | Signification | Quand |
|---|---|---|
| `200` | OK | Lecture / mise à jour réussie |
| `201` | Created | Création réussie |
| `204` | No Content | Suppression réussie |
| `401` | Unauthorized | Token manquant ou invalide |
| `403` | Forbidden | Rôle insuffisant |
| `404` | Not Found | Ressource introuvable |
| `409` | Conflict | Slug / username / email déjà existant |
| `422` | Unprocessable Entity | Erreur de validation du DTO |

---

## Récapitulatif des routes

| Méthode | Route | Rôle | Description |
|---|---|---|---|
| `POST` | `/api/auth/login` | Public | Récupérer un token JWT |
| `POST` | `/api/auth/register` | Public | Créer un compte |
| `GET` | `/api/auth/me` | Connecté | Infos de l'utilisateur connecté |
| `GET` | `/api/articles` | `ROLE_USER` | Lister les articles |
| `GET` | `/api/articles/{id}` | `ROLE_USER` | Afficher un article |
| `POST` | `/api/articles` | `ROLE_WRITER` | Créer un article |
| `PUT` | `/api/articles/{id}` | `ROLE_WRITER` | Mettre à jour complètement |
| `PATCH` | `/api/articles/{id}` | `ROLE_WRITER` | Mettre à jour partiellement |
| `DELETE` | `/api/articles/{id}` | `ROLE_ADMIN` | Supprimer un article |

---

## ⚠️ Pièges courants

| Problème | Cause | Solution |
|---|---|---|
| `no supporting normalizer found` | Serializer pack manquant | `composer require symfony/serializer-pack` |
| `circular reference detected` | `#[Groups]` sur une `OneToMany` | Retirer `#[Groups]` des collections |
| `[] [] []` en réponse | Mauvais import `Groups` | Utiliser `Symfony\Component\Serializer\Attribute\Groups` |
| `401 Invalid credentials` | `getUserIdentifier()` ≠ `property` dans security.yaml | Aligner les deux sur `username` ou `email` |
| `409 Conflict` | Slug / email / username déjà existant | Valider l'entité avec `$validator->validate($entity)` |
| `groups` dans les headers | 3ème paramètre au lieu du 4ème dans `$this->json()` | `$this->json($data, 200, [], ['groups' => [...]])` |
