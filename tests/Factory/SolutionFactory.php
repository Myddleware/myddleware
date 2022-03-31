<?php

namespace App\Tests\Factory;

use App\Entity\Solution;
use App\Repository\SolutionRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Solution>
 *
 * @method static         Solution|Proxy createOne(array $attributes = [])
 * @method static         Solution[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static         Solution|Proxy find(object|array|mixed $criteria)
 * @method static         Solution|Proxy findOrCreate(array $attributes)
 * @method static         Solution|Proxy first(string $sortedField = 'id')
 * @method static         Solution|Proxy last(string $sortedField = 'id')
 * @method static         Solution|Proxy random(array $attributes = [])
 * @method static         Solution|Proxy randomOrCreate(array $attributes = [])
 * @method static         Solution[]|Proxy[] all()
 * @method static         Solution[]|Proxy[] findBy(array $attributes)
 * @method static         Solution[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static         Solution[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static         SolutionRepository|RepositoryProxy repository()
 * @method Solution|Proxy create(array|callable $attributes = [])
 */
final class SolutionFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->text(),
            'active' => self::faker()->randomNumber(),
            'source' => self::faker()->randomNumber(),
            'target' => self::faker()->randomNumber(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Solution $solution): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Solution::class;
    }
}
