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
use Symfony\Component\Yaml\Yaml; // Read yml file

class toolscore {

	protected $connection;
	protected $container;
	protected $logger;
	
	protected $language;
	protected $tranlations;
	
	public function __construct(Logger $logger, Container $container, Connection $dbalConnection) {
		$this->logger = $logger;
		$this->container = $container;
		$this->connection = $dbalConnection;
	}

	// Compose une liste html avec les options
	public static function composeListHtml($array,$phrase = false) {
		$r="";
		if($array) {			
			asort( $array );			
			if($phrase) {
				$r.='<option value="" selected="selected">'.$phrase.'</option>';	
				$r.='<option value="" disabled="disabled">- - - - - - - -</option>';	
			}
			
			foreach ($array as $k => $v) {
				if($v != '') {
					$r.='<option value="'.$k.'">'.str_replace(array(';','\'','\"'), ' ', $v).'</option>';
				}
			}				
		}
		else {
			$r.='<option value="" selected="selected">'.$phrase.'</option>';	
		}
		
		return $r;		
	}	
	
	
	public static function post_slug($str) { 
		$str = utf8_decode($str);
		$str = strtr($str, utf8_decode('ÁÀÂÄÃÅÇÉÈÊËÍÏÎÌÑÓÒÔÖÕÚÙÛÜÝ'), utf8_decode('AAAAAACEEEEEIIIINOOOOOUUUUY'));
		$str = strtr($str, utf8_decode('áàâäãåçéèêëíìîïñóòôöõúùûüýÿ'), utf8_decode('aaaaaaceeeeiiiinooooouuuuyy'));
		return strtolower(preg_replace(array('/[^a-zA-Z0-9 -]/', '/[ -]+/', '/^-|-$/'), array('', '_', ''), $str)); 
	} 
	
	
	// Permet de renvoyer les champs d'un table avec leur type
	public function describeTable($table) {
		// Création de la requête
		$sqlParams = "DESCRIBE $table";
		$stmt = $this->connection->prepare($sqlParams);
		$stmt->execute();	   				
		$fields = $stmt->fetchAll();	
		if (!empty($fields)) {
			foreach ($fields as $field) {
				$result[$field['Field']] = $field; 
			}
			return $result;
		}
		return null;		
	}
	
	// Allow translation from php classes
	public function getTranslation($textArray) {
		try {
			$result = '';
			// Get the current language
			$this->language = $this->container->getParameter('locale');
			
			// Get the translation for the current language
			if (empty($this->tranlations)) {
				$this->tranlations = Yaml::parse(file_get_contents(__DIR__.'/../Resources/translations/messages.'.$this->language.'.yml'));
			}
			// Search the translation
			if (!empty($this->tranlations)) {
				// Get the first level
				if (!empty($this->tranlations[$textArray[0]])) {
					$result = $this->tranlations[$textArray[0]];
				}
				// Get the next levels
				$nbLevel = sizeof($textArray);
				for($i = 1; $i < $nbLevel;$i++) {
					if (!empty($result[$textArray[$i]])) {
						$result = $result[$textArray[$i]];
					}
					else {
						$result = '';
						break;
					}
				}
			}
			// Return the input text if the translation hasn't been found
			if (empty($result)) {
				$result = implode(' - ',$textArray);
			}
		} catch (\Exception $e) {
			$result = implode(' - ',$textArray);
		}
		return $result;
	}
	
	// Change Myddleware parameters
	public function changeMyddlewareParameter($nameArray, $value) {	
		$myddlewareParameters = Yaml::parse(file_get_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml'));
		// Search the translation
		if (!empty($myddlewareParameters)) {
			$nbLevel = sizeof($nameArray);
			switch ($nbLevel) {
				case 1:
					$myddlewareParameters['parameters'][$nameArray[0]] = $value;
					break;
				case 2:
					$myddlewareParameters['parameters'][$nameArray[0]][$nameArray[1]] = $value;
					break;
				case 3:
					$myddlewareParameters['parameters'][$nameArray[0]][$nameArray[1]][$nameArray[2]] = $value;
					break;
				}
		}
		$new_yaml = \Symfony\Component\Yaml\Yaml::dump($myddlewareParameters, 4);
		file_put_contents($this->container->getParameter('kernel.root_dir').'/config/parameters.yml', $new_yaml);
	}
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/tools.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class tools extends toolscore {
		
	}
}
 
?>