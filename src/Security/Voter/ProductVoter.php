<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Service\SecurityChecker;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ProductVoter extends Voter
{
    public const CAN_ADD_PRODUCT = 'CAN_ADD_PRODUCT';

    public function __construct(private Security $security, private RequestStack $requestStack, private SecurityChecker $securityChecker)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::CAN_ADD_PRODUCT], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $role = $this->requestStack->getCurrentRequest()->get('role');
            if (null === $role) {
                return $this->process();
            }

            return $this->securityChecker->isGrantedByRole($role, $attribute, $subject);
        }

        return $this->process();
    }

    private function process()
    {
        if (!$this->security->isGranted('ROLE_ENCODE') && $this->security->isGranted('ROLE_COMMERCIAL')) {
            return true;
        }

        return false;
    }
}
