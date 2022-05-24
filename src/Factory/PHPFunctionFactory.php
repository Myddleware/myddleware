<?php

namespace App\Factory;

use App\Entity\PHPFunction;
use App\Repository\PHPFunctionRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<PHPFunction>
 *
 * @method static PHPFunction|Proxy createOne(array $attributes = [])
 * @method static PHPFunction[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static PHPFunction|Proxy find(object|array|mixed $criteria)
 * @method static PHPFunction|Proxy findOrCreate(array $attributes)
 * @method static PHPFunction|Proxy first(string $sortedField = 'id')
 * @method static PHPFunction|Proxy last(string $sortedField = 'id')
 * @method static PHPFunction|Proxy random(array $attributes = [])
 * @method static PHPFunction|Proxy randomOrCreate(array $attributes = [])
 * @method static PHPFunction[]|Proxy[] all()
 * @method static PHPFunction[]|Proxy[] findBy(array $attributes)
 * @method static PHPFunction[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static PHPFunction[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static PHPFunctionRepository|RepositoryProxy repository()
 * @method PHPFunction|Proxy create(array|callable $attributes = [])
 */
final class PHPFunctionFactory extends ModelFactory
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
            // ->afterInstantiate(function(PHPFunction $pHPFunction): void {})
        ;
    }

    protected static function getClass(): string
    {
        return PHPFunction::class;
    }
}
