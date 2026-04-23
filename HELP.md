
## Dépendances installées

### Production
```bash
composer require symfony/orm-pack                        # Doctrine ORM + DQL
composer require symfony/serializer-pack                 # Serializer + PropertyInfo + PropertyAccess
composer require symfony/validator                       # Validation des entités et DTO
composer require symfony/security-bundle                 # Sécurité, pare-feu, rôles
composer require lexik/jwt-authentication-bundle         # Authentification JWT
```

### Développement
```bash
composer require --dev orm-fixtures                      # Fixtures
composer require --dev fakerphp/faker                   # Génération de fausses données
composer require --dev symfony/maker-bundle              # Générateur de code (make:entity, make:controller...)
```

## Entités

```bash
# Créer une entité
php bin/console make:entity NomEntite

# Créer une migration après modification d'entité
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```


## Entités

```bash
# Créer une entité
php bin/console make:entity NomEntite

# Créer une migration après modification d'entité
php bin/console make:migration

# Appliquer les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

## Base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Supprimer la base de données
php bin/console doctrine:database:drop --force

# Reset complet de la base de données
php bin/console doctrine:database:drop --force && \
php bin/console doctrine:database:create && \
php bin/console doctrine:migrations:migrate --no-interaction && \
php bin/console doctrine:fixtures:load --no-interaction
```

## Fixtures

```bash
# Charger les fixtures
php bin/console doctrine:fixtures:load --no-interaction
```

## Sécurité JWT

```bash
# Générer les clés SSL pour JWT
php bin/console lexik:jwt:generate-keypair

# Régénérer les clés SSL (écrase les anciennes)
php bin/console lexik:jwt:generate-keypair --overwrite
```

## Voter

```bash
# Créer un voter
php bin/console make:voter NomVoter
```

## Debug et cache

```bash
# Vider le cache
php bin/console cache:clear

# Lister toutes les routes
php bin/console debug:router

# Filtrer les routes
php bin/console debug:router | grep api

# Lister les services
php bin/console debug:container
```



# Requêtes API

## Connexion

```bash
curl -k -X POST https://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin", "password": "password123"}'
```

## GET — Liste tous les articles

```bash
curl -k -X GET https://localhost/api/articles \
  -H "Authorization: Bearer $TOKEN"
```

## GET — Un article par id

```bash
curl -k -X GET https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN"
```

## POST — Créer un article

```bash
curl -k -X POST https://localhost/api/articles \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mon premier article",
    "content": "Contenu de mon article...",
    "publishedAt": "2026-04-23 10:00:00",
    "slug": "mon-premier-article",
    "status": "draft",
    "authorId": 1,
    "categoryId": 1
  }'
```

## PUT — Mettre à jour complètement

```bash
curl -k -X PUT https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Titre mis à jour",
    "content": "Nouveau contenu complet...",
    "publishedAt": "2026-04-23 10:00:00",
    "updatedAt": "2026-04-23 15:00:00",
    "slug": "titre-mis-a-jour",
    "status": "published",
    "authorId": 1,
    "categoryId": 2
  }'
```


## PATCH — Mettre à jour partiellement

```bash
curl -k -X PATCH https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "published"}'
```

## DELETE — Supprimer un article

```bash
curl -k -X DELETE https://localhost/api/articles/1 \
  -H "Authorization: Bearer $TOKEN"
```
