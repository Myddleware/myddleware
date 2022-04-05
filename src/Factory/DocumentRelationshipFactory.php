<?php

namespace App\Factory;

use App\Entity\DocumentRelationship;
use App\Repository\DocumentRelationshipRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<DocumentRelationship>
 *
 * @method static                     DocumentRelationship|Proxy createOne(array $attributes = [])
 * @method static                     DocumentRelationship[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static                     DocumentRelationship|Proxy find(object|array|mixed $criteria)
 * @method static                     DocumentRelationship|Proxy findOrCreate(array $attributes)
 * @method static                     DocumentRelationship|Proxy first(string $sortedField = 'id')
 * @method static                     DocumentRelationship|Proxy last(string $sortedField = 'id')
 * @method static                     DocumentRelationship|Proxy random(array $attributes = [])
 * @method static                     DocumentRelationship|Proxy randomOrCreate(array $attributes = [])
 * @method static                     DocumentRelationship[]|Proxy[] all()
 * @method static                     DocumentRelationship[]|Proxy[] findBy(array $attributes)
 * @method static                     DocumentRelationship[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                     DocumentRelationship[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                     DocumentRelationshipRepository|RepositoryProxy repository()
 * @method DocumentRelationship|Proxy create(array|callable $attributes = [])
 */
final class DocumentRelationshipFactory extends ModelFactory
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
            'doc_rel_id' => self::faker()->text(),
            'sourceField' => self::faker()->text(),
            'createdAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(DocumentRelationship $documentRelationship): void {})
        ;
    }

    protected static function getClass(): string
    {
        return DocumentRelationship::class;
    }
}
