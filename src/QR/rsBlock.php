<?php

declare(strict_types=1);

namespace Mopa\Bundle\BarcodeBundle\QR;

final class rsBlock
{
    public $dataLength;
    public $data = [];
    public $eccLength;
    public $ecc = [];

    public function __construct($dl, $data, $el, &$ecc, rsItem $rs)
    {
        $rs->encode_rs_char($data, $ecc);

        $this->dataLength = $dl;
        $this->data = $data;
        $this->eccLength = $el;
        $this->ecc = $ecc;
    }
}
