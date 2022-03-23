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

use Doctrine\DBAL\Connection;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ToolsManager.
 */
class toolscore
{
    protected $connection;
    protected $container;
    protected $logger;

    protected $language;
    protected $translations;
    /**
     * @var string
     */
    private $projectDir;

    // Standard rule param list to avoird to delete specific rule param (eg : filename for file connector)
    protected $ruleParam = ['datereference', 'bidirectional', 'fieldId', 'mode', 'duplicate_fields', 'limit', 'delete', 'fieldDateRef', 'fieldId', 'targetFieldId', 'deletionField', 'deletion', 'language'];

    public function __construct(
        LoggerInterface $logger,
        Connection $connection,
        KernelInterface $kernel,
        RequestStack $requestStack
    ) {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->projectDir = $kernel->getProjectDir();
        $request = $requestStack->getCurrentRequest();
        $language = $request ? $request->getLocale() : 'en';
        $this->translations = Yaml::parse(file_get_contents($this->projectDir.'/translations/messages.'.$language.'.yml'));
    }

    // Compose une liste html avec les options
    public static function composeListHtml($array, $phrase = false, $default = null)
    {
        $r = '';
        if ($array) {
            asort($array);
            if ($phrase) {
                $r .= '<option value="" selected="selected">'.$phrase.'</option>';
                $r .= '<option value="" disabled="disabled">- - - - - - - -</option>';
            }

            foreach ($array as $k => $v) {
                if ('' != $v) {
                    $r .= '<option value="'.$k.'" '.($k == $default ? 'selected' : '').'>'.str_replace([';', '\'', '\"'], ' ', $v).'</option>';
                }
            }
        } else {
            $r .= '<option value="" selected="selected">'.$phrase.'</option>';
        }

        return $r;
    }

    // Compose checkbox
    public static function composeListHtmlCheckbox($array, $phrase = false)
    {
        $r = '';
        if ($array) {
            asort($array);
            foreach ($array as $k => $v) {
                if ('errorMissing' == $v) {
                    $r .= '<div class="form-check">';
                    $r .= '<input type="checkbox" name="'.$v.'" class="'.$v.' form-check-input" checked></input>';
                    $r .= '</div>';
                } else {
                    $r .= '<div class="form-check">';
                    $r .= '<input type="checkbox" name="'.$v.'" class="'.$v.' form-check-input"></input>';
                    $r .= '</div>';
                }
            }
        } else {
            $r .= '<div class="form-check">';
            $r .= '<input type="checkbox" name="'.$phrase.'" class="form-check-input"></input>';
            $r .= '</div>';
        }

        return $r;
    }

    public function beforeRuleEditViewRender($data)
    {
        return $data;
    }

    public function getRuleParam()
    {
        return $this->ruleParam;
    }

    // Allow translation from php classes
    public function getTranslation($textArray)
    {
        try {
            $result = '';
            // Search the translation
            if (!empty($this->translations)) {
                // Get the first level
                if (!empty($this->translations[$textArray[0]])) {
                    $result = $this->translations[$textArray[0]];
                }
                // Get the next levels
                $nbLevel = sizeof($textArray);
                for ($i = 1; $i < $nbLevel; ++$i) {
                    if (!empty($result[$textArray[$i]])) {
                        $result = $result[$textArray[$i]];
                    } else {
                        $result = '';
                        break;
                    }
                }
            }
            // Return the input text if the translation hasn't been found
            if (empty($result)) {
                $result = implode(' - ', $textArray);
            }
        } catch (Exception $e) {
            $result = implode(' - ', $textArray);
        }

        return $result;
    }

    // Change Myddleware parameters
    public function changeMyddlewareParameter($nameArray, $value)
    {
        $myddlewareParameters = Yaml::parse(file_get_contents($this->projectDir.'/config/packages/public/parameters_public.yml'));
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
        $new_yaml = Yaml::dump($myddlewareParameters, 4);
        file_put_contents($this->projectDir.'/config/packages/public/parameters_public.yml', $new_yaml);
    }

    public function getPhpVersion()
    {
        // Get the custom php version first
        $select = "SELECT * FROM config WHERE name = 'php'";
        $stmt = $this->connection->prepare($select);
        $result = $stmt->executeQuery();
        $config = $result->fetchAssociative();
        if (!empty($config['conf_value'])) {
            $php = $config['conf_value'];
        } else {
            // If no php version found, we use the one returned by the php library
            $phpBinaryFinder = new PhpExecutableFinder();
            $phpBinaryPath = $phpBinaryFinder->find();
            $php = $phpBinaryPath;
        }

        // If no executable found we return 'php'
        if (empty($php)) {
            return 'php';
        }

        return $php;
    }
}

class ToolsManager extends toolscore
{
}
