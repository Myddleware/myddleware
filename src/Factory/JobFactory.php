<?php

namespace App\Factory;

use App\Entity\Job;
use App\Repository\JobRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Job>
 *
 * @method static    Job|Proxy createOne(array $attributes = [])
 * @method static    Job[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static    Job|Proxy find(object|array|mixed $criteria)
 * @method static    Job|Proxy findOrCreate(array $attributes)
 * @method static    Job|Proxy first(string $sortedField = 'id')
 * @method static    Job|Proxy last(string $sortedField = 'id')
 * @method static    Job|Proxy random(array $attributes = [])
 * @method static    Job|Proxy randomOrCreate(array $attributes = [])
 * @method static    Job[]|Proxy[] all()
 * @method static    Job[]|Proxy[] findBy(array $attributes)
 * @method static    Job[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static    Job[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static    JobRepository|RepositoryProxy repository()
 * @method Job|Proxy create(array|callable $attributes = [])
 */
final class JobFactory extends ModelFactory
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
            'status' => self::faker()->text(),
            'param' => self::faker()->text(),
            'begin' => null, // TODO add DATETIME ORM type manually
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Job $job): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Job::class;
    }
}
