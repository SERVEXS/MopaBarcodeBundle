<?php

/**
 * Twig extension for barcodes
 *
 * @author Philipp A. Mohrenweiser<phiamo@googlemail.com>
 * @copyright 2011 Philipp Mohrenweiser
 * @license http://www.apache.org/licenses/LICENSE-2.0.html
 */

namespace Mopa\Bundle\BarcodeBundle\Twig\Extension;

use Mopa\Bundle\BarcodeBundle\Model\BarcodeService;
use Twig\TwigFunction;

class BarcodeRenderExtension extends \Twig_Extension
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'dimass_barcode_render';
    }

    /**
     * @inheritDoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('mopa_barcode_url', [$this, 'url']),
        ];
    }

    /**
     * @param $type
     * @param $text
     * @param array $options
     * @return mixed|string
     */
    public function url($type, $text, $options = [])
    {
        return $this->get($type, $text, false, $options);
    }

    /**
     * @param $type
     * @param $text
     * @param array $options
     * @return mixed|string
     */
    public function path($type, $text, $options = [])
    {
        return $this->get($type, $text, true, $options);
    }

    /**
     * @param $type
     * @param $text
     * @param $absolute
     * @param array $options
     * @return mixed|string
     */
    protected function get($type, $text, $absolute, $options = [])
    {
        return $this->barcodeService->get($type, urlencode($text), $absolute, $options);
    }
}
