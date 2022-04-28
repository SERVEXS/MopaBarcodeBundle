<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

final class Mask
{
    public $runLength = [];

    public function __construct()
    {
        $this->runLength = array_fill(0, Constants::QRSPEC_WIDTH_MAX + 1, 0);
    }

    public function writeFormatInformation($width, &$frame, $mask, $level)
    {
        $blacks = 0;
        $format = Spec::getFormatInfo($mask, $level);

        for ($i = 0; $i < 8; ++$i) {
            if ($format & 1) {
                $blacks += 2;
                $v = 0x85;
            } else {
                $v = 0x84;
            }

            $frame[8][$width - 1 - $i] = chr($v);
            if ($i < 6) {
                $frame[$i][8] = chr($v);
            } else {
                $frame[$i + 1][8] = chr($v);
            }
            $format = $format >> 1;
        }

        for ($i = 0; $i < 7; ++$i) {
            if ($format & 1) {
                $blacks += 2;
                $v = 0x85;
            } else {
                $v = 0x84;
            }

            $frame[$width - 7 + $i][8] = chr($v);
            if (0 == $i) {
                $frame[8][7] = chr($v);
            } else {
                $frame[8][6 - $i] = chr($v);
            }

            $format = $format >> 1;
        }

        return $blacks;
    }

    //----------------------------------------------------------------------
    public function mask0($x, $y)
    {
        return ($x + $y) & 1;
    }

    public function mask1($x, $y)
    {
        return $y & 1;
    }

    public function mask2($x, $y)
    {
        return $x % 3;
    }

    public function mask3($x, $y)
    {
        return ($x + $y) % 3;
    }

    public function mask4($x, $y)
    {
        return (((int) ($y / 2)) + ((int) ($x / 3))) & 1;
    }

    public function mask5($x, $y)
    {
        return (($x * $y) & 1) + ($x * $y) % 3;
    }

    public function mask6($x, $y)
    {
        return ((($x * $y) & 1) + ($x * $y) % 3) & 1;
    }

    public function mask7($x, $y)
    {
        return ((($x * $y) % 3) + (($x + $y) & 1)) & 1;
    }

    private function generateMaskNo($maskNo, $width, $frame)
    {
        $bitMask = array_fill(0, $width, array_fill(0, $width, 0));

        for ($y = 0; $y < $width; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                if (ord($frame[$y][$x]) & 0x80) {
                    $bitMask[$y][$x] = 0;
                } else {
                    $maskFunc = call_user_func([$this, 'mask'.$maskNo], $x, $y);
                    $bitMask[$y][$x] = (0 == $maskFunc) ? 1 : 0;
                }
            }
        }

        return $bitMask;
    }

    public static function serial($bitFrame)
    {
        $codeArr = [];

        foreach ($bitFrame as $line) {
            $codeArr[] = implode('', $line);
        }

        return gzcompress(implode("\n", $codeArr), 9);
    }

    public static function unserial($code)
    {
        $codeArr = [];

        $codeLines = explode("\n", gzuncompress($code));
        foreach ($codeLines as $line) {
            $codeArr[] = str_split($line);
        }

        return $codeArr;
    }

    public function makeMaskNo($maskNo, $width, $s, &$d, $maskGenOnly = false)
    {
        $b = 0;
        $bitMask = [];

        $fileName = Config::getCacheDir() . 'mask_' . $maskNo . DIRECTORY_SEPARATOR . 'mask_' . $width . '_' . $maskNo . '.dat';

        if (Config::isCachable()) {
            if (file_exists($fileName)) {
                $bitMask = self::unserial(file_get_contents($fileName));
            } else {
                $bitMask = $this->generateMaskNo($maskNo, $width, $s, $d);
                if (!file_exists(Config::getCacheDir() . 'mask_' . $maskNo)) {
                    mkdir(Config::getCacheDir() . 'mask_' . $maskNo);
                }
                file_put_contents($fileName, self::serial($bitMask));
            }
        } else {
            $bitMask = $this->generateMaskNo($maskNo, $width, $s, $d);
        }

        if ($maskGenOnly) {
            return;
        }

        $d = $s;

        for ($y = 0; $y < $width; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                if (1 == $bitMask[$y][$x]) {
                    $d[$y][$x] = chr(ord($s[$y][$x]) ^ (int) $bitMask[$y][$x]);
                }
                $b += (int) (ord($d[$y][$x]) & 1);
            }
        }

        return $b;
    }

    public function makeMask($width, $frame, $maskNo, $level)
    {
        $masked = array_fill(0, $width, str_repeat("\0", $width));
        $this->makeMaskNo($maskNo, $width, $frame, $masked);
        $this->writeFormatInformation($width, $masked, $maskNo, $level);

        return $masked;
    }

    public function calcN1N3($length)
    {
        $demerit = 0;

        for ($i = 0; $i < $length; ++$i) {
            if ($this->runLength[$i] >= 5) {
                $demerit += (Constants::N1 + ($this->runLength[$i] - 5));
            }
            if ($i & 1) {
                if (($i >= 3) && ($i < ($length - 2)) && ($this->runLength[$i] % 3 == 0)) {
                    $fact = (int) ($this->runLength[$i] / 3);
                    if (($this->runLength[$i - 2] == $fact) &&
                        ($this->runLength[$i - 1] == $fact) &&
                        ($this->runLength[$i + 1] == $fact) &&
                        ($this->runLength[$i + 2] == $fact)) {
                        if (($this->runLength[$i - 3] < 0) || ($this->runLength[$i - 3] >= (4 * $fact))) {
                            $demerit += Constants::N3;
                        } elseif ((($i + 3) >= $length) || ($this->runLength[$i + 3] >= (4 * $fact))) {
                            $demerit += Constants::N3;
                        }
                    }
                }
            }
        }

        return $demerit;
    }

    public function evaluateSymbol($width, $frame)
    {
        $head = 0;
        $demerit = 0;

        for ($y = 0; $y < $width; ++$y) {
            $head = 0;
            $this->runLength[0] = 1;

            $frameY = $frame[$y];

            if ($y > 0) {
                $frameYM = $frame[$y - 1];
            }

            for ($x = 0; $x < $width; ++$x) {
                if (($x > 0) && ($y > 0)) {
                    $b22 = ord($frameY[$x]) & ord($frameY[$x - 1]) & ord($frameYM[$x]) & ord($frameYM[$x - 1]);
                    $w22 = ord($frameY[$x]) | ord($frameY[$x - 1]) | ord($frameYM[$x]) | ord($frameYM[$x - 1]);

                    if (($b22 | ($w22 ^ 1)) & 1) {
                        $demerit += Constants::N2;
                    }
                }
                if ((0 == $x) && (ord($frameY[$x]) & 1)) {
                    $this->runLength[0] = -1;
                    $head = 1;
                    $this->runLength[$head] = 1;
                } elseif ($x > 0) {
                    if ((ord($frameY[$x]) ^ ord($frameY[$x - 1])) & 1) {
                        ++$head;
                        $this->runLength[$head] = 1;
                    } else {
                        ++$this->runLength[$head];
                    }
                }
            }

            $demerit += $this->calcN1N3($head + 1);
        }

        for ($x = 0; $x < $width; ++$x) {
            $head = 0;
            $this->runLength[0] = 1;

            for ($y = 0; $y < $width; ++$y) {
                if (0 == $y && (ord($frame[$y][$x]) & 1)) {
                    $this->runLength[0] = -1;
                    $head = 1;
                    $this->runLength[$head] = 1;
                } elseif ($y > 0) {
                    if ((ord($frame[$y][$x]) ^ ord($frame[$y - 1][$x])) & 1) {
                        ++$head;
                        $this->runLength[$head] = 1;
                    } else {
                        ++$this->runLength[$head];
                    }
                }
            }

            $demerit += $this->calcN1N3($head + 1);
        }

        return $demerit;
    }

    public function mask($width, $frame, $level)
    {
        $minDemerit = PHP_INT_MAX;
        $bestMaskNum = 0;
        $bestMask = [];

        $checked_masks = [0, 1, 2, 3, 4, 5, 6, 7];

        if (Constants::QR_FIND_FROM_RANDOM !== false) {
            $howManuOut = 8 - (Constants::QR_FIND_FROM_RANDOM % 9);
            for ($i = 0; $i < $howManuOut; ++$i) {
                $remPos = random_int(0, count($checked_masks) - 1);
                unset($checked_masks[$remPos]);
                $checked_masks = array_values($checked_masks);
            }
        }

        $bestMask = $frame;

        foreach ($checked_masks as $i) {
            $mask = array_fill(0, $width, str_repeat("\0", $width));

            $demerit = 0;
            $blacks = 0;

            if ($maskNo = $this->makeMaskNo($i, $width, $frame, $mask)) {
                $blacks = $maskNo;
            }
            $blacks += $this->writeFormatInformation($width, $mask, $i, $level);
            $blacks = (int) (100 * $blacks / ($width * $width));
            $demerit = (int) ((int) (abs($blacks - 50) / 5) * Constants::N4);
            $demerit += $this->evaluateSymbol($width, $mask);

            if ($demerit < $minDemerit) {
                $minDemerit = $demerit;
                $bestMask = $mask;
                $bestMaskNum = $i;
            }
        }

        return $bestMask;
    }
}
