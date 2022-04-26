<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use Exception;
use RuntimeException;

final class Code
{
    public $version;
    public $width;
    public $data;

    public function encodeString8bit($value, $version, $level)
    {
        if ($value == null) {
            throw new RuntimeException('empty string!');
        }

        $input = new Input($version, $level);
        if (null == $input) {
            return null;
        }

        $ret = $input->append($input, Config::QR_MODE_8, strlen($value), str_split($value));
        if ($ret < 0) {
            unset($input);

            return null;
        }

        return $this->encodeInput($input);
    }

    public function encodeInput(Input $input)
    {
        return $this->encodeMask($input, -1);
    }

    public function encodeString($string, $version, $level, $hint, $caseSensitive)
    {
        if (Config::QR_MODE_8 != $hint && Config::QR_MODE_KANJI != $hint) {
            throw new \RuntimeException('bad hint');
        }

        $input = new Input($version, $level);
        if (null == $input) {
            return null;
        }

        $ret = Split::splitStringToQRinput($string, $input, $hint, $caseSensitive);
        if ($ret < 0) {
            return null;
        }

        return $this->encodeInput($input);
    }

    private function encode($string, $version, $level)
    {
        if (!$string) {
            throw new RuntimeException('Empty string!');
        }

        $input = new Input($version, $level);
        if (!$input) {
            return null;
        }
    }

    private function encodeMask(Input $input, $mask)
    {
        if ($input->getVersion() < 0 || $input->getVersion() > Config::QRSPEC_VERSION_MAX) {
            throw new RuntimeException('wrong version');
        }
        if ($input->getErrorCorrectionLevel() > Config::EC_LEVEL_H) {
            throw new RuntimeException('wrong level');
        }

        $raw = new RawEncode($input);

        Tools::markTime('after_raw');

        $version = $raw->version;
        $width = Spec::getWidth($version);
        $frame = Spec::newFrame($version);

        $filler = new FrameFiller($width, $frame);
        if (is_null($filler)) {
            return null;
        }

        // inteleaved data and ecc codes
        for ($i = 0; $i < $raw->dataLength + $raw->eccLength; ++$i) {
            $code = $raw->getCode();
            $bit = 0x80;
            for ($j = 0; $j < 8; ++$j) {
                $addr = $filler->next();
                $filler->setFrameAt($addr, 0x02 | (($bit & $code) != 0));
                $bit = $bit >> 1;
            }
        }

        Tools::markTime('after_filler');

        unset($raw);

        // remainder bits
        $j = Spec::getRemainder($version);
        for ($i = 0; $i < $j; ++$i) {
            $addr = $filler->next();
            $filler->setFrameAt($addr, 0x02);
        }

        $frame = $filler->frame;
        unset($filler);

        // masking
        $maskObj = new Mask();
        if ($mask < 0) {
            if (Config::QR_FIND_BEST_MASK) {
                $masked = $maskObj->mask($width, $frame, $input->getErrorCorrectionLevel());
            } else {
                $masked = $maskObj->makeMask($width, $frame, (int)(Config::QR_DEFAULT_MASK % 8), $input->getErrorCorrectionLevel());
            }
        } else {
            $masked = $maskObj->makeMask($width, $frame, $mask, $input->getErrorCorrectionLevel());
        }

        if (null == $masked) {
            return null;
        }

        Tools::markTime('after_mask');

        $this->version = $version;
        $this->width = $width;
        $this->data = $masked;

        return $this;
    }
}
