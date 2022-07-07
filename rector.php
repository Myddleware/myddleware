<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->symfonyContainerXml(__DIR__.'/var/cache/dev/App_KernelDevDebugContainer.xml');
//    $rectorConfig->phpVersion(phpVersion: PhpVersion::PHP_81);
//    $rectorConfig->autoloadPaths(autoloadPaths: [
//        __DIR__.'/vendor/autoload.php',
//    ]);
    $rectorConfig->sets([
//        SetList::CODE_QUALITY,
//        SetList::DEAD_CODE,
//        SetList::EARLY_RETURN,
//        SetList::PHP_81,
//        SetList::UNWRAP_COMPAT,
//        SetList::TYPE_DECLARATION,
//        SetList::TYPE_DECLARATION_STRICT,
//        SetList::CODING_STYLE,
//        SymfonySetList::SYMFONY_52,
        SymfonyLevelSetList::UP_TO_SYMFONY_54,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ]);
};
