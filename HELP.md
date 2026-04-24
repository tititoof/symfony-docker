# 📋 Aide-mémoire — Commandes Symfony utilisées

## 📦 Installation du projet

```bash
composer create-project symfony/skeleton my-project
cd my-project
```

---

## 📦 Dépendances installées

### Production
```bash
composer require symfony/orm-pack                    # Doctrine ORM
composer require symfony/serializer-pack             # Serializer + PropertyInfo
composer require symfony/validator                   # Validation
composer require symfony/security-bundle             # Sécurité, pare-feu, rôles
composer require symfony/form                        # Formulaires
composer require symfony/twig-bundle                 # Templates Twig
composer require twig/extra-bundle                   # Extensions Twig
composer require lexik/jwt-authentication-bundle     # Authentification JWT
```

### Développement
```bash
composer require --dev orm-fixtures                  # Fixtures
composer require --dev fakerphp/faker               # Fausses données
composer require --dev symfony/maker-bundle          # Générateur de code
composer require --dev symfony/test-pack             # PHPUnit + WebTestCase
```

---

## 🏗️ Génération de code

```bash
# Créer une entité
php bin/console make:entity NomEntite

# Créer un controller
php bin/console make:controller NomController

# Créer un CRUD complet (controller + formulaire + vues Twig)
php bin/console make:crud NomEntite

# Créer un voter
php bin/console make:voter NomVoter

# Créer un formulaire
php bin/console make:form NomType

# Créer une commande console
php bin/console make:command app:nom-commande
```

---

## 🗄️ Base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Supprimer la base de données
php bin/console doctrine:database:drop --force

# Générer une migration depuis les entités
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Voir le statut des migrations
php bin/console doctrine:migrations:status

# Vérifier les différences entre entités et BDD
php bin/console doctrine:migrations:diff

# Reset complet BDD + migrations + fixtures
php bin/console doctrine:database:drop --force && \
php bin/console doctrine:database:create && \
php bin/console doctrine:migrations:migrate --no-interaction && \
php bin/console doctrine:fixtures:load --no-interaction
```

---

## 🌱 Fixtures

```bash
# Charger les fixtures
php bin/console doctrine:fixtures:load --no-interaction

# Charger les fixtures en environnement test
php bin/console doctrine:fixtures:load --env=test --no-interaction
```

---

## 🔐 Sécurité JWT

```bash
# Générer les clés SSL pour JWT
php bin/console lexik:jwt:generate-keypair

# Régénérer les clés SSL (écrase les anciennes)
php bin/console lexik:jwt:generate-keypair --overwrite
```

---

## 🧪 Tests

```bash
# Préparer la BDD de test
php bin/console doctrine:database:drop --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction

# Lancer tous les tests
php bin/phpunit

# Lancer un fichier de test spécifique
php bin/phpunit tests/Controller/Api/ArticleControllerTest.php

# Lancer avec le détail des tests
php bin/phpunit --testdox

# Lancer un test spécifique par son nom
php bin/phpunit --filter testCreateArticleAsAdmin
```

---

## 🔍 Debug

```bash
# Vider le cache
php bin/console cache:clear

# Lister toutes les routes
php bin/console debug:router

# Filtrer les routes par mot-clé
php bin/console debug:router | grep api
php bin/console debug:router | grep login

# Voir les détails d'une route
php bin/console debug:router api_article_index

# Lister les services disponibles
php bin/console debug:container

# Voir les listeners d'un événement
php bin/console debug:event-dispatcher article.created
php bin/console debug:event-dispatcher article.updated
php bin/console debug:event-dispatcher article.deleted

# Voir l'environnement actuel
php bin/console about

# Voir les paramètres de configuration
php bin/console debug:config
```

---

## 🔑 Variables d'environnement — `.env`

```dotenv
# Base de données
DATABASE_URL="postgresql://user:password@localhost:5432/db_name"

# JWT
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_passphrase
JWT_TTL=3600
```

---

## 🌐 Curl — API REST

```bash
# Login — récupérer un token JWT
curl -k -X POST https://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin", "password": "password123"}'

# Stocker le token dans une variable
TOKEN="eyJ0eXAiOiJKV1Qi..."

# Requête authentifiée
curl -k -X GET https://localhost/api/articles \
  -H "Authorization: Bearer $TOKEN"

# Créer un article
curl -k -X POST https://localhost/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mon article",
    "content": "Contenu...",
    "publishedAt": "2026-04-23 10:00:00",
    "slug": "mon-article",
    "status": "draft",
    "authorId": 1,
    "categoryId": 1
  }'

# Modifier partiellement
curl -k -X PATCH https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "published"}'

# Supprimer
curl -k -X DELETE https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN"
```

---

## 👥 Rôles et hiérarchie

| Rôle | Hérite de | Permissions |
|---|---|---|
| `ROLE_USER` | — | Lecture seule |
| `ROLE_WRITER` | `ROLE_USER` | Créer et modifier ses articles |
| `ROLE_ADMIN` | `ROLE_WRITER`, `ROLE_USER` | Tout gérer |

---

## 🗺️ Routes API

| Méthode | Route | Rôle minimum |
|---|---|---|
| `POST` | `/api/auth/login` | Public |
| `POST` | `/api/auth/register` | Public |
| `GET` | `/api/auth/me` | Connecté |
| `GET` | `/api/articles` | `ROLE_USER` |
| `POST` | `/api/articles` | `ROLE_WRITER` |
| `PUT` | `/api/articles/{id}` | `ROLE_WRITER` (auteur) |
| `PATCH` | `/api/articles/{id}` | `ROLE_WRITER` (auteur) |
| `DELETE` | `/api/articles/{id}` | `ROLE_ADMIN` |
| `GET` | `/api/categories` | `ROLE_USER` |
| `POST` | `/api/categories` | `ROLE_ADMIN` |
| `PUT` | `/api/categories/{id}` | `ROLE_ADMIN` |
| `PATCH` | `/api/categories/{id}` | `ROLE_ADMIN` |
| `DELETE` | `/api/categories/{id}` | `ROLE_ADMIN` |
| `GET` | `/api/users` | `ROLE_USER` |
| `POST` | `/api/users` | `ROLE_ADMIN` |
| `PUT` | `/api/users/{id}` | `ROLE_ADMIN` |
| `PATCH` | `/api/users/{id}` | `ROLE_ADMIN` |
| `DELETE` | `/api/users/{id}` | `ROLE_ADMIN` |

---

## 🌐 Routes Web (Formulaires Twig)

| Méthode | Route | Action | Rôle |
|---|---|---|---|
| `GET` | `/login` | Formulaire de login | Public |
| `GET` | `/logout` | Déconnexion | Connecté |
| `GET` | `/articles` | Liste des articles | Connecté |
| `GET` | `/articles/{id}` | Détail d'un article | Connecté |
| `GET\|POST` | `/articles/new` | Créer un article | `ROLE_WRITER` |
| `GET\|POST` | `/articles/{id}/edit` | Modifier un article | `ROLE_WRITER` |
| `POST` | `/articles/{id}/delete` | Supprimer un article | `ROLE_ADMIN` |

---

## 📊 Codes HTTP retournés

| Code | Signification | Contexte |
|---|---|---|
| `200` | OK | Lecture / mise à jour réussie |
| `201` | Created | Création réussie |
| `204` | No Content | Suppression réussie |
| `401` | Unauthorized | Non authentifié |
| `403` | Forbidden | Accès refusé (rôle insuffisant) |
| `404` | Not Found | Ressource introuvable |
| `409` | Conflict | Doublon (slug, username, email...) |
| `422` | Unprocessable Entity | Erreur de validation |


## Outils

https://www.usebruno.com/

## Info

https://chartman2-fr.ovh

LinkedIn

Christophe Hartmann
https://fr.linkedin.com/in/christophe-hartmann-3a297a42

https://refactoring.guru/fr/design-patterns
