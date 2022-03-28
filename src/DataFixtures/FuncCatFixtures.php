<?php

namespace App\DataFixtures;

use App\Entity\FuncCat;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

// TODO:;FIX THIS - Fixtures are ADDED even when the categories already exist in Database
class FuncCatFixtures extends Fixture implements FixtureGroupInterface
{
    public const MATHEMATICAL_FUNC_CAT_REFERENCE = 'mathematical-function';
    public const TEXT_FUNC_CAT_REFERENCE = 'text-function';
    public const DATE_FUNC_CAT_REFERENCE = 'date-function';

    public function load(ObjectManager $manager): void
    {
        $mathematical = new FuncCat();
        $text = new FuncCat();
        $date = new FuncCat();

        $mathematical->setName('mathematical');
        $text->setName('text');
        $date->setName('date');
        
        $manager->persist($mathematical);
        $manager->persist($text);
        $manager->persist($date);

        $manager->flush();
        // allows other fixtures to reference the Function Category fixture via the constant
        $this->addReference(self::MATHEMATICAL_FUNC_CAT_REFERENCE, $mathematical);
        $this->addReference(self::TEXT_FUNC_CAT_REFERENCE, $text);
        $this->addReference(self::DATE_FUNC_CAT_REFERENCE, $date);
    }

    public static function getGroups(): array
    {
        return ['functions', 'mydconfig'];
    }
}
