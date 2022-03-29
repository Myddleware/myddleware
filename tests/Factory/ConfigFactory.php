<?php

namespace App\Tests\Factory;

use App\Entity\Config;
use App\Repository\ConfigRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Config>
 *
 * @method static Config|Proxy createOne(array $attributes = [])
 * @method static Config[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Config|Proxy find(object|array|mixed $criteria)
 * @method static Config|Proxy findOrCreate(array $attributes)
 * @method static Config|Proxy first(string $sortedField = 'id')
 * @method static Config|Proxy last(string $sortedField = 'id')
 * @method static Config|Proxy random(array $attributes = [])
 * @method static Config|Proxy randomOrCreate(array $attributes = [])
 * @method static Config[]|Proxy[] all()
 * @method static Config[]|Proxy[] findBy(array $attributes)
 * @method static Config[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Config[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ConfigRepository|RepositoryProxy repository()
 * @method Config|Proxy create(array|callable $attributes = [])
 */
final class ConfigFactory extends ModelFactory
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
            // ->afterInstantiate(function(Config $config): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Config::class;
    }
}
