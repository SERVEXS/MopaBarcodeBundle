<?php
namespace Mopa\Bundle\BarcodeBundle\Controller;

use Mopa\Bundle\BarcodeBundle\Model\BarcodeService;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Mopa\Bundle\BarcodeBundle\Model\BarcodeTypes;

class BarcodeController extends ContainerAware
{
    /**
     * This is just an example howto use barcodes and to display them
     * @param Request $request
     * @return Response
     */
    public function playgroundAction(Request $request)
    {
        $types = BarcodeTypes::getTypes();
        $errors = array();
        $form = $this->container->get('form.factory')->createBuilder('form')
            ->add('text')
            ->add('type', 'choice', array(
                'empty_value' => 'Choose an option',
                'choices' => $types          
            ))
        ->getForm();
        ;
        $webfile = false;
        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            $data = $form->getData();
            $text = $data['text'];
            $type = $data['type'];
            if($type){
                try{
                    $bmanager = $this->container->get('mopa_barcode.barcode_service');
                    $webfile = $bmanager->get($type, $text);
                }
                catch(\Exception $e){
                    $errors[] = $e->getMessage();
                }
            }else{
                $errors[] = "Please select a option";
            }
            if(count($errors)){
                $webfile = false;
            }
        }
        return $this->container->get('templating')->renderResponse(
            'MopaBarcodeBundle:Barcode:playground.html.twig',
            array(
                'form'=>$form->createView(),
                'barcode_url'=>$webfile, 
                'errors'=>$errors,
            )
        );
    }

    /**
     * This might be used to render barcodes dynamically
     * Careful to expose this on the web, maybe others could use your site just to generate and display barcodes
     * @param $type
     * @param $enctext
     * @return Response
     */
    public function displayBarcodeAction($type, $enctext){
        /** @var BarcodeService $bservice */
        $bservice = $this->container->get('mopa_barcode.barcode_service');
        return new Response(
            file_get_contents($file = $bservice->get($type, $enctext, true)),
            200,
            array(
                'Content-Type'          => 'image/png',
                'Content-Disposition'   => 'filename="'.$file.'"'
            )
        );
    }

    /**
     * @param $type
     * @param int $level
     * @param int $size
     * @param int $margin
     * @param bool $useOverlay
     * @param $enctext
     * @return Response
     */
    public function downloadBarcodeAction($type, $level = 0, $size = 3, $margin = 4, $useOverlay = false, $enctext)
    {
        /** @var BarcodeService $bservice */
        $bservice = $this->container->get('mopa_barcode.barcode_service');

        $options = array(
            'level'         => $level,
            'size'          => $size,
            'margin'        => $margin,
            'useOverlay'    => $useOverlay,
        );

        return new Response(
            file_get_contents($file = $bservice->get($type, $enctext, true, $options)),
            200,
            array(
                'Content-Type'          => 'image/png',
                'Content-Disposition'   => sprintf('attachment; filename="qr_%s"', date("Y_m_d_H_i_s")),
            )
        );
    }
}
