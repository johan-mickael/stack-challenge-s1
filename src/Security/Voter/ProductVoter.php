<?php

namespace App\Security\Voter;

use App\Entity\Product;
use App\Security\Roles\IUserRole;
use function PHPUnit\Framework\matches;
use App\Security\Acl\Product\UserCanEditProduct;
use App\Security\Acl\Product\UserCanListProduct;
use App\Security\Acl\Product\UserCanReadProduct;
use App\Security\Acl\Product\UserCanCreateProduct;
use App\Security\Acl\Product\UserCanDeleteProduct;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVoter extends Voter
{
    public const VIEW = 'view';
    public const CREATE = 'add';
    public const READ = 'read';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [
            self::VIEW,
            self::CREATE,
        ])) {
            return true;
        }

        if (!$subject instanceof Product) {
            return false;
        }

        if (in_array($attribute, [
            self::READ,
            self::EDIT,
            self::DELETE,
        ])) {
            return true;
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof UserInterface) {
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user),
            self::CREATE => $this->canCreate($user),
            self::READ => $this->canRead($user, $subject),
            self::EDIT => $this->canEdit($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            default => false,
        };
    }

    private function canView(UserInterface $user): bool
    {
        return (new UserCanListProduct())->isSatisfiedBy($user);
    }

    private function canCreate(UserInterface $user): bool
    {
        return (new UserCanCreateProduct())->isSatisfiedBy($user);
    }

    private function canRead(UserInterface $user, Product $product): bool
    {
        return (new UserCanReadProduct($product))->isSatisfiedBy($user);
    }

    private function canEdit(UserInterface $user, Product $product): bool
    {
        return (new UserCanEditProduct($product))->isSatisfiedBy($user);
    }

    private function canDelete(UserInterface $user, Product $product): bool
    {
        return (new UserCanDeleteProduct($product))->isSatisfiedBy($user);
    }
}