<?php

namespace App\Tests\Factory;

use App\Entity\RuleRelationship;
use App\Repository\RuleRelationshipRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<RuleRelationship>
 *
 * @method static                 RuleRelationship|Proxy createOne(array $attributes = [])
 * @method static                 RuleRelationship[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static                 RuleRelationship|Proxy find(object|array|mixed $criteria)
 * @method static                 RuleRelationship|Proxy findOrCreate(array $attributes)
 * @method static                 RuleRelationship|Proxy first(string $sortedField = 'id')
 * @method static                 RuleRelationship|Proxy last(string $sortedField = 'id')
 * @method static                 RuleRelationship|Proxy random(array $attributes = [])
 * @method static                 RuleRelationship|Proxy randomOrCreate(array $attributes = [])
 * @method static                 RuleRelationship[]|Proxy[] all()
 * @method static                 RuleRelationship[]|Proxy[] findBy(array $attributes)
 * @method static                 RuleRelationship[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                 RuleRelationship[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                 RuleRelationshipRepository|RepositoryProxy repository()
 * @method RuleRelationship|Proxy create(array|callable $attributes = [])
 */
final class RuleRelationshipFactory extends ModelFactory
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
            // ->afterInstantiate(function(RuleRelationship $ruleRelationship): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleRelationship::class;
    }
}
