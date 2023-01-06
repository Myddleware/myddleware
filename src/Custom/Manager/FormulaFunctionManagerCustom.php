<?php
namespace App\Custom\Manager;

use App\Manager\FormulaFunctionManager;

class FormulaFunctionManagerCustom extends FormulaFunctionManager {
	
	protected $namesCustom = array('Afev_serialize', 'Afev_unserialize', 'Afev_removeEmoji');
	
	protected $pathCustom = "App\Custom\Manager\FormulaFunctionManagerCustom::";
	
	// Add custom function in Myddleware
	public function getNamesFunctions(): array	
	{	
		$this->names = parent::getNamesFunctions();
		if (!empty($this->namesCustom)) {
			foreach($this->namesCustom as $name) {
				$this->names[] = $name;
			}
		}
		return $this->names;
	}
	
	public function getPathFunctions(): array
    {
        // Concaténation avant envoi du chemin avec le nom
        $return = [];
        foreach ($this->names as $name) {
			if (in_array($name, $this->namesCustom)) {
				$return[] = $this->pathCustom.$name;
			} else {
				$return[] = $this->path.$name;
			}
        }
        return $return;
    }
	
	public function addPathFunctions($formula)
    {
        if (!empty($this->namesCustom)) {
            foreach ($this->namesCustom as $namesCustom) {
                $formula = str_replace($namesCustom, $this->pathCustom.$namesCustom, $formula);
            }
        }
        return parent::addPathFunctions($formula);
    }

	// Fonction permettant de transformer les liste de SuiteCRM en une chaine serialisée
	public static function Afev_serialize($data) {
		if (!empty($data)) {
			$values = explode(',', str_replace('^', '', $data));
			if (!empty($values)) {
				return serialize($values);
			}
		}
		return '';
	}
	
	// Fonction permettant de transformer les liste de SuiteCRM en une chaine serialisée
	public static function Afev_unserialize($data) {
		if (!empty($data)) {
			$values = unserialize($data);
			if (!empty($values)) {
				return str_replace("^^", "", "^".implode("^,^", $values)."^");
			}
		}
		return '';
	}
	
	// Remove emotji from a string
	public static function Afev_removeEmoji($text) {
		$text = iconv('UTF-8', 'ISO-8859-15//IGNORE', $text);
		$text = preg_replace('/\s+/', ' ', $text);
		return iconv('ISO-8859-15', 'UTF-8', $text);
	}

}
?>