<?php
namespace TransPerfect\GlobalLink\Magento\Mail\Template;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    protected $message;

    public function addAttachment($body, $filename = null)
    {
        $mimeType = \Zend_Mime::TYPE_OCTETSTREAM;
        $this->message->setBodyAttachment($body, $filename, $mimeType);
        return $this;
    }

    public function prepareMessage()
    {
        parent::prepareMessage();
        //$this->message->setPartsToBody();
        return $this;
    }
}
