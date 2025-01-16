<?php

namespace FluffyPaws\Services\Emails;

use Exception;
use Fluffy\Domain\Configuration\Config;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use RuntimeException;
use Swoole\ConnectionPool;
use Swoole\Coroutine\Http2\Client;
use Swoole\Http2\Request;
use Throwable;

class EmailConnector // extends ConnectionPool // ?? can it be http connection pool
{
    public function __construct(private Config $config)
    {
    }

    /**
     * 
     * @param string $emailTo 
     * @param string $subject 
     * @param string $body 
     * @param string $emailName 
     * @param string $altBody 
     * @param null|EmailAttachment[] $attachments 
     * @return true[]|(false|string)[]|void 
     */
    public function send(string $emailTo, string $subject, string $body, string $emailName = '', string $altBody = '', ?array $attachments = null)
    {
        $mail = new PHPMailer(true);
        $mailConfig = $this->config->values['email'];
        try {
            // Server settings
            $from = $mailConfig['from'];
            $mail->SMTPDebug = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host       = $mailConfig['host'];
            $mail->SMTPAuth   = $mailConfig['SMTPAuth'];
            $mail->Username   = $mailConfig['username'];
            $mail->Password   = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['SMTPSecure'];
            $mail->Port       = $mailConfig['port'];
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            // Recipients
            $mail->setFrom($from, 'Manager');
            $mail->addAddress($emailTo, $emailName);
            if (isset($mailConfig['copyTo'])) {
                $mail->addBCC($mailConfig['copyTo']);
            }
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody;
            if ($attachments !== null) {
                foreach ($attachments as $attachment) {
                    $mail->addStringAttachment($attachment->Data, $attachment->FileName, PHPMailer::ENCODING_BASE64, $attachment->MimeType);
                }
            }
            // if it's additional domain
            // $mail->SMTPOptions = array(
            //     'ssl' => array(
            //         'verify_peer' => false,
            //         'verify_peer_name' => false,
            //         'allow_self_signed' => true
            //     )
            // );
            $time = date('Y-m-d H:i:s', time());
            echo "[Email] $time Sending email." . PHP_EOL;
            $mail->smtpClose();
            $mail->send();
            echo "[Email] $time Email has been sent." . PHP_EOL;
            return ['success' => true];
        } catch (Throwable $t) {
            echo "[Email] $time Email send error." . PHP_EOL;
            echo $mail->ErrorInfo . PHP_EOL;
            echo $t->__toString() . PHP_EOL;
            return ['success' => false, 'message' => $mail->ErrorInfo];
        }
    }
}
