<?php
namespace TransPerfect\GlobalLink\Magento\Mail\Template;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    public function addAttachment(
        $body,
        $mimeType = \Zend_Mime::TYPE_OCTETSTREAM,
        $disposition = \Zend_Mime::DISPOSITION_ATTACHMENT,
        $encoding = \Zend_Mime::ENCODING_BASE64,
        $filename = null
    ) {
        $attachment = new \Zend\Mime\Part($body);
        $attachment->type = $mimeType;
        $attachment->disposition = $disposition;
        $attachment->encoding = $encoding;
        $attachment->filename = $filename;
        return $attachment;
    }
}
