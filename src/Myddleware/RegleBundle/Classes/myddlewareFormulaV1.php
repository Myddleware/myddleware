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

class myddlewareFormulaV1core {
	
	private $path = "Myddleware\RegleBundle\Classes\myddlewareFormulaFunctions::";
	
	public $parse = array();
	public $functions;
	
    public function __construct() {
		$this->parse['error'] = 0; // Erreur par défaut	
		$file = __DIR__.'/../Classes/myddlewareFormulaFunctions.php';
		if(file_exists($file)){
			require_once($file);
			$this->functions = new myddlewareFormulaFunctions();
		}			
    }
	
	public function getNamespace() {
		return __NAMESPACE__;
	}
	
	public function init($formule) {
		$this->parse['formule'] = $formule;
	}
	
	// Retourne les tableaux en toute confiance
	public function getSecure() {
		return $this->secure;
	}
	
	// Retourne tout le tableau parse
	public function getParse() {
		return $this->parse;
	}

	// récupère tous les champs
	private function getFields() {
		preg_match_all("|{(.*)}|U",$this->parse['formule'],$fields, PREG_PATTERN_ORDER);
		
		if($fields[1]) {
			foreach ($fields[1] as $f) {
				$this->parse['field'][] = trim($f);	
			}
			
			if(isset($this->parse['field']) && count($this->parse['field']) > 0) {
				$this->parse['field'] = array_unique($this->parse['field']);
				$this->parse['error'] = $this->verifRegexSecure($this->parse['field'],$this->parse['error']);	
			}	
		}	
	}	
		
	// récupère tous les champs textes 
	private function getText() {
		preg_match_all('|"(.*)"|U',$this->parse['formule'],$txt, PREG_PATTERN_ORDER);
		
		if($txt[1]) {
			
			foreach ($txt[1] as $t) {
				if($t == ' ') {
					$this->parse['text'][] = $t;				
				}
				else {
					$string = trim($t);
					$this->parse['text'][] = $string;
				} 					
			}
			 
			if(isset($this->parse['text']) && count($this->parse['text']) > 0) {
				$this->parse['text'] = array_unique($this->parse['text']);
			}
		}		
	}
	
	// récupère toutes les méthodes
	private function getMethode() {
		$stringFunc = $this->parse['formuleConvert'];
		
		// enlève les variables	
		if(isset($this->parse['field'])) {
			foreach ($this->parse['field'] as $field ) {
				$stringFunc = str_replace('{'.$field.'}', '', $stringFunc);
			}		
		}
		
		// enlève les chaines
		if(isset($this->parse['text'])) {
			foreach ($this->parse['text'] as $txt ) {
				$txt = str_replace(';', '', $txt);
				$stringFunc = str_replace(';', '', $stringFunc);
				$stringFunc = str_replace('"'.$txt.'"', '', $stringFunc); // 0 space
				
				$stringFunc = str_replace('" '.$txt.'"', '', $stringFunc); // left			
				$stringFunc = str_replace('"'.$txt.' "', '', $stringFunc); // right				
				$stringFunc = str_replace('" '.$txt.' "', '', $stringFunc); // two
			}
		}
	
		$stringFunc = str_replace(array('.','?',',','=','','+','-','"',';'),'',$stringFunc);		
		$stringFunc = str_replace('[]',',',$stringFunc);	
		$stringFunc = str_replace('()',' ',$stringFunc);
		$stringFunc = str_replace('(','',$stringFunc);	
		$stringFunc = str_replace(')','',$stringFunc);				
		$r = explode(' ',$stringFunc);
		$r = implode(',',$r);
		$r = explode(',',$r);
		
		if($r) {
			foreach ($r as $k) {
				$ktrim = trim($k);
				if($ktrim != '' && strlen($k) > 2) {
					if (!preg_match("#[0-9]#", $ktrim)) {
						$this->parse['function'][] = $ktrim;		
					}
				}
			}	
						
			if(isset($this->parse['function']) && count($this->parse['function']) > 0) {
				$this->parse['function'] = array_unique($this->parse['function']);
				$this->parse['error'] = $this->verifAutorisationSecure($this->secureFunction(),$this->parse['function'],$this->parse['error']);					
			}
			
		}		
	}
	
	// Autorisation des méthodes
	private function secureFunction() {
		// Récupère le chemin des fonctions de myddlewareFormulaFunctions.php
		$pathFunctions = $this->functions->getPathFunctions();
		
		// array("pow","exp","abs","sin","cos","tan"); MATHS
		$array = array('mb_strtolower','trim','ltrim','rtrim','mb_strtoupper','round','ceil','abs','mb_substr','str_replace','preg_replace', 'strip_tags', 'date', 'utf8_encode', 'utf8_decode','html_entity_decode','htmlentities','htmlspecialchars','strlen','urlencode','json_decode','json_encode'); 
		$const = array('ENT_COMPAT','ENT_QUOTES','ENT_NOQUOTES','ENT_HTML401','ENT_XML1','ENT_XHTML','ENT_HTML5');

		return array_merge($array, $const, $pathFunctions);
	}

	private function replaceStringFunction($string) {
		$string = str_replace('lower(', 'mb_strtolower(', $string);
		$string = str_replace('upper(', 'mb_strtoupper(', $string);
		$string = str_replace('substr(', 'mb_substr(', $string);
		$string = str_replace('replace(', 'str_replace(', $string);
		$string = str_replace('striptags(', 'strip_tags(', $string);
		$string = str_replace('utf8encode(', 'utf8_encode(', $string);
		$string = str_replace('utf8decode(', 'utf8_decode(', $string);
		$string = str_replace('htmlEntityDecode(', 'html_entity_decode(', $string);
		$string = str_replace('htmlentities(', 'htmlentities(', $string);
		$string = str_replace('htmlspecialchars(', 'htmlspecialchars(', $string);
		return $string;		
	}
	
	// Change les méthodes
	private function remplaceFunction() {		
		preg_match_all('|"(.*)"|U',$this->parse['formule'],$txt, PREG_PATTERN_ORDER);
		
		$string = $this->parse['formule'];
								
		if(is_array($txt[1]) && count($txt[1]) > 0 ) {
			
			$txt[1] = array_unique($txt[1]);
			
			$new_text = array();
			$i = 0;
			foreach ($txt[1] as $formule_text) {
				$new_text['txt'.$i] = '"'.$formule_text.'"';
				$i++;
			}

			$i=0;
			
			foreach ($new_text as $formule_text) {	
				$string = str_replace($formule_text, '@@@txt'.$i.'@@@', $string);
				$i++;
			}	
			
			// REPLACE FUNCTION ----------------------------------------				
			$string = $this->replaceStringFunction($string);
			// REPLACE FUNCTION ----------------------------------------

			$i = 0;
			foreach ($new_text as $index => $name) {				
				$string = str_replace('@@@txt'.$i.'@@@', $name, $string);
				$i++;
			}				
		}
		else {
			// REPLACE FUNCTION ----------------------------------------				
			$string = $this->replaceStringFunction($string);
			// REPLACE FUNCTION ----------------------------------------			
		}


		// str_replace sur toutes les fonctions de myddlewareFormulaFunctions.php
		$names = $this->functions->getNamesFunctions();
		if(count($names) > 0) {
			foreach ($names as $name) {
				$string = str_replace($name, $this->path.$name, $string);
			}			
		}

		$this->parse['formuleConvert'] = $string;
	}	
	
	// Verification des tableaux pour détecter les erreurs
	private function verifAutorisationSecure($tabSecure,$tabListe,$error) {
		if(count($tabListe) > 0) {
			foreach ($tabListe as $l) {
				if(!in_array($l, $tabSecure)) {
					$error++;
				}
			}
			
			return $error;		
		}
		else {
			return $error;
		}	
	}	
	
	// Détecte si une chaine possède des accents
	private function accent($string) {
		if(preg_match("#[áàâäãåçéèêëíìîïñóòôöõúùûüýÿ]#", mb_strtolower($string)) == 1) {
		  	return true;
		}
		else {
		  	return false;
		}
	}

	// Détecte si un tableau n'est pas conforme
	private function verifRegexSecure($tabListe,$error) {
		if(count($tabListe) > 0) {
			foreach ($tabListe as $l) {
				
				if( preg_match('#[^[:alnum:]_]#u', $l) || $this->accent($l) ) {
					$error++;
				}
			}
	
			return $error;		
		}
		else {
			return $error;
		}	
	}
	
	// Transformation et securite de la formule
	private function secureFormule() {	
		$string = str_replace('{', '$', $this->parse['formuleConvert']);
		$tab = array('}');
		$string = str_replace($tab, '', $string);
		
		// méthodes
		$string = str_replace('[', '(', $string);
		$string = str_replace(']', ')', $string);
		
		// ----------- secure
		$string = trim($string);
		$string = strip_tags($string);
		
		// ----- remove control characters -----
		$string = str_replace("\r", '', $string);    // --- replace with empty space
		$string = str_replace("\n", '', $string);   // --- replace with empty space
		$string = str_replace("\t", '', $string);   // --- replace with empty space
		   
		// ----- remove multiple spaces -----
		$string = trim(preg_replace('/ {2,}/', ' ', $string));
		
		$this->parse['formuleConvert'] = $string;		
	}
	
	// Execute la formule
	public function execFormule() {	
		if($this->parse['error'] == 0) {
			return $this->parse['formuleConvert'];
		}
		else {
			return false;
		}
	}
	
	// Genère la nouvelle formule
	public function generateFormule() {
		$this->remplaceFunction(); // remplace les vrais fonctions
		$this->getFields(); // contrôle sur les champs		
		$this->getText(); // contrôle sur les chaines
		// $this->getMethode(); // contrôle sur les méthodes
		$this->secureFormule(); // niveau securité		
	}
}


/* * * * * * * *  * * * * * *  * * * * * * 
	si custom file exist alors on fait un include de la custom class
 * * * * * *  * * * * * *  * * * * * * * */
$file = __DIR__.'/../Custom/Classes/myddlewareFormulaV1.php';
if(file_exists($file)){
	require_once($file);
}
else {
	//Sinon on met la classe suivante
	class myddlewareFormulaV1 extends myddlewareFormulaV1core {
		
	}
}
?>