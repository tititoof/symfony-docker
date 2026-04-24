<?php

namespace App\Tests\Controller\Api;

use App\Entity\Article;
use App\Entity\Category;
use App\Tests\ApiTestCase;

class ArticleControllerTest extends ApiTestCase
{
    private string $adminToken;
    private string $writerToken;
    private string $userToken;
    private int $articleId;
    private int $authorId;
    private int $categoryId;

    protected function setUp(): void
    {
        parent::setUp();

        // ✅ Créer les users de test
        $admin  = $this->createTestUser('admin_test@test.com',  'admin_test',  'password123', ['ROLE_ADMIN']);
        $writer = $this->createTestUser('writer_test@test.com', 'writer_test', 'password123', ['ROLE_WRITER']);
        $user   = $this->createTestUser('user_test@test.com',   'user_test',   'password123', ['ROLE_USER']);

        $this->authorId = $admin->getId();

        // ✅ Créer une catégorie de test
        $category = new Category();
        $category->setName('Category Test ' . uniqid());
        $category->setDescription('Description test');
        $this->em->persist($category);
        $this->em->flush();
        $this->categoryId = $category->getId();

        // ✅ Créer un article de test
        $article = new Article();
        $article->setTitle('Article de test');
        $article->setContent('Contenu de test');
        $article->setSlug('article-de-test-' . uniqid());
        $article->setStatus('published');
        $article->setPublishedAt(new \DateTime());
        $article->setAuthor($admin);
        $article->setCategory($category);
        $this->em->persist($article);
        $this->em->flush();
        $this->articleId = $article->getId();

        // ✅ Récupérer les tokens
        $this->adminToken  = $this->getToken('admin_test',  'password123');
        $this->writerToken = $this->getToken('writer_test', 'password123');
        $this->userToken   = $this->getToken('user_test',   'password123');
    }

    // ════════════════════════════════════════════
    // GET /api/articles
    // ════════════════════════════════════════════

    public function testGetArticlesAuthenticated(): void
    {
        $this->authenticatedRequest('GET', '/api/articles', $this->adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
    }

    public function testGetArticlesUnauthenticated(): void
    {
        $this->client->request('GET', '/api/articles');

        $this->assertResponseStatusCodeSame(401);
    }

    // ════════════════════════════════════════════
    // GET /api/articles/{id}
    // ════════════════════════════════════════════

    public function testGetOneArticleSuccess(): void
    {
        $this->authenticatedRequest('GET', '/api/articles/' . $this->articleId, $this->adminToken);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id',     $data);
        $this->assertArrayHasKey('title',  $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('author', $data);
        $this->assertEquals($this->articleId, $data['id']);
    }

    public function testGetOneArticleNotFound(): void
    {
        $this->authenticatedRequest('GET', '/api/articles/99999', $this->adminToken);

        $this->assertResponseStatusCodeSame(404);
    }

    // ════════════════════════════════════════════
    // POST /api/articles
    // ════════════════════════════════════════════

    public function testCreateArticleAsAdmin(): void
    {
        $this->authenticatedRequest(
            'POST',
            '/api/articles',
            $this->adminToken,
            [
                'title'      => 'Nouvel article',
                'content'    => 'Contenu du nouvel article',
                'publishedAt'=> '2026-04-23 10:00:00',
                'slug'       => 'nouvel-article-' . uniqid(),
                'status'     => 'draft',
                'authorId'   => $this->authorId,
                'categoryId' => $this->categoryId,
            ]
        );

        $this->assertResponseStatusCodeSame(201);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id',    $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals('Nouvel article', $data['title']);
        $this->assertEquals('draft',          $data['status']);
    }

    public function testCreateArticleAsWriter(): void
    {
        $this->authenticatedRequest(
            'POST',
            '/api/articles',
            $this->writerToken,
            [
                'title'      => 'Article writer',
                'content'    => 'Contenu writer',
                'publishedAt'=> '2026-04-23 10:00:00',
                'slug'       => 'article-writer-' . uniqid(),
                'status'     => 'draft',
                'authorId'   => $this->authorId,
            ]
        );

        $this->assertResponseStatusCodeSame(201);
    }

    public function testCreateArticleAsUserForbidden(): void
    {
        $this->authenticatedRequest(
            'POST',
            '/api/articles',
            $this->userToken,
            [
                'title'      => 'Article interdit',
                'content'    => 'Contenu interdit',
                'publishedAt'=> '2026-04-23 10:00:00',
                'slug'       => 'article-interdit-' . uniqid(),
                'status'     => 'draft',
                'authorId'   => $this->authorId,
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateArticleValidationError(): void
    {
        $this->authenticatedRequest(
            'POST',
            '/api/articles',
            $this->adminToken,
            [
                // ❌ title manquant
                'content' => 'Contenu sans titre',
                'slug'    => 'sans-titre-' . uniqid(),
                'status'  => 'draft',
            ]
        );

        $this->assertResponseStatusCodeSame(422);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);
    }

    public function testCreateArticleInvalidStatus(): void
    {
        $this->authenticatedRequest(
            'POST',
            '/api/articles',
            $this->adminToken,
            [
                'title'      => 'Article statut invalide',
                'content'    => 'Contenu',
                'publishedAt'=> '2026-04-23 10:00:00',
                'slug'       => 'statut-invalide-' . uniqid(),
                'status'     => 'invalid_status', // ❌ invalide
                'authorId'   => $this->authorId,
            ]
        );

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateArticleDuplicateSlug(): void
    {
        $slug = 'slug-duplique-' . uniqid();

        // Premier article
        $this->authenticatedRequest('POST', '/api/articles', $this->adminToken, [
            'title'      => 'Premier',
            'content'    => 'Contenu',
            'publishedAt'=> '2026-04-23 10:00:00',
            'slug'       => $slug,
            'status'     => 'draft',
            'authorId'   => $this->authorId,
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Deuxième article avec le même slug
        $this->authenticatedRequest('POST', '/api/articles', $this->adminToken, [
            'title'      => 'Deuxième',
            'content'    => 'Contenu',
            'publishedAt'=> '2026-04-23 10:00:00',
            'slug'       => $slug, // ❌ slug dupliqué
            'status'     => 'draft',
            'authorId'   => $this->authorId,
        ]);
        $this->assertResponseStatusCodeSame(409);
    }

    // ════════════════════════════════════════════
    // PUT /api/articles/{id}
    // ════════════════════════════════════════════

    public function testUpdateArticleAsAdmin(): void
    {
        $this->authenticatedRequest(
            'PUT',
            '/api/articles/' . $this->articleId,
            $this->adminToken,
            [
                'title'      => 'Titre modifié',
                'content'    => 'Contenu modifié',
                'publishedAt'=> '2026-04-23 10:00:00',
                'slug'       => 'titre-modifie-' . uniqid(),
                'status'     => 'published',
                'authorId'   => $this->authorId,
            ]
        );

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Titre modifié', $data['title']);
    }

    public function testUpdateArticleNotFound(): void
    {
        $this->authenticatedRequest(
            'PUT',
            '/api/articles/99999',
            $this->adminToken,
            [
                'title'      => 'Titre modifié',
                'content'    => 'Contenu modifié',
                'publishedAt'=> '2026-04-23 10:00:00',
                'slug'       => 'titre-modifie-' . uniqid(),
                'status'     => 'published',
                'authorId'   => $this->authorId,
            ]
        );

        $this->assertResponseStatusCodeSame(404);
    }

    // ════════════════════════════════════════════
    // PATCH /api/articles/{id}
    // ════════════════════════════════════════════

    public function testPatchArticleStatus(): void
    {
        $this->authenticatedRequest(
            'PATCH',
            '/api/articles/' . $this->articleId,
            $this->adminToken,
            ['status' => 'draft']
        );

        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('draft', $data['status']);
    }

    public function testPatchArticleInvalidStatus(): void
    {
        $this->authenticatedRequest(
            'PATCH',
            '/api/articles/' . $this->articleId,
            $this->adminToken,
            ['status' => 'invalide']
        );

        $this->assertResponseStatusCodeSame(422);
    }

    // ════════════════════════════════════════════
    // DELETE /api/articles/{id}
    // ════════════════════════════════════════════

    public function testDeleteArticleAsAdmin(): void
    {
        $this->authenticatedRequest('DELETE', '/api/articles/' . $this->articleId, $this->adminToken);

        $this->assertResponseStatusCodeSame(204);

        // Vérifier que l'article n'existe plus
        $this->authenticatedRequest('GET', '/api/articles/' . $this->articleId, $this->adminToken);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDeleteArticleAsWriterForbidden(): void
    {
        $this->authenticatedRequest('DELETE', '/api/articles/' . $this->articleId, $this->writerToken);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteArticleAsUserForbidden(): void
    {
        $this->authenticatedRequest('DELETE', '/api/articles/' . $this->articleId, $this->userToken);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteArticleNotFound(): void
    {
        $this->authenticatedRequest('DELETE', '/api/articles/99999', $this->adminToken);

        $this->assertResponseStatusCodeSame(404);
    }
}
