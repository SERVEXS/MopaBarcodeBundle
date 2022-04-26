<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

final class Tools
{
    private static array $frames = [];
    private static array $qr_time_bench = [];

    public static function binarize($frame)
    {
        $len = count($frame);
        foreach ($frame as &$frameLine) {
            for ($i = 0; $i < $len; ++$i) {
                $frameLine[$i] = (ord($frameLine[$i]) & 1) ? '1' : '0';
            }
        }

        return $frame;
    }

    public static function tcpdfBarcodeArray($code, $mode = 'QR,L', $tcPdfVersion = '4.5.037')
    {
        $barcode_array = [];

        if (!is_array($mode)) {
            $mode = explode(',', $mode);
        }

        $eccLevel = 'L';

        if (count($mode) > 1) {
            $eccLevel = $mode[1];
        }

        $qrTab = Code::text($code, false, $eccLevel);
        $size = count($qrTab);

        $barcode_array['num_rows'] = $size;
        $barcode_array['num_cols'] = $size;
        $barcode_array['bcode'] = [];

        foreach ($qrTab as $line) {
            $arrAdd = [];
            foreach (str_split($line) as $char) {
                $arrAdd[] = ('1' == $char) ? 1 : 0;
            }
            $barcode_array['bcode'][] = $arrAdd;
        }

        return $barcode_array;
    }

    public static function clearCache(): void
    {
        self::$frames = [];
    }

    public static function buildCache(): void
    {
        self::markTime('before_build_cache');

        $mask = new Mask();
        for ($a = 1; $a <= Config::QRSPEC_VERSION_MAX; ++$a) {
            $frame = Spec::newFrame($a);
            if (Config::QR_IMAGE) {
                $fileName = Config::getCacheDir() . 'frame_' . $a . '.png';
                Image::png(self::binarize($frame), $fileName, 1, 0);
            }

            $width = count($frame);
            $bitMask = array_fill(0, $width, array_fill(0, $width, 0));
            for ($maskNo = 0; $maskNo < 8; ++$maskNo) {
                $mask->makeMaskNo($maskNo, $width, $frame, $bitMask, true);
            }
        }

        self::markTime('after_build_cache');
    }

    //----------------------------------------------------------------------
    public static function log($outfile, $err)
    {
        if (Config::getLogDir() !== false) {
            if ('' !== $err) {
                if (false !== $outfile) {
                    file_put_contents(Config::getLogDir() . basename($outfile) . '-errors.txt', date('Y-m-d H:i:s') . ': ' . $err, FILE_APPEND);
                } else {
                    file_put_contents(Config::getLogDir() . 'errors.txt', date('Y-m-d H:i:s') . ': ' . $err, FILE_APPEND);
                }
            }
        }
    }

    public static function dumpMask($frame): void
    {
        $width = count($frame);
        for ($y = 0; $y < $width; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                echo ord($frame[$y][$x]).',';
            }
        }
    }

    public static function markTime($markerId)
    {
        [$usec, $sec] = explode(' ', microtime());
        $time = ((float) $usec + (float) $sec);

        if (!isset(self::$qr_time_bench)) {
            self::$qr_time_bench = [];
        }

        self::$qr_time_bench[$markerId] = $time;
    }

    public static function timeBenchmark(): void
    {
        self::markTime('finish');

        $lastTime = 0;
        $startTime = 0;
        $p = 0;

        echo '<table cellpadding="3" cellspacing="1">
                    <thead><tr style="border-bottom:1px solid silver"><td colspan="2" style="text-align:center">BENCHMARK</td></tr></thead>
                    <tbody>';

        foreach (self::$qr_time_bench as $markerId => $thisTime) {
            if ($p > 0) {
                echo '<tr><th style="text-align:right">till '.$markerId.': </th><td>'.number_format($thisTime - $lastTime, 6).'s</td></tr>';
            } else {
                $startTime = $thisTime;
            }

            ++$p;
            $lastTime = $thisTime;
        }

        echo '</tbody><tfoot>
                <tr style="border-top:2px solid black"><th style="text-align:right">TOTAL: </th><td>'.number_format($lastTime - $startTime, 6).'s</td></tr>
            </tfoot>
            </table>';
    }
}
