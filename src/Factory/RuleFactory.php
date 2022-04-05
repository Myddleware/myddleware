<?php

namespace App\Factory;

use App\Entity\Rule;
use App\Repository\RuleRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Rule>
 *
 * @method static     Rule|Proxy createOne(array $attributes = [])
 * @method static     Rule[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static     Rule|Proxy find(object|array|mixed $criteria)
 * @method static     Rule|Proxy findOrCreate(array $attributes)
 * @method static     Rule|Proxy first(string $sortedField = 'id')
 * @method static     Rule|Proxy last(string $sortedField = 'id')
 * @method static     Rule|Proxy random(array $attributes = [])
 * @method static     Rule|Proxy randomOrCreate(array $attributes = [])
 * @method static     Rule[]|Proxy[] all()
 * @method static     Rule[]|Proxy[] findBy(array $attributes)
 * @method static     Rule[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static     Rule[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static     RuleRepository|RepositoryProxy repository()
 * @method Rule|Proxy create(array|callable $attributes = [])
 */
final class RuleFactory extends ModelFactory
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
            'moduleSource' => self::faker()->text(),
            'moduleTarget' => self::faker()->text(),
            'active' => self::faker()->boolean(),
            'deleted' => self::faker()->boolean(),
            'name' => self::faker()->text(),
            'nameSlug' => self::faker()->text(),
            'createdAt' => self::faker()->datetime(),
            'updatedAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Rule $rule): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Rule::class;
    }
}
