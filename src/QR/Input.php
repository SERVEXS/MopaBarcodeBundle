<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use Exception;
use RuntimeException;

final class Input
{
    /**
     * @var InputItem[]
     */
    public array $items = [];

    private $version;
    private $level;

    public function __construct($version = 0, $level = Constants::EC_LEVEL_L)
    {
        if ($version < 0 || $version > Constants::QRSPEC_VERSION_MAX || $level > Constants::EC_LEVEL_H) {
            throw new RuntimeException('Invalid version no');
        }

        $this->version = $version;
        $this->level = $level;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion($version): int
    {
        if ($version < 0 || $version > Constants::QRSPEC_VERSION_MAX) {
            throw new RuntimeException('Invalid version no');
        }

        $this->version = $version;

        return 0;
    }

    public function getErrorCorrectionLevel(): int
    {
        return $this->level;
    }

    public function setErrorCorrectionLevel($level): int
    {
        if ($level > Constants::EC_LEVEL_H) {
            throw new RuntimeException('Invalid ECLEVEL');
        }

        $this->level = $level;

        return 0;
    }

    public function appendEntry(InputItem $item): void
    {
        $this->items[] = $item;
    }

    public function append($mode, $size, $data): int
    {
        try {
            $entry = new InputItem($mode, $size, $data);
            $this->items[] = $entry;

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function insertStructuredAppendHeader($size, $index, $parity): int
    {
        if ($size > Constants::MAX_STRUCTURED_SYMBOLS) {
            throw new RuntimeException('insertStructuredAppendHeader wrong size');
        }

        if ($index <= 0 || $index > Constants::MAX_STRUCTURED_SYMBOLS) {
            throw new RuntimeException('insertStructuredAppendHeader wrong index');
        }

        $buf = [$size, $index, $parity];

        try {
            $entry = new InputItem(Constants::QR_MODE_STRUCTURE, 3, $buf);
            array_unshift($this->items, $entry);

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function calcParity()
    {
        $parity = 0;

        foreach ($this->items as $item) {
            if (Constants::QR_MODE_STRUCTURE !== $item->getMode()) {
                for ($i = $item->getSize() - 1; $i >= 0; --$i) {
                    $parity ^= $item->getData()[$i];
                }
            }
        }

        return $parity;
    }

    public static function checkModeNum($size, $data): bool
    {
        for ($i = 0; $i < $size; ++$i) {
            if ((ord($data[$i]) < ord('0')) || (ord($data[$i]) > ord('9'))) {
                return false;
            }
        }

        return true;
    }

    public static function estimateBitsModeNum($size)
    {
        $w = $size / 3;
        $bits = $w * 10;

        switch ($size - $w * 3) {
            case 1:
                $bits += 4;
                break;
            case 2:
                $bits += 7;
                break;
            default:
                break;
        }

        return $bits;
    }

    public static $anTable = [
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        36, -1, -1, -1, 37, 38, -1, -1, -1, -1, 39, 40, -1, 41, 42, 43,
        0,  1,  2,  3,  4,  5,  6,  7,  8,  9, 44, -1, -1, -1, -1, -1,
        -1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24,
        25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
    ];

    public static function lookAnTable($c): int
    {
        return ($c > 127) ? -1 : self::$anTable[$c];
    }

    public static function checkModeAn($size, $data): bool
    {
        for ($i = 0; $i < $size; ++$i) {
            if (-1 == self::lookAnTable(ord($data[$i]))) {
                return false;
            }
        }

        return true;
    }

    public static function estimateBitsModeAn($size): int
    {
        $w = ($size / 2);
        $bits = $w * 11;

        if ($size & 1) {
            $bits += 6;
        }

        return $bits;
    }

    public static function estimateBitsMode8($size): int
    {
        return $size * 8;
    }

    public static function estimateBitsModeKanji($size): int
    {
        return (int) (($size / 2) * 13);
    }

    public static function checkModeKanji($size, $data): bool
    {
        if ($size & 1) {
            return false;
        }

        for ($i = 0; $i < $size; $i += 2) {
            $val = (ord($data[$i]) << 8) | ord($data[$i + 1]);
            if ($val < 0x8140
                || ($val > 0x9ffc && $val < 0xe040)
                || $val > 0xebbf) {
                return false;
            }
        }

        return true;
    }

    public static function check($mode, $size, $data): bool
    {
        if ($size <= 0) {
            return false;
        }

        switch ($mode) {
            case Constants::QR_MODE_NUM:
                return self::checkModeNum($size, $data);
            case Constants::QR_MODE_AN:
                return self::checkModeAn($size, $data);
            case Constants::QR_MODE_KANJI:
                return self::checkModeKanji($size, $data);
            case Constants::QR_MODE_STRUCTURE:
            case Constants::QR_MODE_8:
                return true;
        }

        return false;
    }

    /**
     * @return float|int
     */
    public function estimateBitStreamSize(int $version)
    {
        $bits = 0;

        foreach ($this->items as $item) {
            $bits += $item->estimateBitStreamSizeOfEntry($version);
        }

        return $bits;
    }

    public function estimateVersion(): int
    {
        $version = 0;
        $prev = 0;
        do {
            $prev = $version;
            $bits = $this->estimateBitStreamSize($prev);
            $version = Spec::getMinimumVersion((int) (($bits + 7) / 8), $this->level);
            if ($version < 0) {
                return -1;
            }
        } while ($version > $prev);

        return $version;
    }

    public static function lengthOfCode($mode, $version, $bits): int
    {
        $payload = $bits - 4 - Spec::lengthIndicator($mode, $version);
        switch ($mode) {
            case Constants::QR_MODE_NUM:
                $chunks = (int) ($payload / 10);
                $remain = $payload - $chunks * 10;
                $size = $chunks * 3;
                if ($remain >= 7) {
                    $size += 2;
                } elseif ($remain >= 4) {
                    ++$size;
                }
                break;
            case Constants::QR_MODE_AN:
                $chunks = (int) ($payload / 11);
                $remain = $payload - $chunks * 11;
                $size = $chunks * 2;
                if ($remain >= 6) {
                    ++$size;
                }
                break;
            case Constants::QR_MODE_STRUCTURE:
            case Constants::QR_MODE_8:
                $size = (int) ($payload / 8);
                break;
            case Constants::QR_MODE_KANJI:
                $size = (int) (($payload / 13) * 2);
                break;
            default:
                $size = 0;
                break;
        }

        $maxsize = Spec::maximumWords($mode, $version);
        if ($size < 0) {
            $size = 0;
        }
        if ($size > $maxsize) {
            $size = $maxsize;
        }

        return $size;
    }

    public function createBitStream(): int
    {
        $total = 0;

        foreach ($this->items as $item) {
            $bits = $item->encodeBitStream($this->version);

            if ($bits < 0) {
                return -1;
            }

            $total += $bits;
        }

        return $total;
    }

    public function convertData()
    {
        $ver = $this->estimateVersion();
        if ($ver > $this->getVersion()) {
            $this->setVersion($ver);
        }

        for (;;) {
            $bits = $this->createBitStream();

            if ($bits < 0) {
                return -1;
            }

            $ver = Spec::getMinimumVersion((int) (($bits + 7) / 8), $this->level);
            if ($ver < 0) {
                throw new RuntimeException('WRONG VERSION');
            }

            if ($ver > $this->getVersion()) {
                $this->setVersion($ver);
            } else {
                break;
            }
        }

        return 0;
    }

    public function appendPaddingBit(&$bitStream)
    {
        $bits = $bitStream->size();
        $maxWords = Spec::getDataLength($this->version, $this->level);
        $maxBits = $maxWords * 8;

        if ($maxBits == $bits) {
            return 0;
        }

        if ($maxBits - $bits < 5) {
            return $bitStream->appendNum($maxBits - $bits, 0);
        }

        $bits += 4;
        $words = (int) (($bits + 7) / 8);

        $padding = new BitStream();
        $ret = $padding->appendNum($words * 8 - $bits + 4, 0);

        if ($ret < 0) {
            return $ret;
        }

        $padLength = $maxWords - $words;

        if ($padLength > 0) {
            $padBuffer = [];
            for ($i = 0; $i < $padLength; ++$i) {
                $padBuffer[$i] = ($i & 1) ? 0x11 : 0xec;
            }

            $ret = $padding->appendBytes($padLength, $padBuffer);

            if ($ret < 0) {
                return $ret;
            }
        }

        $ret = $bitStream->append($padding);

        return $ret;
    }

    public function mergeBitStream(): ?BitStream
    {
        if ($this->convertData() < 0) {
            return null;
        }

        $bitStream = new BitStream();

        foreach ($this->items as $item) {
            $ret = $bitStream->append($item->getBitStream());
            if ($ret < 0) {
                return null;
            }
        }

        return $bitStream;
    }

    public function getBitStream(): ?BitStream
    {
        $bitStream = $this->mergeBitStream();

        if (null === $bitStream) {
            return null;
        }

        $ret = $this->appendPaddingBit($bitStream);
        if ($ret < 0) {
            return null;
        }

        return $bitStream;
    }

    public function getByteStream(): ?array
    {
        $bitStream = $this->getBitStream();
        if (null === $bitStream) {
            return null;
        }

        return $bitStream->toByte();
    }
}
