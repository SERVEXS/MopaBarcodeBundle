<?php

namespace Mopa\Bundle\BarcodeBundle\Controller;

use Exception;
use Mopa\Bundle\BarcodeBundle\Model\BarcodeService;
use Mopa\Bundle\BarcodeBundle\Model\BarcodeTypes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BarcodeController extends AbstractController
{
    private FormFactoryInterface $formFactory;

    private BarcodeService $barcodeService;

    public function __construct(FormFactoryInterface $formFactory, BarcodeService $barcodeService)
    {
        $this->formFactory = $formFactory;
        $this->barcodeService = $barcodeService;
    }

    /**
     * This is just an example howto use barcodes and to display them.
     */
    public function playground(Request $request): Response
    {
        $types = BarcodeTypes::getTypes();
        $errors = [];
        $form = $this->formFactory
            ->createBuilder('form')
            ->add('text')
            ->add('type', 'choice', [
                'empty_value' => 'Choose an option',
                'choices' => $types,
            ])
            ->getForm();

        $webfile = false;
        if ($request->isMethod(Request::METHOD_POST)) {
            $form->submit($request);
            $data = $form->getData();
            $text = $data['text'];
            $type = $data['type'];
            if ($type) {
                try {
                    $webfile = $this->barcodeService->get($type, $text);
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                }
            } else {
                $errors[] = 'Please select a option';
            }
            if (count($errors)) {
                $webfile = false;
            }
        }

        return $this->render(
            'MopaBarcodeBundle:Barcode:playground.html.twig',
            [
                'form' => $form->createView(),
                'barcode_url' => $webfile,
                'errors' => $errors,
            ]
        );
    }

    /**
     * This might be used to render barcodes dynamically
     * Careful to expose this on the web, maybe others could use your site just to generate and display barcodes.
     *
     * @param $type
     * @param $enctext
     */
    public function displayBarcode($type, $enctext): Response
    {
        return new Response(
            file_get_contents($file = $this->barcodeService->get($type, $enctext, true)),
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => 'filename="'.$file.'"',
            ]
        );
    }

    /**
     * @param bool $useOverlay deprecated
     */
    public function downloadBarcode(
        string $type,
        int $level = 0,
        int $size = 3,
        int $margin = 4,
        bool $useOverlay = false,
        string $enctext
    ): Response {

        $options = [
            'level' => $level,
            'size' => $size,
            'margin' => $margin,
            'useOverlay' => $useOverlay, //deprecated
        ];

        return new Response(
            file_get_contents($file = $this->barcodeService->get($type, $enctext, true, $options)),
            200,
            [
                'Content-Type' => 'image/png',
                'Content-Disposition' => sprintf('attachment; filename="qr_%s"', date('Y_m_d_H_i_s')),
            ]
        );
    }
}
