# 🧪 Tests Symfony avec PHPUnit

Les tests sont le filet de sécurité de ton application 🕸️
Ils attrapent les bugs avant qu’ils ne s’échappent en production.

---

# 📦 Installation de PHPUnit

Dans Symfony :

```bash
composer require --dev symfony/test-pack
```

👉 Cela installe :

* PHPUnit
* les outils Symfony pour tester (WebTestCase, KernelTestCase…)

---

# ⚙️ Configuration

Fichier principal :

```
phpunit.xml.dist
```

Exemple minimal :

```xml
<phpunit bootstrap="vendor/autoload.php">
    <php>
        <env name="APP_ENV" value="test"/>
    </php>
</phpunit>
```

👉 Important :

* environnement isolé (`test`)
* config dédiée dans `.env.test`

---

# ▶️ Lancer les tests

Tous les tests :

```bash
php bin/phpunit
```

Un fichier spécifique :

```bash
php bin/phpunit tests/Entity/ArticleTest.php
```

---

# 🧱 Tests unitaires (Entity)

👉 Objectif : tester une classe sans dépendances externes

---

## 📌 Exemple : ArticleTest

```php
<?php

namespace App\Tests\Entity;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class ArticleTest extends TestCase
{
    private Article $article;

    protected function setUp(): void
    {
        $this->article = new Article();
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->article->getId());
        $this->assertNull($this->article->getTitle());
        $this->assertNull($this->article->getContent());
        $this->assertNull($this->article->getSlug());
        $this->assertNull($this->article->getStatus());
        $this->assertNull($this->article->getImage());
        $this->assertNull($this->article->getPublishedAt());
        $this->assertNull($this->article->getUpdatedAt());
        $this->assertNull($this->article->getAuthor());
        $this->assertNull($this->article->getCategory());
        $this->assertCount(0, $this->article->getComments());
    }

    public function testSetAndGetTitle(): void
    {
        $this->article->setTitle('Mon article');
        $this->assertEquals('Mon article', $this->article->getTitle());
    }

    public function testSlugFormat(): void
    {
        $this->article->setSlug('mon-super-article-2026');

        $this->assertMatchesRegularExpression(
            '/^[a-z0-9]+(-[a-z0-9]+)*$/',
            $this->article->getSlug()
        );
    }

    public function testAddComment(): void
    {
        $comment = new Comment();

        $this->article->addComment($comment);

        $this->assertCount(1, $this->article->getComments());
        $this->assertTrue($this->article->getComments()->contains($comment));
    }
}
```

---

# 🌐 Tests fonctionnels / API

👉 Objectif : tester ton API avec HTTP + sécurité + base de données

---

## 📌 Exemple : ArticleControllerTest

```php
<?php

namespace App\Tests\Controller\Api;

use App\Entity\Article;
use App\Entity\Category;
use App\Tests\ApiTestCase;

class ArticleControllerTest extends ApiTestCase
{
    private string $adminToken;
    private int $articleId;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = $this->createTestUser(
            'admin@test.com',
            'admin',
            'password',
            ['ROLE_ADMIN']
        );

        $category = new Category();
        $category->setName('Test');
        $this->em->persist($category);
        $this->em->flush();

        $article = new Article();
        $article->setTitle('Article test');
        $article->setSlug('article-' . uniqid());
        $article->setStatus('published');
        $article->setAuthor($admin);
        $article->setCategory($category);

        $this->em->persist($article);
        $this->em->flush();

        $this->articleId = $article->getId();
        $this->adminToken = $this->getToken('admin', 'password');
    }

    public function testGetArticlesAuthenticated(): void
    {
        $this->authenticatedRequest('GET', '/api/articles', $this->adminToken);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testCreateArticleAsAdmin(): void
    {
        $this->authenticatedRequest(
            'POST',
            '/api/articles',
            $this->adminToken,
            [
                'title' => 'Nouvel article',
                'slug'  => 'article-' . uniqid(),
                'status'=> 'draft'
            ]
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testDeleteArticleAsAdmin(): void
    {
        $this->authenticatedRequest(
            'DELETE',
            '/api/articles/' . $this->articleId,
            $this->adminToken
        );

        $this->assertResponseStatusCodeSame(204);
    }
}
```

---

# 🧠 Bonnes pratiques

* ✅ 1 test = 1 comportement
* ✅ noms explicites
* ✅ tests indépendants
* ✅ utiliser `setUp()`

---

# 🧪 Types de tests Symfony

| Type        | Classe         | Usage         |
| ----------- | -------------- | ------------- |
| Unitaire    | TestCase       | logique pure  |
| Fonctionnel | WebTestCase    | HTTP          |
| API         | ApiTestCase    | REST          |
| Intégration | KernelTestCase | services + DB |

---

# ⚡ Commandes utiles

### Reset base de test

```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

### Lancer un test précis

```bash
php bin/phpunit --filter ArticleTest
```

---

# 🎯 Conclusion

* 🧱 Tests unitaires → rapides
* 🌐 Tests API → réalistes
* ⚖️ Ensemble → application robuste

Ton code devient une forteresse 🏰
chaque test est une sentinelle qui veille.
