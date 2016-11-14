<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace Myddleware\RegleBundle\Twig;

//use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
use Doctrine\DBAL\Connection; // Connexion BDD

class UserExtension extends \Twig_Extension
{

	public function __construct( Connection $dbalConnection) {				
		$this->connection = $dbalConnection;
	}

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('username', array($this, 'userName')),
        );
    }

    public function userName($id)
    {
		$id = (int)$id;		
		$user = "SELECT username FROM users WHERE id = :id";
	    $stmt = $this->connection->prepare($user);
		$stmt->bindValue("id", $id);
	    $stmt->execute();
		$u = $stmt->fetch();	
		return $u['username'];
    }

	// Nom de l'extension
    public function getName()
    {
        return 'user_extension';
    }
}


?>