<?php

namespace App\Factory;

use Shapecode\Bundle\CronBundle\Entity\CronJobResult;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<CronJobResult>
 *
 * @method static              CronJobResult|Proxy createOne(array $attributes = [])
 * @method static              CronJobResult[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static              CronJobResult|Proxy find(object|array|mixed $criteria)
 * @method static              CronJobResult|Proxy findOrCreate(array $attributes)
 * @method static              CronJobResult|Proxy first(string $sortedField = 'id')
 * @method static              CronJobResult|Proxy last(string $sortedField = 'id')
 * @method static              CronJobResult|Proxy random(array $attributes = [])
 * @method static              CronJobResult|Proxy randomOrCreate(array $attributes = [])
 * @method static              CronJobResult[]|Proxy[] all()
 * @method static              CronJobResult[]|Proxy[] findBy(array $attributes)
 * @method static              CronJobResult[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static              CronJobResult[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method CronJobResult|Proxy create(array|callable $attributes = [])
 */
final class CronJobResultFactory extends ModelFactory
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
            'runAt' => null, // TODO add DATETIME ORM type manually
            'runTime' => self::faker()->randomFloat(),
            'statusCode' => self::faker()->randomNumber(),
            'createdAt' => null, // TODO add DATETIME ORM type manually
            'updatedAt' => null, // TODO add DATETIME ORM type manually
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(CronJobResult $cronJobResult): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CronJobResult::class;
    }
}
