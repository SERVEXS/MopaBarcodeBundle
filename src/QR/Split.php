<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use RuntimeException;

final class Split
{
    public $dataStr = '';
    public $input;
    public $modeHint;

    public function __construct($dataStr, $input, $modeHint)
    {
        $this->dataStr = $dataStr;
        $this->input = $input;
        $this->modeHint = $modeHint;
    }

    public static function isdigitat($str, $pos)
    {
        if ($pos >= strlen($str)) {
            return false;
        }

        return (ord($str[$pos]) >= ord('0')) && (ord($str[$pos]) <= ord('9'));
    }

    public static function isalnumat($str, $pos)
    {
        if ($pos >= strlen($str)) {
            return false;
        }

        return Input::lookAnTable(ord($str[$pos])) >= 0;
    }

    public function identifyMode($pos)
    {
        if ($pos >= strlen($this->dataStr)) {
            return Config::QR_MODE_NUL;
        }

        $c = $this->dataStr[$pos];

        if (self::isdigitat($this->dataStr, $pos)) {
            return Config::QR_MODE_NUM;
        }

        if (self::isalnumat($this->dataStr, $pos)) {
            return Config::QR_MODE_AN;
        }

        if (Config::QR_MODE_KANJI == $this->modeHint) {
            if ($pos + 1 < strlen($this->dataStr)) {
                $d = $this->dataStr[$pos + 1];
                $word = (ord($c) << 8) | ord($d);
                if (($word >= 0x8140 && $word <= 0x9ffc) || ($word >= 0xe040 && $word <= 0xebbf)) {
                    return Config::QR_MODE_KANJI;
                }
            }
        }

        return Config::QR_MODE_8;
    }

    public function eatNum()
    {
        $ln = Spec::lengthIndicator(Config::QR_MODE_NUM, $this->input->getVersion());

        $p = 0;
        while (self::isdigitat($this->dataStr, $p)) {
            ++$p;
        }

        $run = $p;
        $mode = $this->identifyMode($p);

        if (Config::QR_MODE_8 == $mode) {
            $dif = Input::estimateBitsModeNum($run) + 4 + $ln
                   + Input::estimateBitsMode8(1)         // + 4 + l8
                   - Input::estimateBitsMode8($run + 1); // - 4 - l8
            if ($dif > 0) {
                return $this->eat8();
            }
        }
        if (Config::QR_MODE_AN == $mode) {
            $dif = Input::estimateBitsModeNum($run) + 4 + $ln
                   + Input::estimateBitsModeAn(1)        // + 4 + la
                   - Input::estimateBitsModeAn($run + 1); // - 4 - la
            if ($dif > 0) {
                return $this->eatAn();
            }
        }

        $ret = $this->input->append(Config::QR_MODE_NUM, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function eatAn()
    {
        $la = Spec::lengthIndicator(Config::QR_MODE_AN, $this->input->getVersion());
        $ln = Spec::lengthIndicator(Config::QR_MODE_NUM, $this->input->getVersion());

        $p = 0;

        while (self::isalnumat($this->dataStr, $p)) {
            if (self::isdigitat($this->dataStr, $p)) {
                $q = $p;
                while (self::isdigitat($this->dataStr, $q)) {
                    ++$q;
                }

                $dif = Input::estimateBitsModeAn($p) // + 4 + la
                       + Input::estimateBitsModeNum($q - $p) + 4 + $ln
                       - Input::estimateBitsModeAn($q); // - 4 - la

                if ($dif < 0) {
                    break;
                } else {
                    $p = $q;
                }
            } else {
                ++$p;
            }
        }

        $run = $p;

        if (!self::isalnumat($this->dataStr, $p)) {
            $dif = Input::estimateBitsModeAn($run) + 4 + $la
                   + Input::estimateBitsMode8(1) // + 4 + l8
                   - Input::estimateBitsMode8($run + 1); // - 4 - l8
            if ($dif > 0) {
                return $this->eat8();
            }
        }

        $ret = $this->input->append(Config::QR_MODE_AN, $run, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function eatKanji()
    {
        $p = 0;

        while (Config::QR_MODE_KANJI == $this->identifyMode($p)) {
            $p += 2;
        }

        $ret = $this->input->append(Config::QR_MODE_KANJI, $p, str_split($this->dataStr));
        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function eat8()
    {
        $la = Spec::lengthIndicator(Config::QR_MODE_AN, $this->input->getVersion());
        $ln = Spec::lengthIndicator(Config::QR_MODE_NUM, $this->input->getVersion());

        $p = 1;
        $dataStrLen = strlen($this->dataStr);

        while ($p < $dataStrLen) {
            $mode = $this->identifyMode($p);
            if (Config::QR_MODE_KANJI == $mode) {
                break;
            }
            if (Config::QR_MODE_NUM == $mode) {
                $q = $p;
                while (self::isdigitat($this->dataStr, $q)) {
                    ++$q;
                }
                $dif = Input::estimateBitsMode8($p) // + 4 + l8
                       + Input::estimateBitsModeNum($q - $p) + 4 + $ln
                       - Input::estimateBitsMode8($q); // - 4 - l8
                if ($dif < 0) {
                    break;
                }

                $p = $q;
            } elseif (Config::QR_MODE_AN == $mode) {
                $q = $p;
                while (self::isalnumat($this->dataStr, $q)) {
                    ++$q;
                }
                $dif = Input::estimateBitsMode8($p)  // + 4 + l8
                       + Input::estimateBitsModeAn($q - $p) + 4 + $la
                       - Input::estimateBitsMode8($q); // - 4 - l8
                if ($dif < 0) {
                    break;
                }

                $p = $q;
            } else {
                ++$p;
            }
        }

        $run = $p;
        $ret = $this->input->append(Config::QR_MODE_8, $run, str_split($this->dataStr));

        if ($ret < 0) {
            return -1;
        }

        return $run;
    }

    public function splitString()
    {
        while (strlen($this->dataStr) > 0) {
            if ('' == $this->dataStr) {
                return 0;
            }

            $mode = $this->identifyMode(0);

            switch ($mode) {
                case Config::QR_MODE_NUM:
                    $length = $this->eatNum();
                    break;
                case Config::QR_MODE_AN:
                    $length = $this->eatAn();
                    break;
                case Config::QR_MODE_KANJI:
                    $length = $this->eat8();
                    if (Config::QR_MODE_KANJI == $hint) {
                        $length = $this->eatKanji();
                    }
                    break;
                default:
                    $length = $this->eat8();
                    break;
            }

            if (0 == $length) {
                return 0;
            }
            if ($length < 0) {
                return -1;
            }

            $this->dataStr = substr($this->dataStr, $length);
        }
    }

    //----------------------------------------------------------------------
    public function toUpper()
    {
        $stringLen = strlen($this->dataStr);
        $p = 0;

        while ($p < $stringLen) {
            $mode = self::identifyMode(substr($this->dataStr, $p), $this->modeHint);
            if (Config::QR_MODE_KANJI == $mode) {
                $p += 2;
            } else {
                if (ord($this->dataStr[$p]) >= ord('a') && ord($this->dataStr[$p]) <= ord('z')) {
                    $this->dataStr[$p] = chr(ord($this->dataStr[$p]) - 32);
                }
                ++$p;
            }
        }

        return $this->dataStr;
    }

    public static function splitStringToInput($string, Input $input, $modeHint, $casesensitive = true)
    {
        if (is_null($string) || '\0' == $string || '' == $string) {
            throw new RuntimeException('empty string!!!');
        }

        $split = new Split($string, $input, $modeHint);

        if (!$casesensitive) {
            $split->toUpper();
        }

        return $split->splitString();
    }

    public static function splitStringToQRinput($string, Input $input, $hint, bool $caseSensitive)
    {
        if (is_null($string) || $string == '\0' || $string == '') {
            throw new RuntimeException('empty string!!!');
        }

        $split = new Split($string, $input, $hint);

        if (!$caseSensitive) {
            $split->toUpper();
        }

        return $split->splitString();
    }
}
