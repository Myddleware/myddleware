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

namespace App\Manager;

class FormulaManager
{
    public $parse = [];

    public $formulaFunctionManager;

    public function __construct(FormulaFunctionManager $formulaFunctionManager)
    {
        $this->parse['error'] = 0; // Erreur par défaut
        $this->formulaFunctionManager = $formulaFunctionManager;
    }

    public function getNamespace(): string
    {
        return __NAMESPACE__;
    }

    public function init($formule)
    {
        $this->parse['formule'] = $formule;
    }

    // Retourne tout le tableau parse
    public function getParse()
    {
        return $this->parse;
    }

    // récupère tous les champs
    private function getFields()
    {
        preg_match_all('|{(.*)}|U', $this->parse['formule'], $fields, PREG_PATTERN_ORDER);

        if ($fields[1]) {
            foreach ($fields[1] as $f) {
                $this->parse['field'][] = trim($f);
            }

            if (isset($this->parse['field']) && count($this->parse['field']) > 0) {
                $this->parse['field'] = array_unique($this->parse['field']);
                $this->parse['error'] = $this->verifRegexSecure($this->parse['field'], $this->parse['error']);
            }
        }
    }

    // récupère tous les champs textes
    private function getText()
    {
        preg_match_all('|"(.*)"|U', $this->parse['formule'], $txt, PREG_PATTERN_ORDER);

        if ($txt[1]) {
            foreach ($txt[1] as $t) {
                if (' ' == $t) {
                    $this->parse['text'][] = $t;
                } else {
                    $string = trim($t);
                    $this->parse['text'][] = $string;
                }
            }

            if (isset($this->parse['text']) && count($this->parse['text']) > 0) {
                $this->parse['text'] = array_unique($this->parse['text']);
            }
        }
    }

    private function replaceStringFunction($string)
    {
        $string = str_replace('lower(', 'mb_strtolower(', $string);
        $string = str_replace('upper(', 'mb_strtoupper(', $string);
        $string = str_replace('substr(', 'mb_substr(', $string);
        $string = str_replace('replace(', 'str_replace(', $string);
        $string = str_replace('striptags(', 'strip_tags(', $string);
        $string = str_replace('utf8encode(', 'utf8_encode(', $string);
        $string = str_replace('utf8decode(', 'utf8_decode(', $string);
        $string = str_replace('htmlEntityDecode(', 'html_entity_decode(', $string);
        $string = str_replace('htmlentities(', 'htmlentities(', $string);

        return str_replace('htmlspecialchars(', 'htmlspecialchars(', $string);
    }

    // Change les méthodes
    private function remplaceFunction()
    {
        preg_match_all('|"(.*)"|U', $this->parse['formule'], $txt, PREG_PATTERN_ORDER);

        $string = $this->parse['formule'];

        if (is_array($txt[1]) && count($txt[1]) > 0) {
            $txt[1] = array_unique($txt[1]);

            $new_text = [];
            $i = 0;
            foreach ($txt[1] as $formule_text) {
                $new_text['txt'.$i] = '"'.$formule_text.'"';
                ++$i;
            }

            $i = 0;

            foreach ($new_text as $formule_text) {
                $string = str_replace($formule_text, '@@@txt'.$i.'@@@', $string);
                ++$i;
            }

            // REPLACE FUNCTION ----------------------------------------
            $string = $this->replaceStringFunction($string);
            // REPLACE FUNCTION ----------------------------------------

            $i = 0;
            foreach ($new_text as $name) {
                $string = str_replace('@@@txt'.$i.'@@@', $name, $string);
                ++$i;
            }
        } else {
            // REPLACE FUNCTION ----------------------------------------
            $string = $this->replaceStringFunction($string);
            // REPLACE FUNCTION ----------------------------------------
        }

        // str_replace sur toutes les fonctions de myddlewareFormulaFunctions.php
        $string = $this->formulaFunctionManager->addPathFunctions($string);
        $this->parse['formuleConvert'] = $string;
    }

    // Détecte si une chaine possède des accents
    private function accent($string)
    {
        if (1 == preg_match('#[áàâäãåçéèêëíìîïñóòôöõúùûüýÿ]#', mb_strtolower($string))) {
            return true;
        }

        return false;
    }

    // Détecte si un tableau n'est pas conforme
    private function verifRegexSecure($tabListe, $error)
    {
        if (count($tabListe) > 0) {
            foreach ($tabListe as $l) {
                if (preg_match('#[^[:alnum:]_.]#u', $l) || $this->accent($l)) {
                    ++$error;
                }
            }

            return $error;
        }

        return $error;
    }

    // Transformation et securite de la formule
    private function secureFormule()
    {
        $string = str_replace('{', '$', $this->parse['formuleConvert']);
        $tab = ['}'];
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
    public function execFormule()
    {
        if (0 == $this->parse['error']) {
            return $this->parse['formuleConvert'];
        }

        return false;
    }

    // Genère la nouvelle formule
    public function generateFormule()
    {
        $this->remplaceFunction(); // remplace les vrais fonctions
        $this->getFields(); // contrôle sur les champs
        $this->getText(); // contrôle sur les chaines
        // $this->getMethode(); // contrôle sur les méthodes
        $this->secureFormule(); // niveau securité
    }
}
