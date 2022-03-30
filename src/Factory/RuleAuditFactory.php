<?php

namespace App\Factory;

use App\Entity\RuleAudit;
use App\Repository\RuleAuditRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<RuleAudit>
 *
 * @method static RuleAudit|Proxy createOne(array $attributes = [])
 * @method static RuleAudit[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static RuleAudit|Proxy find(object|array|mixed $criteria)
 * @method static RuleAudit|Proxy findOrCreate(array $attributes)
 * @method static RuleAudit|Proxy first(string $sortedField = 'id')
 * @method static RuleAudit|Proxy last(string $sortedField = 'id')
 * @method static RuleAudit|Proxy random(array $attributes = [])
 * @method static RuleAudit|Proxy randomOrCreate(array $attributes = [])
 * @method static RuleAudit[]|Proxy[] all()
 * @method static RuleAudit[]|Proxy[] findBy(array $attributes)
 * @method static RuleAudit[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static RuleAudit[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static RuleAuditRepository|RepositoryProxy repository()
 * @method RuleAudit|Proxy create(array|callable $attributes = [])
 */
final class RuleAuditFactory extends ModelFactory
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
            'data' => [],
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(RuleAudit $ruleAudit): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleAudit::class;
    }
}
