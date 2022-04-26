<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use Exception;
use RuntimeException;

final class Encode
{
    /**
     * @var string|int
     */
    private $level = Config::EC_LEVEL_L;

    private int $hint = Config::QR_MODE_8;

    private int $size = 3;

    private int $margin = 4;

    private int $version = 0;

    private bool $caseSensitive = false;

    private bool $eightBit = false;

    /**
     * @param string|int $level
     */
    public static function factory($level = Config::EC_LEVEL_L, int $size = 3, int $margin = 4): self
    {
        $encoder = new self();
        $encoder->setSize($size);
        $encoder->setMargin($margin);
        $encoder->setLevel(
            self::normalizeLevel($level)
        );

        return $encoder;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * @param string|int $level
     */
    protected static function normalizeLevel($level): int
    {
        switch ($level) {
            case (string)Config::EC_LEVEL_L;
            case (string)Config::EC_LEVEL_M;
            case (string)Config::EC_LEVEL_Q;
            case (string)Config::EC_LEVEL_H;
                return (int)$level;
            case 'l':
            case 'L':
                return Config::EC_LEVEL_L;
            case 'm':
            case 'M':
                return Config::EC_LEVEL_M;
            case 'q':
            case 'Q':
                return Config::EC_LEVEL_Q;
            case 'h':
            case 'H':
                return Config::EC_LEVEL_H;
        }

        throw new RuntimeException(sprintf('Unsupported level "%s" value', $level));
    }

    public static function png(?string $text, $outfile = false, int $level = Config::EC_LEVEL_L, int $size = 3, int $margin = 4, bool $saveAndPrint = false)
    {
        return self::factory($level, $size, $margin)->encodePNG($text, $outfile, $saveAndPrint);
    }

    public function setHint(int $hint): void
    {
        $this->hint = $hint;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function setMargin(int $margin): void
    {
        $this->margin = $margin;
    }

    public function encodePNG($intext, $outFile = false, bool $saveAndPrint = false): void
    {
        try {
            ob_start();
            $tab = $this->encode($intext);
            $error = ob_get_contents();
            ob_get_clean();

            if ('' != $error) {
                Tools::log($outFile, $error);
            }

            $maxSize = (int)(Config::PNG_MAX_SIZE / (count($tab) * 2 + $this->margin));

            Image::png($tab, $outFile, min(max(1, $this->size), $maxSize), $this->margin, $saveAndPrint);
        }catch (Exception $e) {
            Tools::log($outFile, $e->getMessage());
        }
    }

    private function encode($intext, $outFile = false)
    {
        $code = new Code();

        if ($this->eightBit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->caseSensitive);
        }

        Tools::markTime('after_encode');

        $data = Tools::binarize($code->data);
        if ($outFile) {
            file_put_contents($outFile, implode(PHP_EOL, $data));
        }

        return $data;
    }
}
