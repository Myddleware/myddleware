<?php

namespace App\Tests\Factory;

use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<CronJob>
 *
 * @method static        CronJob|Proxy createOne(array $attributes = [])
 * @method static        CronJob[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static        CronJob|Proxy find(object|array|mixed $criteria)
 * @method static        CronJob|Proxy findOrCreate(array $attributes)
 * @method static        CronJob|Proxy first(string $sortedField = 'id')
 * @method static        CronJob|Proxy last(string $sortedField = 'id')
 * @method static        CronJob|Proxy random(array $attributes = [])
 * @method static        CronJob|Proxy randomOrCreate(array $attributes = [])
 * @method static        CronJob[]|Proxy[] all()
 * @method static        CronJob[]|Proxy[] findBy(array $attributes)
 * @method static        CronJob[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static        CronJob[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method CronJob|Proxy create(array|callable $attributes = [])
 */
final class CronJobFactory extends ModelFactory
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
            'command' => self::faker()->text(),
            'runningInstances' => self::faker()->randomNumber(),
            'maxInstances' => self::faker()->randomNumber(),
            'number' => self::faker()->randomNumber(),
            'period' => self::faker()->text(),
            'nextRun' => null, // TODO add DATETIME ORM type manually
            'enable' => self::faker()->boolean(),
            'createdAt' => null, // TODO add DATETIME ORM type manually
            'updatedAt' => null, // TODO add DATETIME ORM type manually
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(CronJob $cronJob): void {})
        ;
    }

    protected static function getClass(): string
    {
        return CronJob::class;
    }
}
