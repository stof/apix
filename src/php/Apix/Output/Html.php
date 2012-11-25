<?php
namespace Apix\Output;

use Apix\View\Template,
    Apix\View\View,
    Apix\View\ViewModel,
    Apix\Model;

class Html extends AbstractOutput
{

    /**
     * {@inheritdoc}
     * @see http://www.ietf.org/rfc/rfc2854.txt
     */
    protected $content_type = 'text/html';

    /**
     * {@inheritdoc}
     */
    public function encode(array $data, $rootNode=null)
    {
        $view = new View($data);

        #return $view; // _toString doesn't handle exception too well!!!

        return $view->render();
    }

}