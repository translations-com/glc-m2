<?php
namespace TransPerfect\GlobalLink\Logger\BgTask;

class Logger extends \Monolog\Logger
{
    /**
     * Create log message
     *
     * @array $logData
     *
     * @return string
     */
    public function bgLogMessage(array $logData)
    {
        $message = $logData['message'];

        if (!empty($logData['file'])) {
            $message .= ' In '.$logData['file'];
        }
        if (!empty($logData['line'])) {
            $message .= ' - Line '.$logData['line'].'.';
        }
        if (!empty($logData['action'])) {
            $message .= ' Action: '.$logData['action'].'.';
        }
        if (!empty($logData['actor'])) {
            $message .= ' Actor: '.$logData['actor'].'.';
        }

        return $message;
    }
}
