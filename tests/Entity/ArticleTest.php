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

    // ════════════════════════════════════════════
    // Valeurs par défaut
    // ════════════════════════════════════════════

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

    // ════════════════════════════════════════════
    // Title
    // ════════════════════════════════════════════

    public function testSetAndGetTitle(): void
    {
        $this->article->setTitle('Mon article');
        $this->assertEquals('Mon article', $this->article->getTitle());
    }

    public function testSetTitleReturnsStatic(): void
    {
        $result = $this->article->setTitle('Test');
        $this->assertInstanceOf(Article::class, $result);
    }

    // ════════════════════════════════════════════
    // Content
    // ════════════════════════════════════════════

    public function testSetAndGetContent(): void
    {
        $this->article->setContent('Contenu de l\'article');
        $this->assertEquals('Contenu de l\'article', $this->article->getContent());
    }

    public function testSetContentReturnsStatic(): void
    {
        $result = $this->article->setContent('Contenu');
        $this->assertInstanceOf(Article::class, $result);
    }

    // ════════════════════════════════════════════
    // Slug
    // ════════════════════════════════════════════

    public function testSetAndGetSlug(): void
    {
        $this->article->setSlug('mon-article');
        $this->assertEquals('mon-article', $this->article->getSlug());
    }

    public function testSlugFormat(): void
    {
        $this->article->setSlug('mon-super-article-2026');
        $this->assertMatchesRegularExpression(
            '/^[a-z0-9]+(-[a-z0-9]+)*$/',
            $this->article->getSlug()
        );
    }

    // ════════════════════════════════════════════
    // Status
    // ════════════════════════════════════════════

    public function testSetAndGetStatusDraft(): void
    {
        $this->article->setStatus('draft');
        $this->assertEquals('draft', $this->article->getStatus());
    }

    public function testSetAndGetStatusPublished(): void
    {
        $this->article->setStatus('published');
        $this->assertEquals('published', $this->article->getStatus());
    }

    public function testIsDraft(): void
    {
        $this->article->setStatus('draft');
        $this->assertTrue($this->article->getStatus() === 'draft');
        $this->assertFalse($this->article->getStatus() === 'published');
    }

    public function testIsPublished(): void
    {
        $this->article->setStatus('published');
        $this->assertTrue($this->article->getStatus() === 'published');
        $this->assertFalse($this->article->getStatus() === 'draft');
    }

    // ════════════════════════════════════════════
    // Image
    // ════════════════════════════════════════════

    public function testSetAndGetImage(): void
    {
        $this->article->setImage('https://example.com/image.jpg');
        $this->assertEquals('https://example.com/image.jpg', $this->article->getImage());
    }

    public function testSetImageNullable(): void
    {
        $this->article->setImage(null);
        $this->assertNull($this->article->getImage());
    }

    // ════════════════════════════════════════════
    // PublishedAt
    // ════════════════════════════════════════════

    public function testSetAndGetPublishedAt(): void
    {
        $date = new \DateTime('2026-04-23 10:00:00');
        $this->article->setPublishedAt($date);
        $this->assertEquals($date, $this->article->getPublishedAt());
    }

    public function testPublishedAtIsDateTime(): void
    {
        $date = new \DateTime();
        $this->article->setPublishedAt($date);
        $this->assertInstanceOf(\DateTime::class, $this->article->getPublishedAt());
    }

    // ════════════════════════════════════════════
    // UpdatedAt
    // ════════════════════════════════════════════

    public function testSetAndGetUpdatedAt(): void
    {
        $date = new \DateTime('2026-04-24 12:00:00');
        $this->article->setUpdatedAt($date);
        $this->assertEquals($date, $this->article->getUpdatedAt());
    }

    public function testSetUpdatedAtNullable(): void
    {
        $this->article->setUpdatedAt(null);
        $this->assertNull($this->article->getUpdatedAt());
    }

    public function testUpdatedAtAfterPublishedAt(): void
    {
        $publishedAt = new \DateTime('2026-04-23 10:00:00');
        $updatedAt   = new \DateTime('2026-04-24 10:00:00');

        $this->article->setPublishedAt($publishedAt);
        $this->article->setUpdatedAt($updatedAt);

        $this->assertGreaterThan(
            $this->article->getPublishedAt(),
            $this->article->getUpdatedAt()
        );
    }

    // ════════════════════════════════════════════
    // Author
    // ════════════════════════════════════════════

    public function testSetAndGetAuthor(): void
    {
        $user = new User();
        $user->setUsername('john');

        $this->article->setAuthor($user);
        $this->assertSame($user, $this->article->getAuthor());
    }

    public function testSetAuthorNullable(): void
    {
        $this->article->setAuthor(null);
        $this->assertNull($this->article->getAuthor());
    }

    public function testAuthorIsUserInstance(): void
    {
        $user = new User();
        $this->article->setAuthor($user);
        $this->assertInstanceOf(User::class, $this->article->getAuthor());
    }

    // ════════════════════════════════════════════
    // Category
    // ════════════════════════════════════════════

    public function testSetAndGetCategory(): void
    {
        $category = new Category();
        $category->setName('Technologie');

        $this->article->setCategory($category);
        $this->assertSame($category, $this->article->getCategory());
    }

    public function testSetCategoryNullable(): void
    {
        $this->article->setCategory(null);
        $this->assertNull($this->article->getCategory());
    }

    public function testCategoryIsCategoryInstance(): void
    {
        $category = new Category();
        $this->article->setCategory($category);
        $this->assertInstanceOf(Category::class, $this->article->getCategory());
    }

    // ════════════════════════════════════════════
    // Comments
    // ════════════════════════════════════════════

    public function testAddComment(): void
    {
        $comment = new Comment();

        $this->article->addComment($comment);

        $this->assertCount(1, $this->article->getComments());
        $this->assertTrue($this->article->getComments()->contains($comment));
    }

    public function testAddMultipleComments(): void
    {
        $comment1 = new Comment();
        $comment2 = new Comment();
        $comment3 = new Comment();

        $this->article->addComment($comment1);
        $this->article->addComment($comment2);
        $this->article->addComment($comment3);

        $this->assertCount(3, $this->article->getComments());
    }

    public function testAddSameCommentTwice(): void
    {
        $comment = new Comment();

        $this->article->addComment($comment);
        $this->article->addComment($comment); // ← doublon

        // ✅ Ne doit pas être ajouté deux fois
        $this->assertCount(1, $this->article->getComments());
    }

    public function testRemoveComment(): void
    {
        $comment = new Comment();

        $this->article->addComment($comment);
        $this->assertCount(1, $this->article->getComments());

        $this->article->removeComment($comment);
        $this->assertCount(0, $this->article->getComments());
    }

    public function testRemoveCommentSetsArticleNull(): void
    {
        $comment = new Comment();

        $this->article->addComment($comment);
        $this->article->removeComment($comment);

        // ✅ La relation inverse est bien nettoyée
        $this->assertNull($comment->getArticle());
    }

    public function testAddCommentSetsArticle(): void
    {
        $comment = new Comment();
        $this->article->addComment($comment);

        // ✅ La relation inverse est bien définie
        $this->assertSame($this->article, $comment->getArticle());
    }

    // ════════════════════════════════════════════
    // Cohérence globale
    // ════════════════════════════════════════════

    public function testFullArticle(): void
    {
        $user     = new User();
        $category = new Category();
        $comment  = new Comment();

        $user->setUsername('author');
        $category->setName('Tech');

        $publishedAt = new \DateTime('2026-04-23');
        $updatedAt   = new \DateTime('2026-04-24');

        $this->article->setTitle('Mon article complet');
        $this->article->setContent('Contenu complet de l\'article');
        $this->article->setSlug('mon-article-complet');
        $this->article->setStatus('published');
        $this->article->setImage('https://example.com/img.jpg');
        $this->article->setPublishedAt($publishedAt);
        $this->article->setUpdatedAt($updatedAt);
        $this->article->setAuthor($user);
        $this->article->setCategory($category);
        $this->article->addComment($comment);

        $this->assertEquals('Mon article complet',             $this->article->getTitle());
        $this->assertEquals('Contenu complet de l\'article',   $this->article->getContent());
        $this->assertEquals('mon-article-complet',             $this->article->getSlug());
        $this->assertEquals('published',                       $this->article->getStatus());
        $this->assertEquals('https://example.com/img.jpg',     $this->article->getImage());
        $this->assertEquals($publishedAt,                      $this->article->getPublishedAt());
        $this->assertEquals($updatedAt,                        $this->article->getUpdatedAt());
        $this->assertSame($user,                               $this->article->getAuthor());
        $this->assertSame($category,                           $this->article->getCategory());
        $this->assertCount(1,                                  $this->article->getComments());
    }
}
