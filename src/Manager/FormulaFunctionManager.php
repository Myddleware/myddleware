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

class formulafunctioncore
{
    protected array $names = ['changeTimeZone', 'changeFormatDate', 'changeValue', 'changeMultiValue', 'getValueFromArray'];
    protected string $path = "App\Manager\FormulaFunctionManager::";

    public function getNamesFunctions(): array
    {
        return $this->names;
    }

    public function getPathFunctions(): array
    {
        // Concaténation avant envoi du chemin avec le nom
        $return = [];
        foreach ($this->names as $name) {
            $return[] = $this->path.$name;
        }

        return $return;
    }

    public function addPathFunctions($formula)
    {
        if (!empty($this->names)) {
            foreach ($this->names as $name) {
                $formula = str_replace($name, $this->path.$name, $formula);
            }
        }

        return $formula;
    }

    public static function changeTimeZone($dateToChange, $oldTimeZone, $newTimeZone)
    {
        if (empty($dateToChange)) {
            return;
        }
        $date = date_create($dateToChange, timezone_open($oldTimeZone));
        date_timezone_set($date, timezone_open($newTimeZone));

        return date_format($date, 'Y-m-d H:i:s');
    }

    public static function changeFormatDate($dateToChange, $oldFormat, $newFormat)
    {
        if (empty($dateToChange)) {
            return;
        }
        $date = \DateTime::createFromFormat($oldFormat, $dateToChange);

        return date_format($date, $newFormat);
    }

    public static function changeValue($var, $arrayKeyToValue, $acceptNull = null)
    {
        // Transform string into an array
		// Change first and last characters (parentheses) by accolades
		// Replace ' before and after , and : by " (manage space before , and :)
        $arrayKeyToValue = json_decode('{"'.str_replace([ ':\'', '\':',  ': \'', '\' :', ',\'', '\',', ', \'', '\' ,'], [ ':"', '":', ':"', '":', ',"', '",', ',"', '",'], substr($arrayKeyToValue,2,-2)).'"}', true);
        if (in_array($var, array_keys($arrayKeyToValue))) {
            return $arrayKeyToValue[$var];
        }
        if (!empty($acceptNull)) {
            return '';
        }
    }

    public static function changeMultiValue($var, $arrayKeyToValue, $delimiter)
    {
        // Transform string into an array
		// Change first and last characters (parentheses) by accolades
		// Replace ' before and after , and : by " (manage space before , and :)
        $return = '';
        $arrayVar = explode($delimiter, $var);
        if (!empty($arrayVar)) {
            $arrayKeyToValue = json_decode('{"'.str_replace([ ':\'', '\':',  ': \'', '\' :', ',\'', '\',', ', \'', '\' ,'], [ ':"', '":', ':"', '":', ',"', '",', ',"', '",'], substr($arrayKeyToValue,2,-2)).'"}', true);
            foreach ($arrayVar as $varValue) {
                // Transform string into an array
                if (!empty($arrayKeyToValue[$varValue])) {
                    // Prepare return value
                    $return .= $arrayKeyToValue[$varValue].$delimiter;
                }
            }

            return rtrim($return, $delimiter);
        }
    }

    public static function getValueFromArray($key, $array)
    {
        if (!empty($array[$key])) {
            return $array[$key];
        }
    }
}

class FormulaFunctionManager extends formulafunctioncore
{
}
