<?php

namespace App\Security\Voter;

use App\Entity\Config;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class InstallVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        
// TODO : change Config structure to key/value pairs for allowInstall

             // only vote on `Config` objects
             if (!$subject instanceof Config) {
                return false;
            }

            return true;
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['DATABASE_EDIT', 'DATABASE_VIEW'])
            && $subject instanceof Config;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();

         // if the user exists, do not grant access
         if ($user instanceof UserInterface) {
            return false;
        }

     
        $config = $subject;
        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'DATABASE_EDIT':
                // logic to determine if the user can EDIT
                // return true or false
            
                return $config->getAllowInstall();
              
                break;
            case 'DATABASE_VIEW':
                // logic to determine if the user can VIEW
                // return true or false
              
                return $config->getAllowInstall();
                break;
        }

        return false;
    }
}
