<?php

namespace App\Factory;

use App\Entity\RuleOrder;
use App\Repository\RuleOrderRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<RuleOrder>
 *
 * @method static          RuleOrder|Proxy createOne(array $attributes = [])
 * @method static          RuleOrder[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static          RuleOrder|Proxy find(object|array|mixed $criteria)
 * @method static          RuleOrder|Proxy findOrCreate(array $attributes)
 * @method static          RuleOrder|Proxy first(string $sortedField = 'id')
 * @method static          RuleOrder|Proxy last(string $sortedField = 'id')
 * @method static          RuleOrder|Proxy random(array $attributes = [])
 * @method static          RuleOrder|Proxy randomOrCreate(array $attributes = [])
 * @method static          RuleOrder[]|Proxy[] all()
 * @method static          RuleOrder[]|Proxy[] findBy(array $attributes)
 * @method static          RuleOrder[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static          RuleOrder[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static          RuleOrderRepository|RepositoryProxy repository()
 * @method RuleOrder|Proxy create(array|callable $attributes = [])
 */
final class RuleOrderFactory extends ModelFactory
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
            'order' => self::faker()->randomNumber(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(RuleOrder $ruleOrder): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleOrder::class;
    }
}
