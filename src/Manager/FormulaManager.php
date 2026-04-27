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

use App\Service\DebugLogger;

class FormulaManager
{
    private string $path = "App\Manager\FormulaFunctionManager::";
    public array $parse = [];
    public FormulaFunctionManager $formulaFunctionManager;
    private DebugLogger $debugLogger;

    public function __construct(FormulaFunctionManager $formulaFunctionManager, DebugLogger $debugLogger)
    {
        $this->parse['error'] = 0;
        $this->formulaFunctionManager = $formulaFunctionManager;
        $this->debugLogger = $debugLogger;
    }

    public function getNamespace(): string
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            return $__debugReturn = __NAMESPACE__;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function init($formule)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['formule' => $formule]);
        try {
            $this->parse['formule'] = $formule;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function getParse(): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->parse;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function getFields()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
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
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    private function getText()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
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
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    private function getMethode()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
            $stringFunc = $this->parse['formuleConvert'];

            if (isset($this->parse['field'])) {
                foreach ($this->parse['field'] as $field) {
                    $stringFunc = str_replace('{'.$field.'}', '', $stringFunc);
                }
            }

            if (isset($this->parse['text'])) {
                foreach ($this->parse['text'] as $txt) {
                    $txt = str_replace(';', '', $txt);
                    $stringFunc = str_replace(';', '', $stringFunc);
                    $stringFunc = str_replace('"'.$txt.'"', '', $stringFunc);

                    $stringFunc = str_replace('" '.$txt.'"', '', $stringFunc);
                    $stringFunc = str_replace('"'.$txt.' "', '', $stringFunc);
                    $stringFunc = str_replace('" '.$txt.' "', '', $stringFunc);
                }
            }

            $stringFunc = str_replace(['.', '?', ',', '=', '', '+', '-', '"', ';'], '', $stringFunc);
            $stringFunc = str_replace('[]', ',', $stringFunc);
            $stringFunc = str_replace('()', ' ', $stringFunc);
            $stringFunc = str_replace('(', '', $stringFunc);
            $stringFunc = str_replace(')', '', $stringFunc);
            $r = explode(' ', $stringFunc);
            $r = implode(',', $r);
            $r = explode(',', $r);

            if ($r) {
                foreach ($r as $k) {
                    $ktrim = trim($k);
                    if ('' != $ktrim && strlen($k) > 2) {
                        if (!preg_match('#[0-9]#', $ktrim)) {
                            $this->parse['function'][] = $ktrim;
                        }
                    }
                }

                if (isset($this->parse['function']) && count($this->parse['function']) > 0) {
                    $this->parse['function'] = array_unique($this->parse['function']);
                    $this->parse['error'] = $this->verifAutorisationSecure($this->secureFunction(), $this->parse['function'], $this->parse['error']);
                }
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    private function secureFunction(): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $pathFunctions = $this->formulaFunctionManager->getPathFunctions();

            $array = ['mb_strtolower', 'trim', 'ltrim', 'rtrim', 'mb_strtoupper', 'round', 'ceil', 'abs', 'mb_substr', 'str_replace', 'preg_replace', 'strip_tags', 'date', 'utf8_encode', 'utf8_decode', 'html_entity_decode', 'htmlentities', 'htmlspecialchars', 'strlen', 'urlencode', 'json_decode', 'json_encode', 'empty', 'isset'];
            $const = ['ENT_COMPAT', 'ENT_QUOTES', 'ENT_NOQUOTES', 'ENT_HTML401', 'ENT_XML1', 'ENT_XHTML', 'ENT_HTML5'];

            return $__debugReturn = array_merge($array, $const, $pathFunctions);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function replaceStringFunction($string)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['string' => $string]);
        $__debugReturn = null;
        try {
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

            return $__debugReturn = $string;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function remplaceFunction()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
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
            foreach ($new_text as $index => $name) {
                $string = str_replace('@@@txt'.$i.'@@@', $name, $string);
                ++$i;
            }
        } else {
            // REPLACE FUNCTION ----------------------------------------
            $string = $this->replaceStringFunction($string);
            // REPLACE FUNCTION ----------------------------------------
        }

        $string = $this->formulaFunctionManager->addPathFunctions($string);
        $this->parse['formuleConvert'] = $string;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    private function verifAutorisationSecure($tabSecure, $tabListe, $error)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['tabSecure' => $tabSecure, 'tabListe' => $tabListe, 'error' => $error]);
        $__debugReturn = null;
        try {
            if (count($tabListe) > 0) {
                foreach ($tabListe as $l) {
                    if (!in_array($l, $tabSecure)) {
                        ++$error;
                    }
                }

                return $__debugReturn = $error;
            }

            return $__debugReturn = $error;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function accent($string): bool
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['string' => $string]);
        $__debugReturn = null;
        try {
            $encoding = mb_detect_encoding($string);
            if ($encoding != 'UTF-8') {
                $string = mb_convert_encoding($string, 'UTF-8');
            }

            $lowercaseString = mb_strtolower($string);
            $iAccents = ["ì", "î",  "ï"];
            $eAccents = ["è", "é", "ê", "ë"];
            $cCedille = ["ç"];
            $aAccents = ["à", "á", "â", "ã", "ä", "å"];
            $oAccents = ["ò", "ó", "ô", "õ", "ö"];
            $uAccents = ["ù", "ú", "û", "ü"];

            foreach ($iAccents as $char) {
                if (strpos($lowercaseString, $char) !== false) {
                    return $__debugReturn = true;
                }
            }

            foreach ($eAccents as $char) {
                if (strpos($lowercaseString, $char) !== false) {
                    return $__debugReturn = true;
                }
            }

            foreach ($cCedille as $char) {
                if (strpos($lowercaseString, $char) !== false) {
                    return $__debugReturn = true;
                }
            }

            foreach ($aAccents as $char) {
                if (strpos($lowercaseString, $char) !== false) {
                    return $__debugReturn = true;
                }
            }

            foreach ($oAccents as $char) {
                if (strpos($lowercaseString, $char) !== false) {
                    return $__debugReturn = true;
                }
            }

            foreach ($uAccents as $char) {
                if (strpos($lowercaseString, $char) !== false) {
                    return $__debugReturn = true;
                }
            }

            return $__debugReturn = false;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    private function verifRegexSecure($tabListe, $error)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['tabListe' => $tabListe, 'error' => $error]);
        $__debugReturn = null;
        try {
            if (count($tabListe) > 0) {
                foreach ($tabListe as $l) {
                    $pregMatchResult = preg_match('#[^[:alnum:]_.¿]#u', $l);
                    if ($pregMatchResult) {
                        ++$error;
                    }
                    if ($this->accent($l)) {
                        ++$error;
                    }
                }

                return $__debugReturn = $error;
            }

            return $__debugReturn = $error;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    // Transformation et securite de la formule
    private function secureFormule()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
            $string = str_replace('{', '$', $this->parse['formuleConvert']);
            $tab = ['}'];
            $string = str_replace($tab, '', $string);

            // méthodes
            $string = str_replace('[', '(', $string);
            $string = str_replace(']', ')', $string);

            // ----------- secure
            $string = trim($string);

            // ----- remove control characters -----
            $string = str_replace("\r", '', $string);    // --- replace with empty space
            $string = str_replace("\n", '', $string);   // --- replace with empty space
            $string = str_replace("\t", '', $string);   // --- replace with empty space

            // ----- remove multiple spaces -----
            $string = trim(preg_replace('/ {2,}/', ' ', $string));

            $this->parse['formuleConvert'] = $string;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function execFormule()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            if (0 == $this->parse['error']) {
                return $__debugReturn = $this->parse['formuleConvert'];
            }

            return $__debugReturn = false;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function generateFormule()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        try {
            $this->remplaceFunction();
            $this->getFields();
            $this->getText();
            $this->secureFormule();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}
