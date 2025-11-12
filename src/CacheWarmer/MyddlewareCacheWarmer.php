<?php

namespace App\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\Filesystem\Filesystem;

class MyddlewareCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private string $cacheDir) {}

    public function warmup(string $cacheDir): array
    {
        $jobDir = $cacheDir . '/myddleware/job';
        $fs = new Filesystem();

        try {
            $fs->mkdir($jobDir, 0777);
        } catch (\Exception $e) {
            // Log but don't fail - directory might already exist
            // or permissions might prevent creation in some environments
        }

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }
}
