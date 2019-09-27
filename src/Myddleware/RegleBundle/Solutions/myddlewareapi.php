<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2017  Stéphane Faure - Myddleware ltd - contact@myddleware.com
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

namespace Myddleware\RegleBundle\Solutions;

use Symfony\Bridge\Monolog\Logger;
use Myddleware\RegleBundle\Classes\rule as ruleMyddleware;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType; 

class myddlewareapicore extends solution { 

	protected $driver;
	protected $pdo;
	protected $charset = 'utf8';
	
	protected $stringSeparatorOpen = '`';
	protected $stringSeparatorClose = '`';

	public function login($paramConnexion) {
		parent::login($paramConnexion);
		// Always OK because the connection comes from other application 
		$this->connexion_valide = true;	
	} // login($paramConnexion)
 	
	public function getFieldsLogin() {	
		return array(
					array(
                            'name' => 'file',
                            'type' => TextType::class,
                            'label' => 'solution.fields.file'
                        )	
		);
	}
	
	// Get all tables from the database
	public function get_modules($type = 'source') {		
		try{
			$modules = array('Container' => 'Bittle container (new)');
 			// $modules = array();
			// $modulesArray = explode(';', $this->paramConnexion['modules']);
			// foreach ($modulesArray as $moduleArray) {
				// $modules[$moduleArray] = $moduleArray;	
			// }	
			return $modules; 
		} catch (\Exception $e) {
			$error = 'Error : '.$e->getMessage().' '.$e->getFile().' Line : ( '.$e->getLine().' )';
			return $error;			
		}
	} 	
	
	// Get all fields from the table selected
	public function get_module_fields($module, $type = 'source') {
		parent::get_module_fields($module, $type);
		try{
			$this->moduleFields = array();
			// Ajout du champs date
			$this->moduleFields['Date'] = array('label' => 'Date', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => ($module == 'Container' ? 1 : 0));
			
			// Il n'est pas nécessaire d'ajouter des champs obligatoire sur une règle child (le connecteur a déjà ses champs obligatoires avec la règle root)
			if ($module == 'Container') {
				// Ajout du champs metric
				$this->moduleFields['Metric'] = array('label' => 'Metric', 'type' => 'varchar(255)', 'type_bdd' => 'varchar(255)', 'required' => 1);	
			}
			return $this->moduleFields;
		}
		catch (\Exception $e){
			return false;
		}
	} // get_module_fields($module) 
	
	// Ajout de champ personnalisé dans la target ex : bittle 
	public function getFieldMappingAdd($moduleTarget) {
		return array(
			'Metric' => 'Metric',
			'Date' => 'Date',
			'Filter' => 'Filter',
			'Reference' => 'Reference'
		);
	}
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Solutions/myddlewareapi.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class myddlewareapi extends myddlewareapicore {
		
	}
}