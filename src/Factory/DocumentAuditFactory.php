<?php

namespace App\Factory;

use App\Entity\DocumentAudit;
use App\Repository\DocumentAuditRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<DocumentAudit>
 *
 * @method static DocumentAudit|Proxy createOne(array $attributes = [])
 * @method static DocumentAudit[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static DocumentAudit|Proxy find(object|array|mixed $criteria)
 * @method static DocumentAudit|Proxy findOrCreate(array $attributes)
 * @method static DocumentAudit|Proxy first(string $sortedField = 'id')
 * @method static DocumentAudit|Proxy last(string $sortedField = 'id')
 * @method static DocumentAudit|Proxy random(array $attributes = [])
 * @method static DocumentAudit|Proxy randomOrCreate(array $attributes = [])
 * @method static DocumentAudit[]|Proxy[] all()
 * @method static DocumentAudit[]|Proxy[] findBy(array $attributes)
 * @method static DocumentAudit[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static DocumentAudit[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static DocumentAuditRepository|RepositoryProxy repository()
 * @method DocumentAudit|Proxy create(array|callable $attributes = [])
 */
final class DocumentAuditFactory extends ModelFactory
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
            'updatedAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(DocumentAudit $documentAudit): void {})
        ;
    }

    protected static function getClass(): string
    {
        return DocumentAudit::class;
    }
}
