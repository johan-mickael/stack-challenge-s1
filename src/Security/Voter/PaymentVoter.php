<?php

namespace App\Security\Voter;

use App\Entity\Payment;
use App\Security\Acl\Payment\UserCanCreatePayment;
use App\Security\Acl\Payment\UserCanEditPayment;
use App\Security\Acl\Payment\UserCanListPayment;
use App\Security\Acl\Payment\UserCanDeletePayment;
use App\Security\Acl\Payment\UserCanReadPayment;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class PaymentVoter extends Voter
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

        if (!$subject instanceof Payment) {
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
        return (new UserCanListPayment())->isSatisfiedBy($user);
    }

    private function canCreate(UserInterface $user): bool
    {
        return (new UserCanCreatePayment())->isSatisfiedBy($user);
    }

    private function canRead(UserInterface $user, Payment $payment): bool
    {
        return (new UserCanReadPayment($payment))->isSatisfiedBy($user);
    }

    private function canEdit(UserInterface $user, Payment $payment): bool
    {
        return (new UserCanEditPayment($payment))->isSatisfiedBy($user);
    }

    private function canDelete(UserInterface $user, Payment $payment): bool
    {
        return (new UserCanDeletePayment($payment))->isSatisfiedBy($user);
    }
}
