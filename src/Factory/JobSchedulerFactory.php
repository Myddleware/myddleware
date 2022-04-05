<?php

namespace App\Factory;

use App\Entity\JobScheduler;
use App\Repository\JobSchedulerRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<JobScheduler>
 *
 * @method static             JobScheduler|Proxy createOne(array $attributes = [])
 * @method static             JobScheduler[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static             JobScheduler|Proxy find(object|array|mixed $criteria)
 * @method static             JobScheduler|Proxy findOrCreate(array $attributes)
 * @method static             JobScheduler|Proxy first(string $sortedField = 'id')
 * @method static             JobScheduler|Proxy last(string $sortedField = 'id')
 * @method static             JobScheduler|Proxy random(array $attributes = [])
 * @method static             JobScheduler|Proxy randomOrCreate(array $attributes = [])
 * @method static             JobScheduler[]|Proxy[] all()
 * @method static             JobScheduler[]|Proxy[] findBy(array $attributes)
 * @method static             JobScheduler[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static             JobScheduler[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static             JobSchedulerRepository|RepositoryProxy repository()
 * @method JobScheduler|Proxy create(array|callable $attributes = [])
 */
final class JobSchedulerFactory extends ModelFactory
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
            'period' => self::faker()->randomNumber(),
            'active' => self::faker()->boolean(),
            'createdAt' => self::faker()->datetime(),
            'updatedAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(JobScheduler $jobScheduler): void {})
        ;
    }

    protected static function getClass(): string
    {
        return JobScheduler::class;
    }
}
