<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

final class rs
{
    public static $items = [];

    public static function init_rs($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        foreach (self::$items as $rs) {
            if ($rs->pad != $pad) {
                continue;
            }
            if ($rs->nroots != $nroots) {
                continue;
            }
            if ($rs->mm != $symsize) {
                continue;
            }
            if ($rs->gfpoly != $gfpoly) {
                continue;
            }
            if ($rs->fcr != $fcr) {
                continue;
            }
            if ($rs->prim != $prim) {
                continue;
            }

            return $rs;
        }

        $rs = rsItem::init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad);
        array_unshift(self::$items, $rs);

        return $rs;
    }
}
