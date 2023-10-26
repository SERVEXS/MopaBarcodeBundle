<?php

namespace Mopa\Bundle\BarcodeBundle\Model;

use Imagine\Gd\Image;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\RGB as RGBColor;
use Laminas\Barcode\Barcode;

class BarcodeService
{
    /**
     * @var string[]
     */
    private array $types;

    private string $webDir;

    private string $webRoot;

    public function __construct(
        string $webDir,
        string $webRoot
    ) {
        $this->types = BarcodeTypes::getTypes();
        $this->webDir = $webDir;
        $this->webRoot = $webRoot;
    }

    public function saveAs($type, $text, $file, array $options = []): bool
    {
        @unlink($file);
        switch ($type) {
            case 'qr':
                throw new \InvalidArgumentException('QR code is not supported anymore!');
            case is_numeric($type):
                $type = $this->types[$type];
            // no break
            default:
                $barcodeOptions = array_merge($options['barcodeOptions'] ?? [], ['text' => $text]);
                $rendererOptions = $options['rendererOptions'] ?? [];
                $rendererOptions['width'] ??= 2233;
                $rendererOptions['height'] ??= 649;
                $palette = new RGBColor();
                $metaData = new MetadataBag();
                $imageResource = Barcode::factory($type, 'image', $barcodeOptions, $rendererOptions)->draw();
                $image = new Image($imageResource, $palette, $metaData);
                $image->save($file);
        }

        return true;
    }

    /**
     * Get a Barcodes Filename
     * Generates it if it's not here.
     *
     * @param string $type BarcodeType
     * @param string $enctext BarcodeText
     * @param bool $absolute get absolute path, default: false
     * @param array $options Options
     *
     * @return mixed|string
     */
    public function get($type, $enctext, bool $absolute = false, array $options = [])
    {
        $text = urldecode($enctext);
        $filename = $this->getAbsoluteBarcodeDir($type) . $this->getBarcodeFilename($text, $options);

        if ((isset($options['noCache']) && $options['noCache']) || !file_exists($filename)) {
            $this->saveAs($type, $text, $filename, $options);
        }

        if (!$absolute) {
            $path = DIRECTORY_SEPARATOR . $this->webDir . $this->getTypeDir($type) . $this->getBarcodeFilename(
                    $text,
                    $options
                );

            return str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }

        return $filename;
    }

    /**
     * @param int|string $type
     */
    protected function getTypeDir($type): string
    {
        if (is_numeric($type)) {
            $type = $this->types[$type];
        }

        return $type . DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    protected function getBarcodeFilename($text, $options)
    {
        return sha1($text . serialize($options)) . '.png';
    }

    /**
     * @return string
     */
    protected function getAbsoluteBarcodeDir($type)
    {
        $path = $this->getAbsolutePath() . $this->getTypeDir($type);
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true) && !is_dir($path)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
            }
        }

        return $path;
    }

    /**
     * @return string
     */
    protected function getAbsolutePath()
    {
        return $this->webRoot . DIRECTORY_SEPARATOR . $this->webDir;
    }
}
