<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_REFERENCE = 'category_';

    // Catégories prédéfinies pour éviter les doublons
    private const CATEGORIES = [
        ['name' => 'Technologie',   'description' => 'Articles sur la tech, le dev et l\'innovation'],
        ['name' => 'Science',       'description' => 'Découvertes scientifiques et recherches'],
        ['name' => 'Culture',       'description' => 'Art, musique, cinéma et littérature'],
        ['name' => 'Sport',         'description' => 'Actualités sportives et analyses'],
        ['name' => 'Politique',     'description' => 'Actualités politiques nationales et internationales'],
        ['name' => 'Économie',      'description' => 'Finance, marchés et économie mondiale'],
        ['name' => 'Santé',         'description' => 'Bien-être, médecine et santé publique'],
        ['name' => 'Environnement', 'description' => 'Écologie, climat et développement durable'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CATEGORIES as $i => $data) {
            $category = new Category();
            $category->setName($data['name']);
            $category->setDescription($data['description']);

            $manager->persist($category);

            // ✅ Référence pour les autres fixtures
            $this->addReference(self::CATEGORY_REFERENCE . $i, $category);
        }

        $manager->flush();
    }
}
