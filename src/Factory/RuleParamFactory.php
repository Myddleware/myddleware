<?php

namespace App\Factory;

use App\Entity\RuleParam;
use App\Repository\RuleParamRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<RuleParam>
 *
 * @method static          RuleParam|Proxy createOne(array $attributes = [])
 * @method static          RuleParam[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static          RuleParam|Proxy find(object|array|mixed $criteria)
 * @method static          RuleParam|Proxy findOrCreate(array $attributes)
 * @method static          RuleParam|Proxy first(string $sortedField = 'id')
 * @method static          RuleParam|Proxy last(string $sortedField = 'id')
 * @method static          RuleParam|Proxy random(array $attributes = [])
 * @method static          RuleParam|Proxy randomOrCreate(array $attributes = [])
 * @method static          RuleParam[]|Proxy[] all()
 * @method static          RuleParam[]|Proxy[] findBy(array $attributes)
 * @method static          RuleParam[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static          RuleParam[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static          RuleParamRepository|RepositoryProxy repository()
 * @method RuleParam|Proxy create(array|callable $attributes = [])
 */
final class RuleParamFactory extends ModelFactory
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
            'value' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(RuleParam $ruleParam): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleParam::class;
    }
}
