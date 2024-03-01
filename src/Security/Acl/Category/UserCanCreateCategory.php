<?php

namespace App\Security\Acl\Category;

use App\Security\Acl\AuthorizationInterface;
use App\Security\Roles\IUserRole;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCanCreateCategory implements AuthorizationInterface
{
    public function isSatisfiedBy(UserInterface $user): bool
    {
        foreach ($this->authorizedRoles() as $authorizedRole) {
            if (in_array($authorizedRole, $user->getRoles())) {
                return true;
            }
        }

        return false;
    }

    private function authorizedRoles(): array
    {
        return [
            IUserRole::ROLE_ADMIN,
            IUserRole::ROLE_COMPANY
        ];
    }
}