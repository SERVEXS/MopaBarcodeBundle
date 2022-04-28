<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use RuntimeException;

final class Config
{
    public const PNG_MAX_SIZE = 1024;

    public const QR_MODE_NUL = -1;
    public const QR_MODE_NUM = 0;
    public const QR_MODE_AN = 1;
    public const QR_MODE_KANJI = 3;
    public const QR_MODE_STRUCTURE = 4;
    public const QR_MODE_8 = 8;

    public const EC_LEVEL_L = 0;
    public const EC_LEVEL_M = 1;
    public const EC_LEVEL_Q = 2;
    public const EC_LEVEL_H = 3;

    public const FORMAT_PNG = 1;
    public const FORMAT_TEXT = 0;

    public const MAX_STRUCTURED_SYMBOLS = 16;
    public const QRSPEC_VERSION_MAX = 40;
    public const STRUCTURE_HEADER_BITS = 20;

    public const QR_IMAGE = true;

    public const QRCAP_EC = 3;
    public const QRCAP_REMINDER = 2;
    public const QRCAP_WIDTH = 0;
    public const QRCAP_WORDS = 1;

    public const QR_FIND_BEST_MASK = true;
    public const QR_DEFAULT_MASK = 2;
    public const QR_FIND_FROM_RANDOM = false;
    public const QRSPEC_WIDTH_MAX = 177;

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

    private static function setCachable(bool $cachable): void
    {
        self::$cachable = $cachable;
    }
}
