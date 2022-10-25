<?php

namespace App\Security\Voter;

use App\Entity\Config;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class InstallVoter extends Voter
{
    protected function supports($attribute, $subject): bool
    {
        // only vote on `Config` objects
        if (!$subject instanceof Config) {
            return false;
        }

        // only vote on 'allow_install' property
        if ('allow_install' !== $subject->getName()) {
            return false;
        }

        return in_array($attribute, ['DATABASE_EDIT', 'DATABASE_VIEW'])
            && $subject instanceof Config;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user exists, do not grant access
        if ($user instanceof UserInterface) {
            return false;
        }

        $config = $subject;

        switch ($attribute) {
            case 'DATABASE_VIEW':
            case 'DATABASE_EDIT':
                return $this->canInstall($config);
        }

        return false;
    }

    public function canInstall($config): bool
    {
        if ('allow_install' === $config->getName()) {
            if ('true' === $config->getValue()) {
                return true;
            } elseif ('false' === $config->getValue()) {
                return false;
            } else {
                throw new LogicException();
            }
        } else {
            return false;
        }
    }
}
