<?php

namespace Zaxbux\GmailMailerDriver\Classes;

use ApplicationException;
use Google_Http_MediaFileUpload;
use Google_Service_Gmail_Message;
use Log;
use Zaxbux\GmailMailerDriver\Classes\GoogleAPI;

class GmailMail
{

    /**
     * Google API client
     * @var GoogleAPI
     */
    private $googleAPI;

    public function __construct()
    {
        $this->googleAPI = new GoogleAPI();

        if (!$this->googleAPI->isAuthorized()) {
            throw new \Exception('Cannot send email. Gmail API not authorized.');
        }
    }

    /**
     * Stub since Gmail API is stateless
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * Stub since Gmail API is stateless
     */
    public function start()
    {
        return true;
    }

    /**
     * Stub since Gmail API is stateless
     */
    public function stop()
    {
        return true;
    }

    /**
     * Stub since Gmail API is stateless
     */
    public function ping()
    {
        return true;
    }

    /**
     * Send an email
     */
    public function send($to, $subject, $from, $html)
    {
        // Set client to deferred mode
        $this->googleAPI->client->setDefer(true);
        // Resumable upload
        $usersMessages = $this->googleAPI->getServiceGmail()->users_messages;
        //
        $message = (new \Swift_Message($subject))
            ->setFrom($from)
            ->setTo($to)
            ->setContentType('text/html')
            ->setCharset('utf-8')
            ->setBody($html);

        $msg_base64 = (new \Swift_Mime_ContentEncoder_Base64ContentEncoder())
            ->encodeString($message->toString());

        // Use a resumable upload for large mails
        $gmailMessage = new Google_Service_Gmail_Message();
        $gmailMessage->setRaw($msg_base64);
        $gmailMessage = $mailer->send('me', $message);
    }
    public function Oldsend($message, &$failedRecipients = null)
    {
        try {
            // Use a resumable upload for large mails
            $gmailMessage = new Google_Service_Gmail_Message();

            // Set client to deferred mode
            $this->googleAPI->client->setDefer(true);

            // Resumable upload
            $usersMessages = $this->googleAPI->getServiceGmail()->users_messages;
            trace_log($usersMessages);
            trace_log(get_class($userMessages));
            $gmailMessage = $usersMessages->send('me', $gmailMessage, ['uploadType' => Google_Http_MediaFileUpload::UPLOAD_RESUMABLE_TYPE]);
            trace_log($gmailMessage);
            // Use chunks of 3 MB
            $chunkSizeBytes = 3 * 1024 * 1024;
            $media = new Google_Http_MediaFileUpload(
                $this->googleAPI->client,
                $gmailMessage,
                'message/rfc822',
                $message->toString(),
                true,
                $chunkSizeBytes
            );
            $media->setFileSize(strlen($message->toString()));

            $status = false;
            while (!$status) {
                $status = $media->nextChunk();
            }

            // Reset client to immediately send requests
            $this->googleAPI->client->setDefer(false);
        } catch (\Google_Service_Exception $ex) {
            Log::alert($ex);
            throw new ApplicationException('Failed to send email. Check event log for more info. Message: ' . json_decode($ex->getMessage(), true)['error']['message']);
        }
    }
}
