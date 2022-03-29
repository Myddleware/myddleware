<?php

namespace App\Tests\Factory;

use App\Entity\RuleRelationShip;
use App\Repository\RuleRelationShipRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<RuleRelationShip>
 *
 * @method static RuleRelationShip|Proxy createOne(array $attributes = [])
 * @method static RuleRelationShip[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static RuleRelationShip|Proxy find(object|array|mixed $criteria)
 * @method static RuleRelationShip|Proxy findOrCreate(array $attributes)
 * @method static RuleRelationShip|Proxy first(string $sortedField = 'id')
 * @method static RuleRelationShip|Proxy last(string $sortedField = 'id')
 * @method static RuleRelationShip|Proxy random(array $attributes = [])
 * @method static RuleRelationShip|Proxy randomOrCreate(array $attributes = [])
 * @method static RuleRelationShip[]|Proxy[] all()
 * @method static RuleRelationShip[]|Proxy[] findBy(array $attributes)
 * @method static RuleRelationShip[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static RuleRelationShip[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static RuleRelationShipRepository|RepositoryProxy repository()
 * @method RuleRelationShip|Proxy create(array|callable $attributes = [])
 */
final class RuleRelationShipFactory extends ModelFactory
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
            'fieldNameSource' => self::faker()->text(),
            'fieldNameTarget' => self::faker()->text(),
            'deleted' => self::faker()->boolean(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(RuleRelationShip $ruleRelationShip): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleRelationShip::class;
    }
}
