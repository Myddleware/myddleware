<?php

namespace App\Factory;

use App\Entity\ResetPasswordRequest;
use App\Repository\ResetPasswordRequestRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<ResetPasswordRequest>
 *
 * @method static ResetPasswordRequest|Proxy createOne(array $attributes = [])
 * @method static ResetPasswordRequest[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static ResetPasswordRequest|Proxy find(object|array|mixed $criteria)
 * @method static ResetPasswordRequest|Proxy findOrCreate(array $attributes)
 * @method static ResetPasswordRequest|Proxy first(string $sortedField = 'id')
 * @method static ResetPasswordRequest|Proxy last(string $sortedField = 'id')
 * @method static ResetPasswordRequest|Proxy random(array $attributes = [])
 * @method static ResetPasswordRequest|Proxy randomOrCreate(array $attributes = [])
 * @method static ResetPasswordRequest[]|Proxy[] all()
 * @method static ResetPasswordRequest[]|Proxy[] findBy(array $attributes)
 * @method static ResetPasswordRequest[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static ResetPasswordRequest[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static ResetPasswordRequestRepository|RepositoryProxy repository()
 * @method ResetPasswordRequest|Proxy create(array|callable $attributes = [])
 */
final class ResetPasswordRequestFactory extends ModelFactory
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
            'selector' => self::faker()->text(),
            'hashedToken' => self::faker()->text(),
            'requestedAt' => self::faker()->datetime(),
            'expiresAt' => self::faker()->datetime(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(ResetPasswordRequest $resetPasswordRequest): void {})
        ;
    }

    protected static function getClass(): string
    {
        return ResetPasswordRequest::class;
    }
}
