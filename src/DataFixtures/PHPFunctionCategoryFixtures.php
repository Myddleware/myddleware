<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\PHPFunctionCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class PHPFunctionCategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public const MATHEMATICAL_FUNC_CAT_REFERENCE = 'mathematical-function';
    public const TEXT_FUNC_CAT_REFERENCE = 'text-function';
    public const DATE_FUNC_CAT_REFERENCE = 'date-function';

    protected $defaultFunctionCategories = [
        ['name' => 'mathematical'],
        ['name' => 'text'],
        ['name' => 'date'],
    ];

    public function load(ObjectManager $manager): void
    {
        $existingFunctionCategories = $manager->getRepository(PHPFunctionCategory::class)->findAll();
        if (!empty($existingFunctionCategories)) {
            foreach ($existingFunctionCategories as $existingFunctionCategory) {
                foreach ($this->defaultFunctionCategories as $defaultFunctionCategory) {
                    if ($existingFunctionCategory->getName() === $defaultFunctionCategory['name']) {
                        $functionCategory = new PHPFunctionCategory();
                        $functionCategory->setName($defaultFunctionCategory['name']);
                        $manager->persist($functionCategory);
                    }
                }
            }
        } else {
            $mathematical = new PHPFunctionCategory();
            $text = new PHPFunctionCategory();
            $date = new PHPFunctionCategory();

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
    }

    public static function getGroups(): array
    {
        return ['functions', 'mydconfig'];
    }
}
