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
    protected $configData = [
        ['name' => 'executable', 'value' => 'php72']
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
