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

class myddlewareFormulaFunctionscore {

	private $names = array('changeTimeZone', 'changeFormatDate', 'changeValue', 'getValueFromArray');

	private $path = "Myddleware\RegleBundle\Classes\myddlewareFormulaFunctions::";

	public function test() {
		echo "test";
	}
	
	public function getNamesFunctions(){
		return $this->names;
	}
	
	public function getPathFunctions() {
		// Concaténation avant envoi du chemin avec le nom
		$return = array();
		foreach ($this->names as $name) {
			$return[] = $this->path.$name;
		}
		return $return;
	}

	public static function changeTimeZone($dateToChange, $oldTimeZone, $newTimeZone) {
		if (empty($dateToChange)) {
			return null;
		}
		$date = date_create($dateToChange, timezone_open($oldTimeZone));
		date_timezone_set($date, timezone_open($newTimeZone));
		return date_format($date, "Y-m-d H:i:s");
	}

	public static function changeFormatDate($dateToChange, $oldFormat, $newFormat) {
		if (empty($dateToChange)) {
			return null;
		}
		$date = \DateTime::createFromFormat($oldFormat, $dateToChange);
		return date_format($date, $newFormat);
	}

	public static function changeValue($var, $arrayKeyToValue) {
		// Transform string into an array
		$arrayKeyToValue = json_decode(str_replace(array('(',')','\''),array('{','}','"'),$arrayKeyToValue),true);
		if(in_array($var, array_keys($arrayKeyToValue))) {
			$var = $arrayKeyToValue[$var];
			return $var;
		}
		return null;
	}
	
	public static function getValueFromArray($key, $array) {
		if(!empty($array[$key])) {
			return $array[$key];
		}
		return null;
	}
}

/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/myddlewareFormulaFunctions.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class myddlewareFormulaFunctions extends myddlewareFormulaFunctionscore {
		
	}
}
?>