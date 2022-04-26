<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

use function count;
use function header;
use function ImageColorAllocate;
use function ImageCopyResized;
use function ImageCreate;
use function ImageDestroy;
use function ImageJpeg;
use function ImagePng;
use function ImageSetPixel;
use function strlen;

final class Image
{
//----------------------------------------------------------------------
    public static function png($frame, $filename = false, $pixelPerPoint = 4, $outerFrame = 4, $saveandprint = false)
    {
        $image = self::image($frame, $pixelPerPoint, $outerFrame);

        if (false === $filename) {
            header('Content-type: image/png');
            ImagePng($image);
        } else {
            if (true === $saveandprint) {
                ImagePng($image, $filename);
                header('Content-type: image/png');
                ImagePng($image);
            } else {
                ImagePng($image, $filename);
            }
        }

        ImageDestroy($image);
    }

    //----------------------------------------------------------------------
    public static function jpg($frame, $filename = false, $pixelPerPoint = 8, $outerFrame = 4, $q = 85)
    {
        $image = self::image($frame, $pixelPerPoint, $outerFrame);

        if (false === $filename) {
            header('Content-type: image/jpeg');
            ImageJpeg($image, null, $q);
        } else {
            ImageJpeg($image, $filename, $q);
        }

        ImageDestroy($image);
    }

    //----------------------------------------------------------------------
    private static function image($frame, int $pixelPerPoint = 4, int $outerFrame = 4)
    {
        $h = count($frame);
        $w = strlen($frame[0]);

        $imgW = $w + 2 * $outerFrame;
        $imgH = $h + 2 * $outerFrame;

        $base_image = ImageCreate($imgW, $imgH);

        $col[0] = ImageColorAllocate($base_image, 255, 255, 255);
        $col[1] = ImageColorAllocate($base_image, 0, 0, 0);

        imagefill($base_image, 0, 0, $col[0]);

        for ($y = 0; $y < $h; ++$y) {
            for ($x = 0; $x < $w; ++$x) {
                if ('1' == $frame[$y][$x]) {
                    ImageSetPixel($base_image, $x + $outerFrame, $y + $outerFrame, $col[1]);
                }
            }
        }

        $target_image = ImageCreate($imgW * $pixelPerPoint, $imgH * $pixelPerPoint);
        ImageCopyResized($target_image, $base_image, 0, 0, 0, 0, $imgW * $pixelPerPoint, $imgH * $pixelPerPoint, $imgW, $imgH);
        ImageDestroy($base_image);

        return $target_image;
    }
}
