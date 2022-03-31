<?php

namespace App\Tests\Factory;

use App\Entity\Functions;
use App\Repository\FunctionsRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Functions>
 *
 * @method static          Functions|Proxy createOne(array $attributes = [])
 * @method static          Functions[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static          Functions|Proxy find(object|array|mixed $criteria)
 * @method static          Functions|Proxy findOrCreate(array $attributes)
 * @method static          Functions|Proxy first(string $sortedField = 'id')
 * @method static          Functions|Proxy last(string $sortedField = 'id')
 * @method static          Functions|Proxy random(array $attributes = [])
 * @method static          Functions|Proxy randomOrCreate(array $attributes = [])
 * @method static          Functions[]|Proxy[] all()
 * @method static          Functions[]|Proxy[] findBy(array $attributes)
 * @method static          Functions[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static          Functions[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static          FunctionsRepository|RepositoryProxy repository()
 * @method Functions|Proxy create(array|callable $attributes = [])
 */
final class FunctionsFactory extends ModelFactory
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
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Functions $functions): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Functions::class;
    }
}
