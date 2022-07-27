<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\PHPFunction;
use App\Entity\PHPFunctionCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PHPFunctionFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
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

        // load all categories that already exist in the database
        $funcCats = $this->manager->getRepository(PHPFunctionCategory::class)->findAll();
        foreach ($funcCats as $funcCat) {
            $this->functionCats[$funcCat->getId()] = $funcCat;
        }
        // load all functions that already exist in the database
        $functions = $this->manager->getRepository(PHPFunction::class)->findAll();
        foreach ($functions as $function) {
            $this->functions[$function->getCategory()->getName()][$function->getId()] = $function->getName();
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
            $funcCat = new PHPFunctionCategory();
            $funcCat->setName($cat);
            $this->manager->persist($funcCat);
        }

        // Check each function of the category
        foreach ($functions as $function) {
            // Ff the function  doesn't exist in Myddleware, then we create it
            if (empty($this->functions[$cat]) || false === array_search($function, $this->functions[$cat])) {
                $func = new PHPFunction();
                $func->setName($function);
                $func->setCategory($funcCat);
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
            PHPFunctionCategoryFixtures::class,
        ];
    }
}
