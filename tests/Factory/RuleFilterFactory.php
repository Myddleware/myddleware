<?php

namespace App\Tests\Factory;

use App\Entity\RuleFilter;
use App\Repository\RuleFilterRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<RuleFilter>
 *
 * @method static RuleFilter|Proxy createOne(array $attributes = [])
 * @method static RuleFilter[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static RuleFilter|Proxy find(object|array|mixed $criteria)
 * @method static RuleFilter|Proxy findOrCreate(array $attributes)
 * @method static RuleFilter|Proxy first(string $sortedField = 'id')
 * @method static RuleFilter|Proxy last(string $sortedField = 'id')
 * @method static RuleFilter|Proxy random(array $attributes = [])
 * @method static RuleFilter|Proxy randomOrCreate(array $attributes = [])
 * @method static RuleFilter[]|Proxy[] all()
 * @method static RuleFilter[]|Proxy[] findBy(array $attributes)
 * @method static RuleFilter[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static RuleFilter[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static RuleFilterRepository|RepositoryProxy repository()
 * @method RuleFilter|Proxy create(array|callable $attributes = [])
 */
final class RuleFilterFactory extends ModelFactory
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
            'target' => self::faker()->text(),
            'type' => self::faker()->text(),
            'value' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(RuleFilter $ruleFilter): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleFilter::class;
    }
}
