<?php

namespace Dimass\SupportPlazaBundle\Twig;

use Mopa\Bundle\BarcodeBundle\Model\BarcodeService;
use Twig\TwigFunction;

class BarcodeRenderExtension extends \Twig_Extension
{
    /**
     * @var BarcodeService
     */
    protected $bs;

    /**
     * @param BarcodeService $bs
     */
    public function __construct(BarcodeService $bs)
    {
        $this->bs = $bs;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'dimass_barcode_render';
    }

    /**
     * @return array
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
        return $this->bs->get($type, urlencode($text), $absolute, $options);
    }

}
