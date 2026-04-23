<?php

namespace App\Security\Voter;

use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class ArticleVoter extends Voter
{
    const VIEW   = 'view';
    const CREATE = 'create';
    const EDIT   = 'edit';
    const DELETE = 'delete';

    // ────────────────────────────────────────────────────────
    // supports() — définit QUAND ce voter s'applique
    // Appelé pour chaque denyAccessUnlessGranted() / IsGranted()
    // ────────────────────────────────────────────────────────
    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter s'applique uniquement si :
        // 1. L'action est dans notre liste
        // 2. Le sujet est bien un Article
        return in_array($attribute, [self::VIEW, self::CREATE, self::EDIT, self::DELETE])
            && $subject instanceof Article;
    }

    // ────────────────────────────────────────────────────────
    // voteOnAttribute() — définit les règles d'autorisation
    // Appelé uniquement si supports() retourne true
    // ────────────────────────────────────────────────────────
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Article $article */
        $article = $subject;

        return match ($attribute) {
            self::VIEW   => $this->canView($article, $user),
            self::CREATE => $this->canCreate($user),
            self::EDIT   => $this->canEdit($article, $user),
            self::DELETE => $this->canDelete($article, $user),
            default      => false,
        };
    }

    // ────────────────────────────────────────────────────────
    // Règles métier
    // ────────────────────────────────────────────────────────

    private function canView(Article $article, User $user): bool
    {
        // Tout utilisateur connecté peut voir un article publié
        if ($article->getStatus() === 'published') {
            return true;
        }

        // Seul l'auteur ou un admin peut voir un brouillon
        return $this->isOwner($article, $user)
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canCreate(User $user): bool
    {
        // ROLE_WRITER ou ROLE_ADMIN peuvent créer
        return in_array('ROLE_WRITER', $user->getRoles())
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canEdit(Article $article, User $user): bool
    {
        // Admin peut tout modifier
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // ROLE_WRITER peut modifier uniquement ses propres articles
        return $this->isOwner($article, $user)
            && in_array('ROLE_WRITER', $user->getRoles());
    }

    private function canDelete(Article $article, User $user): bool
    {
        // Seul un admin peut supprimer
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    // ────────────────────────────────────────────────────────
    // Helper
    // ────────────────────────────────────────────────────────
    private function isOwner(Article $article, User $user): bool
    {
        return $article->getAuthor() === $user;
    }
}
