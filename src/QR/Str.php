<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

final class Str
{
    public static function set(&$srctab, $x, $y, $repl, $replLen = false)
    {
        $srctab[$y] = substr_replace(
            $srctab[$y],
            (false !== $replLen) ? substr($repl, 0, $replLen) : $repl,
            $x,
            (false !== $replLen) ? $replLen : strlen($repl)
        );
    }
}
