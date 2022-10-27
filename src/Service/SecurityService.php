<?php

namespace App\Service;

use Exception;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

class SecurityService
{
    private RoleHierarchyInterface $roleHierarchy;

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    /**
     * @param $password
     * @param $salt
     *
     * @throws Exception
     *
     * @version 2.0
     *
     * @copyright ©2MConseil 2019.
     * @author Flavian Cecilien
     */
    public function hashPassword(&$password, &$salt): void
    {
        if (null === $salt) {
            $salt = rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=');
        }

        $salted = $password.'{'.$salt.'}';
        $digest = hash('sha512', $salted, true);

        for ($i = 1; $i < 5000; ++$i) {
            $digest = hash('sha512', $digest.$salted, true);
        }
        $password = base64_encode($digest);
    }

    /**
     * @version 2.0
     *
     * @copyright ©2MConseil 2019.
     * @author Flavian Cecilien
     */
    public function getDefinedRoles(): array
    {
        $roles = [];

        // Cast to Array to break protected and private values
        $roleHierarchyArray = (array) $this->roleHierarchy;

        // Shift array to get first element
        $roleHierarchyArray = array_shift($roleHierarchyArray);

        // Loop on each to get on key the role and on value the english name of role
        foreach ($roleHierarchyArray as $role => $dependencyRoles) {
            $roles[$role] = ucwords(str_replace('_', ' ', strtolower(str_replace('ROLE_', '', $role))), ' ');
        }

        return $roles;
    }
}
