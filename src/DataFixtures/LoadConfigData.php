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

namespace App\DataFixtures;

use App\Entity\Config;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadConfigData implements FixtureInterface
{
    private $manager;
    protected array $configData = [
        ['name' => 'allow_install', 'value' => true, 'update' => false],
        ['name' => 'extension_allowed', 'value' => '["xml","wsdl"]', 'update' => false],
        ['name' => 'pager', 'value' => 20, 'update' => false],
        ['name' => 'migration_mode', 'value' => false, 'update' => false],
        ['name' => 'alert_time_limit', 'value' => 60, 'update' => false],
        ['name' => 'search_limit', 'value' => 1000, 'update' => false],
        ['name' => 'git_branch', 'value' => 'main', 'update' => false],
        ['name' => 'base_uri', 'value' => '', 'update' => false],
        ['name' => 'email_from', 'value' => 'no-reply@myddleware.com', 'update' => false],
        ['name' => 'cron_enabled', 'value' => true, 'update' => true],
        ['name' => 'alert_date_ref', 'value' => '1999-01-01 00:00:00', 'update' => true],
    ];

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $this->generateEntities();
        $this->manager->flush();
    }

    private function generateEntities()
    {
        // Get all config already in the database
        $configs = $this->manager->getRepository(Config::class)->findAll();
        foreach ($this->configData as $configData) {
            $foundConfig = false;
            if (!empty($configs)) {
                foreach ($configs as $config) {
                    if ($config->getName() == $configData['name']) {
                        $foundConfig = true;
                        $conf = $config;
                        break;
                    }
                }
            }
            // Update only if the config has the flag update = true
            if (
                    $foundConfig
                and !$configData['update']
            ) {
                continue;
            }

            // If we didn't found the config we create a new one, otherwise we update it
            if (!$foundConfig) {
                $conf = new Config();
            }
            $conf->setName($configData['name']);
            $conf->setValue($configData['value']);
            $this->manager->persist($conf);
        }
    }
}
