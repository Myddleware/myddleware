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

namespace Myddleware\RegleBundle\Classes;

use Symfony\Bridge\Monolog\Logger; // Gestion des logs
use Symfony\Component\DependencyInjection\ContainerInterface as Container; // Accède aux services
use Doctrine\DBAL\Connection; // Connexion BDD
use Myddleware\RegleBundle\Classes\tools as MyddlewareTools;

class homecore {
	
	protected $connection;
	protected $container;
	protected $logger;
	
	protected $historicDays = 7;
	protected $historicDateFormat = 'M-d';

	
    public function __construct(Logger $logger, Container $container, Connection $dbalConnection, $param = false) {
    	$this->logger = $logger;
		$this->container = $container;
		$this->connection = $dbalConnection;	
	}
	
	// 
	public function errorByRule($is_support, $id) {
		try {	

			if($is_support == false) {
				$where = ' WHERE created_by='.$id;
			}
			else {
				$where = '';
			}

		    $sql = "SELECT 
						Rule.rule_name,
						Rule.rule_version,
						Rule.rule_id,
						count(Documents.id) cpt
					FROM Rule
						LEFT OUTER JOIN Documents
							ON  Rule.rule_id = Documents.rule_id
							AND Documents.global_status = 'Error'
					$where 
					GROUP BY Rule.rule_name
					HAVING cpt > 0
					ORDER BY cpt DESC";
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}	
	}
	
	// 
	public function closedByRule($is_support, $id) {
		
		try {
						
			if($is_support == false) {
				$where = 'AND created_by='.$id;
			}
			else {
				$where = '';
			}			
				
		    $sql = "SELECT 
						Rule.rule_name,
						count(Documents.id) cpt
					FROM Rule
						LEFT OUTER JOIN Documents
							ON  Rule.rule_id = Documents.rule_id
							AND Documents.global_status = 'Close'
					WHERE
							Rule.rule_active = 1
						AND Rule.rule_deleted = 0
						$where
					GROUP BY Rule.rule_name";
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}	
	}

	public function listError($nbJour = false) {
		try {				
		    $sql = "SELECT 
						Rule.rule_name,
						Documents.id,
						Documents.date_modified
					FROM Documents
						INNER JOIN Rule
							ON  Rule.rule_id = Documents.rule_id
							AND Documents.global_status = 'Error'
					WHERE
							Rule.rule_active = 1
						AND Rule.rule_deleted = 0";
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}	
	}

	public function countTypeDoc($isAdmin, $id) {
		try {
			$where = '';
			if($isAdmin == false) {
				$where = 'WHERE created_by='.$id;
			}			
							
		    $sql = "SELECT count(*) as nb, global_status
					FROM Documents
					$where
					GROUP BY global_status
					ORDER BY nb DESC
					";
							
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
		
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}
	}
	
	public function countTransferRule($isAdmin, $id) {
		try {
			$where = '';
			if($isAdmin == false) {
				$where = ' AND created_by='.$id;
			}		
							
		    $sql = "SELECT count(*) as nb, Rule.rule_name
					FROM Rule
						INNER JOIN Documents
							ON Rule.rule_id = Documents.rule_id
					WHERE
						Documents.global_status = 'Close'
						$where
					GROUP BY rule_name
					ORDER BY nb DESC
					";
							
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();			
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}
	}
	
	public function countTransferHisto($isAdmin, $id) {
		try {
			$historic = array();
			// Start date
			$startDate = date('Y-m-d', strtotime( '-'.$this->historicDays.' days' ) );;
			// End date
			$endDate = date('Y-m-d');
			// Init array
			while (strtotime($startDate) < strtotime($endDate)) {
				// echo "$startDate\n   ";
				$startDateFromat = date ($this->historicDateFormat, strtotime('+1 day', strtotime($startDate)));
				$startDate = date ("Y-m-d", strtotime('+1 day', strtotime($startDate)));
				$historic[$startDate] = array('date' => $startDateFromat,'open' => 0,'error' => 0,'cancel' => 0,'close' => 0);
			}
			
			$where = '';
			if($isAdmin == false) {
				$where = ' AND created_by='.$id;
			}		
		    $sql = "SELECT DATE_FORMAT(date_modified, '%Y-%m-%d') date, Documents.* FROM Documents WHERE date_modified >= DATE_ADD(CURDATE(), INTERVAL -".$this->historicDays." DAY) ".$where;				
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			if (!empty($result)) {
				foreach ($result as $row) {
					if (isset($historic[$row['date']][strtolower($row['global_status'])])) {
						$historic[$row['date']][strtolower($row['global_status'])]++;
					}
				}
			}
			return $historic; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.__CLASS__.' Line : ( '.$e->getLine().' )' );
		}
	}

}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/home.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class home extends homecore {
		
	}
}
?>