<?php

namespace App\Security\Voter;

use App\Entity\DatabaseParameter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class InstallVoter extends Voter
{
    protected function supports($attribute, $subject)
    {


             // only vote on `DatabaseParameter` objects
             if (!$subject instanceof DatabaseParameter) {
                return false;
            }

            return true;
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        // return in_array($attribute, ['DATABASE_EDIT', 'DATABASE_VIEW'])
        //     && $subject instanceof DatabaseParameter;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

         // if the user exists, do not grant access
         if ($user instanceof UserInterface) {
            return false;
        }

     
        $databaseParameter = $subject;
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'DATABASE_EDIT':
                // logic to determine if the user can EDIT
                // return true or false
                return $databaseParameter->getAllowInstall();
                break;
            case 'DATABASE_VIEW':
                // logic to determine if the user can VIEW
                // return true or false
                return $databaseParameter->getAllowInstall();
                break;
        }

        return false;
    }
}
