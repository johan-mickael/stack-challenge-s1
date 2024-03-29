<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Security\Acl\Employee\UserCanCreateEmployee;
use App\Security\Acl\Employee\UserCanDeleteEmployee;
use App\Security\Acl\Employee\UserCanEditEmployee;
use App\Security\Acl\Employee\UserCanListEmployee;
use App\Security\Acl\Employee\UserCanReadEmployee;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class EmployeeVoter extends Voter {
    public const LIST = 'list';
    public const CREATE = 'add';
    public const READ = 'read';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (in_array($attribute, [
            self::LIST,
            self::CREATE,
        ])) {
            return true;
        }

        if (!$subject instanceof User) {
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
            self::LIST => $this->canList($user),
            self::CREATE => $this->canCreate($user),
            self::READ => $this->canRead($user, $subject),
            self::EDIT => $this->canEdit($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
            default => false,
        };
    }

    private function canList(UserInterface $user): bool
    {
        return (new UserCanListEmployee())->isSatisfiedBy($user);
    }

    private function canCreate(UserInterface $user): bool
    {
        return (new UserCanCreateEmployee())->isSatisfiedBy($user);
    }

    private function canRead(UserInterface $user, User $employee): bool
    {
        return (new UserCanReadEmployee($employee))->isSatisfiedBy($user);
    }

    private function canEdit(UserInterface $user, User $employee): bool
    {
        return (new UserCanEditEmployee($employee))->isSatisfiedBy($user);
    }

    private function canDelete(UserInterface $user, User $employee): bool
    {
        return (new UserCanDeleteEmployee($employee))->isSatisfiedBy($user);
    }
}