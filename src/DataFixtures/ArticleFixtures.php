<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $statusList   = ['draft', 'published'];
        $nbUsers      = 10;
        $nbCategories = 8;

        for ($i = 0; $i < 30; $i++) {
            $article = new Article();

            $title = $faker->sentence(6);

            $article->setTitle($title);
            $article->setContent($faker->paragraphs(5, true));
            $article->setPublishedAt($faker->dateTimeBetween('-1 year', 'now'));
            $article->setUpdatedAt($faker->optional(0.5)->dateTimeBetween('-6 months', 'now'));
            $article->setSlug($this->slugify($title) . '-' . $i); // ✅ slug unique garanti
            $article->setImage($faker->optional(0.7)->imageUrl(800, 400, 'nature'));
            $article->setStatus($faker->randomElement($statusList));

            $author   = $this->getReference(UserFixtures::USER_REFERENCE . rand(0, $nbUsers - 1), \App\Entity\User::class);
            $category = $faker->boolean(80) // 80% des articles ont une catégorie
                ? $this->getReference(CategoryFixtures::CATEGORY_REFERENCE . rand(0, $nbCategories - 1), \App\Entity\Category::class)
                : null;

            $article->setAuthor($author);
            $article->setCategory($category);

            $manager->persist($article);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }

    // Helper : convertit un titre en slug
    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
