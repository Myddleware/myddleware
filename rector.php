<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;

<<<<<<< HEAD
return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->phpVersion(phpVersion: PhpVersion::PHP_81);
    $rectorConfig->autoloadPaths(autoloadPaths: [
        __DIR__ . '/vendor/autoload.php',
    ]);
    $rectorConfig->sets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::PHP_81,
        SetList::UNWRAP_COMPAT,
        SetList::TYPE_DECLARATION,
        SetList::TYPE_DECLARATION_STRICT,
        SetList::CODING_STYLE,
    ]);
=======
return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src'
    ]);

    // Define what rule sets will be applied
    $containerConfigurator->import(SetList::DEAD_CODE);
    $containerConfigurator->import(SetList::CODE_QUALITY);

    // get services (needed for register a single rule)
    $services = $containerConfigurator->services();

    // register a single rule
   //  $services->set(TypedPropertyRector::class);
>>>>>>> c37b32c (refactor: Rector: Code Quality run)
};
