<?php

namespace TransPerfect\GlobalLink\Magento\Mail;

use Zend\Mime\Mime;
use Zend\Mime\PartFactory;
use Zend\Mail\MessageFactory as MailFactory;
use Zend\Mime\MessageFactory as MessageFactory;


class Message implements \Magento\Framework\Mail\MailMessageInterface
{
    protected $parts = [];
    protected $zendMessage;
    protected $partFactory;
    protected $messageFactory;

    public function __construct(PartFactory $partFactory, MessageFactory $messageFactory, $charset = 'utf-8')
    {
        $this->partFactory = $partFactory;
        $this->messageFactory = $messageFactory;
        $this->zendMessage = MailFactory::getInstance();
        $this->zendMessage->setEncoding($charset);
    }

    public function setBodyText($content){
        $text = $this->partFactory->create();
        $text->setContent($content);
        $text->setType(Mime::TYPE_TEXT);
        $text->setCharset($this->zendMessage->getEncoding());
        $this->parts[] = $text;
        return $this;
    }

    public function setBodyHtml($content)
    {
        $html = $this->partFactory->create();
        $html->setContent($content);
        $html->setType(Mime::TYPE_HTML);
        $html->setCharset($this->zendMessage->getEncoding());
        $this->parts[] = $html;
        return $this;
    }

    public function setBodyAttachment($content, $fileName, $fileType)
    {
        $attachment = $this->partFactory->create();
        $attachment->setContent($content);
        $attachment->setType($fileType);
        $attachment->setFileName($fileName);
        $attachment->setDisposition(Mime::DISPOSITION_ATTACHMENT);
        $this->parts[] = $attachment;
        return $this;
    }

    public function setPartsToBody()
    {
        $mime = $this->messageFactory->create();
        $mime->setParts($this->parts);
        $this->zendMessage->setBody($mime);
        return $this;
    }

    public function setBody($body)
    {
        return $this;
    }
    public function setSubject($subject)
    {
        $this->zendMessage->setSubject($subject);
        return $this;
    }
    public function getSubject()
    {
        return $this->zendMessage->getSubject();
    }
    public function getBody()
    {
        return $this->zendMessage->getBody();
    }
    public function setFrom($fromAddress)
    {
        $this->zendMessage->setFrom($fromAddress);
        return $this;
    }
    public function addTo($toAddress)
    {
        $this->zendMessage->addTo($toAddress);
        return $this;
    }
    public function addCc($ccAddress)
    {
        $this->zendMessage->addCc($ccAddress);
        return $this;
    }
    public function addBcc($bccAddress)
    {
        $this->zendMessage->addBcc($bccAddress);
        return $this;
    }
    public function setReplyTo($replyToAddress)
    {
        $this->zendMessage->setReplyTo($replyToAddress);
        return $this;
    }
    public function getRawMessage()
    {
        return $this->zendMessage->toString();
    }
    public function setMessageType($type)
    {
        return $this;
    }
}