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

use Doctrine\DBAL\Driver\Connection;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

$file = __DIR__.'/../Custom/Manager/ToolsManager.php';
if (file_exists($file)) {
    require_once $file;
} else {
    /**
     * Class ToolsManager.
     *
     * @package App\Manager
     *
     *
     */
    class ToolsManager
    {
        protected $connection;
        protected $container;
        protected $logger;

        protected $language;
        protected $translations;
        /**
         * @var ParameterBagInterface
         */
        private $params;
        /**
         * @var string
         */
        private $projectDir;

        public function __construct(
            LoggerInterface $logger,
            Connection $connection,
            ParameterBagInterface $params,
            KernelInterface $kernel
        ) {
            $this->logger = $logger;
            $this->connection = $connection;
            $this->params = $params;
            $this->language = $this->params->get('locale');
            $this->projectDir = $kernel->getProjectDir();
            $this->translations = Yaml::parse(file_get_contents($this->projectDir.'/translations/messages.'.$this->language.'.yml'));
        }

        // Compose une liste html avec les options
        public static function composeListHtml($array, $phrase = false)
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
                        $r .= '<option value="'.$k.'">'.str_replace([';', '\'', '\"'], ' ', $v).'</option>';
                    }
                }
            } else {
                $r .= '<option value="" selected="selected">'.$phrase.'</option>';
            }

            return $r;
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
            $myddlewareParameters = Yaml::parse(file_get_contents($this->projectDir.'/config/public/parameters_public.yml'));
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
            file_put_contents($this->projectDir.'/config/public/parameters_public.yml', $new_yaml);
        }
    }
}
