<?php

namespace App\Tests\Factory;

use App\Entity\RuleParamAudit;
use App\Repository\RuleParamAuditRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<RuleParamAudit>
 *
 * @method static               RuleParamAudit|Proxy createOne(array $attributes = [])
 * @method static               RuleParamAudit[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static               RuleParamAudit|Proxy find(object|array|mixed $criteria)
 * @method static               RuleParamAudit|Proxy findOrCreate(array $attributes)
 * @method static               RuleParamAudit|Proxy first(string $sortedField = 'id')
 * @method static               RuleParamAudit|Proxy last(string $sortedField = 'id')
 * @method static               RuleParamAudit|Proxy random(array $attributes = [])
 * @method static               RuleParamAudit|Proxy randomOrCreate(array $attributes = [])
 * @method static               RuleParamAudit[]|Proxy[] all()
 * @method static               RuleParamAudit[]|Proxy[] findBy(array $attributes)
 * @method static               RuleParamAudit[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static               RuleParamAudit[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static               RuleParamAuditRepository|RepositoryProxy repository()
 * @method RuleParamAudit|Proxy create(array|callable $attributes = [])
 */
final class RuleParamAuditFactory extends ModelFactory
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
            'updatedAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(RuleParamAudit $ruleParamAudit): void {})
        ;
    }

    protected static function getClass(): string
    {
        return RuleParamAudit::class;
    }
}
