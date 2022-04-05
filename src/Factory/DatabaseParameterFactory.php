<?php

namespace App\Factory;

use App\Entity\DatabaseParameter;
use App\Repository\DatabaseParameterRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DatabaseParameter>
 *
 * @method static                  DatabaseParameter|Proxy createOne(array $attributes = [])
 * @method static                  DatabaseParameter[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static                  DatabaseParameter|Proxy find(object|array|mixed $criteria)
 * @method static                  DatabaseParameter|Proxy findOrCreate(array $attributes)
 * @method static                  DatabaseParameter|Proxy first(string $sortedField = 'id')
 * @method static                  DatabaseParameter|Proxy last(string $sortedField = 'id')
 * @method static                  DatabaseParameter|Proxy random(array $attributes = [])
 * @method static                  DatabaseParameter|Proxy randomOrCreate(array $attributes = [])
 * @method static                  DatabaseParameter[]|Proxy[] all()
 * @method static                  DatabaseParameter[]|Proxy[] findBy(array $attributes)
 * @method static                  DatabaseParameter[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                  DatabaseParameter[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                  DatabaseParameterRepository|RepositoryProxy repository()
 * @method DatabaseParameter|Proxy create(array|callable $attributes = [])
 */
final class DatabaseParameterFactory extends ModelFactory
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
            'driver' => self::faker()->text(),
            'host' => self::faker()->domainName(),
            'port' => self::faker()->randomNumber(),
            'name' => self::faker()->text(),
            'user' => self::faker()->userName(),
            'secret' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(DatabaseParameter $databaseParameter): void {})
        ;
    }

    protected static function getClass(): string
    {
        return DatabaseParameter::class;
    }
}
