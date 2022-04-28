<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

final class Constants
{
    public const STRUCTURE_HEADER_BITS = 20;
    public const MAX_STRUCTURED_SYMBOLS = 16;

    public const QR_FIND_BEST_MASK = true;

    public const QRCAP_REMINDER = 2;
    public const QRCAP_WORDS = 1;

    public const QR_DEFAULT_MASK = 2;
    public const QR_IMAGE = true;

    public const QRCAP_EC = 3;

    public const PNG_MAX_SIZE = 1024;

    public const N1 = 3;
    public const N2 = 3;
    public const N3 = 40;
    public const N4 = 10;

    public const EC_LEVEL_L = 0;
    public const EC_LEVEL_Q = 2;
    public const EC_LEVEL_M = 1;
    public const EC_LEVEL_H = 3;

    public const QRCAP_WIDTH = 0;

    public const FORMAT_TEXT = 0;
    public const FORMAT_PNG = 1;

    public const QR_MODE_NUL = -1;
    public const QR_MODE_NUM = 0;
    public const QR_MODE_AN = 1;
    public const QR_MODE_KANJI = 3;
    public const QR_MODE_STRUCTURE = 4;
    public const QR_MODE_8 = 8;

    public const QR_FIND_FROM_RANDOM = false;

    public const QRSPEC_VERSION_MAX = 40;
    public const QRSPEC_WIDTH_MAX = 177;
}
