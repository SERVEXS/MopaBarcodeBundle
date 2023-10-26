<?php

namespace Mopa\Bundle\BarcodeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class MopaBarcodeBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
