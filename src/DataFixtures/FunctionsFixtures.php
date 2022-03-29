<?php

declare(strict_types=1);
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  St�phane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  St�phane Faure - Myddleware ltd - contact@myddleware.com
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

use App\Entity\FuncCat;
use App\Entity\Functions;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class FunctionsFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private $manager;
    protected $functionData = [
        'mathematical' => ['round', 'ceil', 'abs'],
        'text' => ['trim', 'ltrim', 'rtrim', 'lower', 'upper', 'substr', 'striptags', 'changeValue', 'htmlEntityDecode', 'replace', 'utf8encode', 'utf8decode', 'htmlentities', 'htmlspecialchars', 'strlen', 'urlencode', 'chr', 'json_decode', 'json_encode', 'getValueFromArray'],
        'date' => ['date', 'microtime', 'changeTimeZone', 'changeFormatDate'],
    ];

    protected $functionCats = [];
    protected $functions = [];

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        // TODO: implement this to create a hierarchy between the 2 fixtures file
        $mathematical = $this->getReference(FuncCatFixtures::MATHEMATICAL_FUNC_CAT_REFERENCE);
        $text = $this->getReference(FuncCatFixtures::TEXT_FUNC_CAT_REFERENCE);
        $date = $this->getReference(FuncCatFixtures::DATE_FUNC_CAT_REFERENCE);

        // load all categories that already exist in the database
        $funcCats = $this->manager->getRepository(FuncCat::class)->findAll();
        if (!empty($funcCats)) {
            foreach ($funcCats as $funcCat) {
                $this->functionCats[$funcCat->getId()] = $funcCat;
            }
        }
        // load all functions that already exist in the database
        $functions = $this->manager->getRepository(Functions::class)->findAll();
        if (!empty($functions)) {
            foreach ($functions as $function) {
                $this->functions[$function->getCategoryId()->getName()][$function->getId()] = $function->getName();
            }
        }
        $this->generateEntities();
        $this->manager->flush();
    }

    private function generateEntities()
    {
        foreach ($this->functionData as $cat => $functions) {
            $this->newEntity($cat, $functions);
        }
    }

    private function newEntity($cat, $functions)
    {
        $catFound = false;
        // Check if the function category doesn't exist in Myddleware
        if (!empty($this->functionCats)) {
            foreach ($this->functionCats as $funcCat) {
                if ($funcCat->getName() == $cat) {
                    $catFound = true;
                    break;
                }
            }
        }
        // If it doesn't exist, we create it
        if (!$catFound) {
            $funcCat = new FuncCat();
            $funcCat->setName($cat);
            $this->manager->persist($funcCat);
        }

        // Check each function of the category
        foreach ($functions as $function) {
            // Ff the function  doesn't exist in Myddleware, then we create it
            if (
                    empty($this->functions[$cat])
                || false === array_search($function, $this->functions[$cat])
            ) {
                $func = new Functions();
                $func->setName($function);
                $func->setCategoryId($funcCat);
                $this->manager->persist($func);
            }
        }
    }

    public static function getGroups(): array
    {
        return ['functions', 'mydconfig'];
    }

    public function getDependencies()
    {
        return [
            FuncCatFixtures::class,
        ];
    }
}
