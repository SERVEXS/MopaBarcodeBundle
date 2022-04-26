<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use function array_fill;
use function array_merge;
use function array_values;
use function count;
use function is_null;

final class BitStream
{
    private array $data = [];

    public function size(): int
    {
        return count($this->data);
    }

    public function allocate($setLength): int
    {
        $this->data = array_fill(0, $setLength, 0);

        return 0;
    }

    public static function newFromNum($bits, $num): self
    {
        $bitStream = new self();
        $bitStream->allocate($bits);

        $mask = 1 << ($bits - 1);
        for ($i = 0; $i < $bits; ++$i) {
            if ($num & $mask) {
                $bitStream->getData()[$i] = 1;
            } else {
                $bitStream->getData()[$i] = 0;
            }
            $mask = $mask >> 1;
        }

        return $bitStream;
    }

    public static function newFromBytes($size, $data): self
    {
        $bitStream = new self();
        $bitStream->allocate($size * 8);
        $p = 0;

        for ($i = 0; $i < $size; ++$i) {
            $mask = 0x80;
            for ($j = 0; $j < 8; ++$j) {
                if ($data[$i] & $mask) {
                    $bitStream->getData()[$p] = 1;
                } else {
                    $bitStream->getData()[$p] = 0;
                }
                ++$p;
                $mask = $mask >> 1;
            }
        }

        return $bitStream;
    }

    public function append(?self $arg): int
    {
        if (is_null($arg)) {
            return -1;
        }

        if (0 == $arg->size()) {
            return 0;
        }

        if (0 == $this->size()) {
            $this->data = $arg->getData();

            return 0;
        }

        $this->data = array_values(array_merge($this->data, $arg->getData()));

        return 0;
    }

    /**
     * @param string|int $num
     */
    public function appendNum($bits, $num): int
    {
        if (0 == $bits) {
            return 0;
        }

        $bitStream = self::newFromNum($bits, $num);

        if (is_null($bitStream)) {
            return -1;
        }

        $ret = $this->append($bitStream);
        unset($bitStream);

        return $ret;
    }

    public function appendBytes($size, $data): int
    {
        if (0 == $size) {
            return 0;
        }

        $b = self::newFromBytes($size, $data);

        if (is_null($b)) {
            return -1;
        }

        $ret = $this->append($b);
        unset($b);

        return $ret;
    }

    public function toByte(): array
    {
        $size = $this->size();

        if (0 == $size) {
            return [];
        }

        $data = array_fill(0, (int) (($size + 7) / 8), 0);
        $bytes = (int) ($size / 8);

        $p = 0;

        for ($i = 0; $i < $bytes; ++$i) {
            $v = 0;
            for ($j = 0; $j < 8; ++$j) {
                $v = $v << 1;
                $v |= $this->data[$p];
                ++$p;
            }
            $data[$i] = $v;
        }

        if ($size & 7) {
            $v = 0;
            for ($j = 0; $j < ($size & 7); ++$j) {
                $v = $v << 1;
                $v |= $this->data[$p];
                ++$p;
            }
            $data[$bytes] = $v;
        }

        return $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
