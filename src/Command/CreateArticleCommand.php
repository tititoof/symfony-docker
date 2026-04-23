<?php

namespace App\Command;

use App\Repository\CategoryRepository;
use App\Repository\UserRepository;
use App\Service\ArticleService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// php bin/console app:create-article
#[AsCommand(
    name: 'app:create-article',
    description: 'Créer un article interactivement',
)]
class CreateArticleCommand extends Command
{
    public function __construct(
        private ArticleService $articleService,
        private UserRepository $userRepository,
        private CategoryRepository $categoryRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Créer un nouvel article');

        // ── Titre ──────────────────────────────────────
        $title = $io->ask('Titre de l\'article', null, function (?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('Le titre est obligatoire.');
            }
            return $value;
        });

        // ── Contenu ────────────────────────────────────
        $content = $io->ask('Contenu de l\'article', null, function (?string $value): string {
            if (empty($value)) {
                throw new \RuntimeException('Le contenu est obligatoire.');
            }
            return $value;
        });

        // ── Slug ───────────────────────────────────────
        $defaultSlug = $this->articleService->generateSlug($title);
        $slug = $io->ask("Slug de l'article", $defaultSlug);

        // ── Statut ─────────────────────────────────────
        $status = $io->choice(
            'Statut de l\'article',
            ['draft', 'published'],
            'draft'
        );

        // ── Auteur ─────────────────────────────────────
        $users = $this->userRepository->findAll();
        if (empty($users)) {
            $io->error('Aucun utilisateur trouvé. Créez d\'abord un utilisateur.');
            return Command::FAILURE;
        }

        $userChoices = [];
        foreach ($users as $user) {
            $userChoices[$user->getId()] = $user->getUsername() . ' (' . $user->getEmail() . ')';
        }

        $selectedUser = $io->choice('Auteur de l\'article', $userChoices);
        $authorId     = array_search($selectedUser, $userChoices);
        $author       = $this->userRepository->find($authorId);

        // ── Catégorie (optionnelle) ────────────────────
        $category   = null;
        $categories = $this->categoryRepository->findAll();

        if (!empty($categories)) {
            $categoryChoices = ['0' => 'Aucune catégorie'];
            foreach ($categories as $cat) {
                $categoryChoices[$cat->getId()] = $cat->getName();
            }

            $selectedCategory = $io->choice('Catégorie', $categoryChoices, '0');

            if ($selectedCategory !== 'Aucune catégorie') {
                $categoryId = array_search($selectedCategory, $categoryChoices);
                $category   = $this->categoryRepository->find($categoryId);
            }
        }

        // ── Confirmation ───────────────────────────────
        $io->section('Récapitulatif');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Titre',     $title],
                ['Contenu',   substr($content, 0, 50) . '...'],
                ['Slug',      $slug],
                ['Statut',    $status],
                ['Auteur',    $author->getUsername()],
                ['Catégorie', $category?->getName() ?? 'Aucune'],
            ]
        );

        if (!$io->confirm('Confirmer la création ?', true)) {
            $io->warning('Création annulée.');
            return Command::SUCCESS;
        }

        // ── Création ───────────────────────────────────
        try {
            $article = $this->articleService->createArticle(
                title:    $title,
                content:  $content,
                slug:     $slug,
                status:   $status,
                author:   $author,
                category: $category,
            );

            $io->success("Article créé avec succès ! ID : {$article->getId()}");
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
