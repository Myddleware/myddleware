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
	protected $nbHistoricJobs = 5;
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
				$where = ' WHERE Rule.created_by='.$id;
			}
			else {
				$where = '';
			}

		    $sql = "SELECT 
						Rule.name,
						Rule.id,
						count(Document.id) cpt
					FROM Rule
						LEFT OUTER JOIN Document
							ON  Rule.id = Document.rule_id
							AND Document.global_status IN ('Open','Error')
							AND Document.deleted = 0
					$where 
					GROUP BY Rule.name, Rule.id
					HAVING cpt > 0
					ORDER BY cpt DESC";
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}	
	}
		
	public function listJobDetail() {
		try {	
			$sql = "SELECT id, begin, end, status, message, TIMESTAMPDIFF(SECOND,begin,end) duration FROM Job ORDER BY begin DESC LIMIT ".$this->nbHistoricJobs;	
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
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
						Rule.name,
						count(Document.id) cpt
					FROM Rule
						LEFT OUTER JOIN Document
							ON  Rule.id = Document.rule_id
							AND Document.global_status = 'Close'
							AND Document.deleted = 0
					WHERE
							Rule.active = 1
						AND Rule.deleted = 0
						$where
					GROUP BY Rule.name";
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}	
	}

	public function listError($nbJour = false) {
		try {				
		    $sql = "SELECT 
						Rule.name,
						Document.id,
						Document.date_modified
					FROM Document
						INNER JOIN Rule
							ON  Rule.id = Document.rule_id
							AND Document.global_status = 'Error'
							AND Document.deleted = 0
					WHERE
							Rule.active = 1
						AND Rule.deleted = 0";
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}	
	}

	public function countTypeDoc($isAdmin, $id) {
		try {
			$where = ' WHERE Document.deleted = 0 ';
			if($isAdmin == false) {
				$where .= ' AND created_by='.$id;
			}			
							
		    $sql = "SELECT count(*) as nb, global_status
					FROM Document
					$where
					GROUP BY global_status
					ORDER BY nb DESC
					";
							
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();
		
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
	
	public function countTransferRule($isAdmin, $id) {
		try {
			$where = '';
			if($isAdmin == false) {
				$where = ' AND Document.created_by='.$id;
			}		
							
		    $sql = "SELECT count(*) as nb, Rule.name
					FROM Rule
						INNER JOIN Document
							ON Rule.id = Document.rule_id
							AND Document.deleted = 0
					WHERE
						Document.global_status = 'Close'
						$where
					GROUP BY name
					ORDER BY nb DESC
					";
							
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();			
			return $result; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
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
				$startDateFromat = date ($this->historicDateFormat, strtotime('+1 day', strtotime($startDate)));
				$startDate = date ("Y-m-d", strtotime('+1 day', strtotime($startDate)));
				$historic[$startDate] = array('date' => $startDateFromat,'open' => 0,'error' => 0,'cancel' => 0,'close' => 0);
			}
			
			$where = '';
			if($isAdmin == false) {
				$where = ' AND created_by='.$id;
			}	
			
			// Select the number of transfert per day
		    $sql = "SELECT DATE_FORMAT(date_modified, '%Y-%m-%d') date, global_status, count(*) nb FROM Document WHERE Document.deleted = 0 AND date_modified >= DATE_ADD(CURDATE(), INTERVAL -".$this->historicDays." DAY) ".$where." GROUP BY date, global_status";		
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();		
			if (!empty($result)) {
				foreach ($result as $row) {
					$historic[$row['date']][strtolower($row['global_status'])] = $row['nb'];
				}
			}
			return $historic; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
		}
	}
	
	public function countJobHisto($isAdmin, $id) {
		try {
			$historic = array();
			// Select last jobs
		    $sql = "SELECT begin date, open, close, cancel, error FROM Job LIMIT ".$this->nbHistoricJobs;		
		    $stmt = $this->connection->prepare($sql);
		    $stmt->execute();	    
			$result = $stmt->fetchAll();			
			if (!empty($result)) {
				foreach ($result as $row) {
					$historic[$row['date']] = array('date' => $row['date'],'open' => $row['open'],'error' => $row['error'],'cancel' => $row['cancel'],'close' => $row['close']);
				}
			}
			return $historic; 			
		} catch (\Exception $e) {
			$this->logger->error( 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )' );
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