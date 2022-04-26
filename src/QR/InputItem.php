<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use Exception;
use RuntimeException;

use function array_fill;
use function array_merge;
use function array_slice;
use function count;
use function implode;
use function ord;

final class InputItem
{
    private int $mode;

    private int $size;

    private array $data;

    private ?BitStream $bitStream;

    public function __construct($mode,  $size, $data, ?BitStream $bitStream = null)
    {
        $setData = array_slice($data, 0, $size);

        if (count($setData) < $size) {
            $setData = array_merge($setData, array_fill(0, $size - count($setData), 0));
        }

        if (!Input::check($mode, $size, $setData)) {
            throw new RuntimeException('Error m:' . $mode . ',s:' . $size . ',d:' . implode(',', $setData));
        }

        $this->mode = $mode;
        $this->size = $size;
        $this->data = $data;
        $this->bitStream = $bitStream;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getBitStream(): ?BitStream
    {
        return $this->bitStream;
    }

    public function encodeModeNum($version): int
    {
        try {
            $words = (int)($this->size / 3);
            $bitStream = new BitStream();

            $val = 0x1;
            $bitStream->appendNum(4, $val);
            $bitStream->appendNum(Spec::lengthIndicator(Config::QR_MODE_NUM, $version), $this->size);

            for ($i = 0; $i < $words; ++$i) {
                $val = (ord($this->data[$i * 3]) - ord('0')) * 100;
                $val += (ord($this->data[$i * 3 + 1]) - ord('0')) * 10;
                $val += (ord($this->data[$i * 3 + 2]) - ord('0'));
                $bitStream->appendNum(10, $val);
            }

            if (1 === $this->size - $words * 3) {
                $val = ord($this->data[$words * 3]) - ord('0');
                $bitStream->appendNum(4, $val);
            } elseif (2 === $this->size - $words * 3) {
                $val = (ord($this->data[$words * 3]) - ord('0')) * 10;
                $val += (ord($this->data[$words * 3 + 1]) - ord('0'));
                $bitStream->appendNum(7, $val);
            }

            $this->bitStream = $bitStream;

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeModeAn($version): int
    {
        try {
            $words = (int)($this->size / 2);
            $bitStream = new BitStream();

            $bitStream->appendNum(4, 0x02);
            $bitStream->appendNum(Spec::lengthIndicator(Config::QR_MODE_AN, $version), $this->size);

            for ($i = 0; $i < $words; ++$i) {
                $val = (int)Input::lookAnTable(ord($this->data[$i * 2])) * 45;
                $val += (int)Input::lookAnTable(ord($this->data[$i * 2 + 1]));

                $bitStream->appendNum(11, $val);
            }

            if ($this->size & 1) {
                $val = Input::lookAnTable(ord($this->data[$words * 2]));
                $bitStream->appendNum(6, $val);
            }

            $this->bitStream = $bitStream;

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeMode8(int $version): int
    {
        try {
            $bitStream = new BitStream();

            $bitStream->appendNum(4, 0x4);
            $bitStream->appendNum(Spec::lengthIndicator(Config::QR_MODE_8, $version), $this->size);

            for ($i = 0; $i < $this->size; ++$i) {
                $bitStream->appendNum(8, ord($this->data[$i]));
            }

            $this->bitStream = $bitStream;

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeModeKanji(int $version): int
    {
        try {
            $bitStream = new BitStream();

            $bitStream->appendNum(4, 0x8);
            $bitStream->appendNum(Spec::lengthIndicator(Config::QR_MODE_KANJI, $version), (int)($this->size / 2));

            for ($i = 0; $i < $this->size; $i += 2) {
                $val = (ord($this->data[$i]) << 8) | ord($this->data[$i + 1]);
                if ($val <= 0x9ffc) {
                    $val -= 0x8140;
                } else {
                    $val -= 0xc140;
                }

                $h = ($val >> 8) * 0xc0;
                $val = ($val & 0xff) + $h;

                $bitStream->appendNum(13, $val);
            }

            $this->bitStream = $bitStream;

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function encodeModeStructure(): int
    {
        try {
            $bitStream = new BitStream();

            $bitStream->appendNum(4, 0x03);
            $bitStream->appendNum(4, ord($this->data[1]) - 1);
            $bitStream->appendNum(4, ord($this->data[0]) - 1);
            $bitStream->appendNum(8, ord($this->data[2]));

            $this->bitStream = $bitStream;

            return 0;
        } catch (Exception $e) {
            return -1;
        }
    }

    public function estimateBitStreamSizeOfEntry($version): int
    {
        $bits = 0;

        if (0 === $version) {
            $version = 1;
        }

        switch ($this->mode) {
            case Config::QR_MODE_NUM:
                $bits = Input::estimateBitsModeNum($this->size);
                break;
            case Config::QR_MODE_AN:
                $bits = Input::estimateBitsModeAn($this->size);
                break;
            case Config::QR_MODE_8:
                $bits = Input::estimateBitsMode8($this->size);
                break;
            case Config::QR_MODE_KANJI:
                $bits = Input::estimateBitsModeKanji($this->size);
                break;
            case Config::QR_MODE_STRUCTURE:
                return Config::STRUCTURE_HEADER_BITS;
            default:
                return 0;
        }

        $l = Spec::lengthIndicator($this->mode, $version);
        $m = 1 << $l;
        $num = (int)(($this->size + $m - 1) / $m);

        $bits += $num * (4 + $l);

        return $bits;
    }

    public function encodeBitStream(int $version): int
    {
        try {
            unset($this->bitStream);
            $words = Spec::maximumWords($this->mode, $version);

            if ($this->size > $words) {
                $st1 = new InputItem($this->mode, $words, $this->data);
                $st2 = new InputItem($this->mode, $this->size - $words, array_slice($this->data, $words));

                $st1->encodeBitStream($version);
                $st2->encodeBitStream($version);

                $this->bitStream = new BitStream();
                $this->bitStream->append($st1->getBitStream());
                $this->bitStream->append($st2->getBitStream());

                unset($st1);
                unset($st2);
            } else {
                $ret = 0;

                switch ($this->mode) {
                    case Config::QR_MODE_NUM:
                        $ret = $this->encodeModeNum($version);
                        break;
                    case Config::QR_MODE_AN:
                        $ret = $this->encodeModeAn($version);
                        break;
                    case Config::QR_MODE_8:
                        $ret = $this->encodeMode8($version);
                        break;
                    case Config::QR_MODE_KANJI:
                        $ret = $this->encodeModeKanji($version);
                        break;
                    case Config::QR_MODE_STRUCTURE:
                        $ret = $this->encodeModeStructure();
                        break;

                    default:
                        break;
                }

                if ($ret < 0) {
                    return -1;
                }
            }

            return $this->bitStream->size();
        } catch (Exception $e) {
            return -1;
        }
    }
}
