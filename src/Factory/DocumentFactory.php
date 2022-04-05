<?php

namespace App\Factory;

use App\Entity\Document;
use App\Repository\DocumentRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Document>
 *
 * @method static         Document|Proxy createOne(array $attributes = [])
 * @method static         Document[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static         Document|Proxy find(object|array|mixed $criteria)
 * @method static         Document|Proxy findOrCreate(array $attributes)
 * @method static         Document|Proxy first(string $sortedField = 'id')
 * @method static         Document|Proxy last(string $sortedField = 'id')
 * @method static         Document|Proxy random(array $attributes = [])
 * @method static         Document|Proxy randomOrCreate(array $attributes = [])
 * @method static         Document[]|Proxy[] all()
 * @method static         Document[]|Proxy[] findBy(array $attributes)
 * @method static         Document[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static         Document[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static         DocumentRepository|RepositoryProxy repository()
 * @method Document|Proxy create(array|callable $attributes = [])
 */
final class DocumentFactory extends ModelFactory
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
            'dateCreated' => self::faker()->datetime(),
            'dateModified' => self::faker()->datetime(),
            'attempt' => self::faker()->randomNumber(),
            'globalStatus' => self::faker()->text(),
            'deleted' => self::faker()->boolean(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Document $document): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Document::class;
    }
}
