<?php

namespace App\Factory;

use App\Entity\PHPFunctionCategory;
use App\Repository\PHPFunctionCategoryRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<PHPFunctionCategory>
 *
 * @method static                    PHPFunctionCategory|Proxy createOne(array $attributes = [])
 * @method static                    PHPFunctionCategory[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static                    PHPFunctionCategory|Proxy find(object|array|mixed $criteria)
 * @method static                    PHPFunctionCategory|Proxy findOrCreate(array $attributes)
 * @method static                    PHPFunctionCategory|Proxy first(string $sortedField = 'id')
 * @method static                    PHPFunctionCategory|Proxy last(string $sortedField = 'id')
 * @method static                    PHPFunctionCategory|Proxy random(array $attributes = [])
 * @method static                    PHPFunctionCategory|Proxy randomOrCreate(array $attributes = [])
 * @method static                    PHPFunctionCategory[]|Proxy[] all()
 * @method static                    PHPFunctionCategory[]|Proxy[] findBy(array $attributes)
 * @method static                    PHPFunctionCategory[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                    PHPFunctionCategory[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                    PHPFunctionCategoryRepository|RepositoryProxy repository()
 * @method PHPFunctionCategory|Proxy create(array|callable $attributes = [])
 */
final class PHPFunctionCategoryFactory extends ModelFactory
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
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(PHPFunctionCategory $pHPFunctionCategory): void {})
        ;
    }

    protected static function getClass(): string
    {
        return PHPFunctionCategory::class;
    }
}
