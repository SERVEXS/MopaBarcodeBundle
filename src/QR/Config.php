<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use RuntimeException;

final class Config
{
    private static ?string $cacheDir = null;

    private static ?string $logDir = null;

    private static bool $cachable = false;

    public static function initialize(?string $cacheDir =  null, ?string $logDir = null, bool $cachable = true): void
    {
        self::setCacheDir($cacheDir);
        self::setLogDir($logDir);

        self::$cachable = $cachable;
    }

    public static function getCacheDir(): ?string
    {
        return self::$cacheDir;
    }

    protected static function ensureDirExists(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }
    }

    private static function setCacheDir(?string $dir): void
    {
        if ($dir) {
            self::ensureDirExists($dir);
        }

        self::$cacheDir = $dir;
    }

    public static function getLogDir(): ?string
    {
        return self::$logDir;
    }

    private static function setLogDir(?string $dir): void
    {
        if ($dir) {
            self::ensureDirExists($dir);
        }

        self::$logDir = $dir;
    }

    public static function isCachable(): bool
    {
        return self::$cachable;
    }
}
