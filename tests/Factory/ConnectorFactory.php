<?php

namespace App\Tests\Factory;

use App\Entity\Connector;
use App\Repository\ConnectorRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Connector>
 *
 * @method static          Connector|Proxy createOne(array $attributes = [])
 * @method static          Connector[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static          Connector|Proxy find(object|array|mixed $criteria)
 * @method static          Connector|Proxy findOrCreate(array $attributes)
 * @method static          Connector|Proxy first(string $sortedField = 'id')
 * @method static          Connector|Proxy last(string $sortedField = 'id')
 * @method static          Connector|Proxy random(array $attributes = [])
 * @method static          Connector|Proxy randomOrCreate(array $attributes = [])
 * @method static          Connector[]|Proxy[] all()
 * @method static          Connector[]|Proxy[] findBy(array $attributes)
 * @method static          Connector[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static          Connector[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static          ConnectorRepository|RepositoryProxy repository()
 * @method Connector|Proxy create(array|callable $attributes = [])
 */
final class ConnectorFactory extends ModelFactory
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
            'nameSlug' => self::faker()->text(),
            'deleted' => self::faker()->boolean(10),
            'createdAt' => self::faker()->datetime(),
            'updatedAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Connector $connector): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Connector::class;
    }
}
