<?php

namespace App\Tests\Factory;

use App\Entity\DocumentData;
use App\Repository\DocumentDataRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DocumentData>
 *
 * @method static             DocumentData|Proxy createOne(array $attributes = [])
 * @method static             DocumentData[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static             DocumentData|Proxy find(object|array|mixed $criteria)
 * @method static             DocumentData|Proxy findOrCreate(array $attributes)
 * @method static             DocumentData|Proxy first(string $sortedField = 'id')
 * @method static             DocumentData|Proxy last(string $sortedField = 'id')
 * @method static             DocumentData|Proxy random(array $attributes = [])
 * @method static             DocumentData|Proxy randomOrCreate(array $attributes = [])
 * @method static             DocumentData[]|Proxy[] all()
 * @method static             DocumentData[]|Proxy[] findBy(array $attributes)
 * @method static             DocumentData[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static             DocumentData[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static             DocumentDataRepository|RepositoryProxy repository()
 * @method DocumentData|Proxy create(array|callable $attributes = [])
 */
final class DocumentDataFactory extends ModelFactory
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
            'type' => self::faker()->text(),
            'data' => [],
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(DocumentData $documentData): void {})
        ;
    }

    protected static function getClass(): string
    {
        return DocumentData::class;
    }
}
