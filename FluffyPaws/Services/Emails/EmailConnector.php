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
    public function __construct(private Config $config) {}

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
        if ($mailConfig['log_console'] ?? false) {
            print_r([
                "Outgoing Email:",
                $emailTo,
                $subject,
                $body,
                $emailName,
                $altBody,

            ]);
        }
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
            // The domain PHPMailer uses for EHLO and the Message-ID. Left unset it falls back to
            // the machine's hostname — "DESKTOP-G4NJ8FT" on a dev box, the bare host on a server.
            // That is a non-FQDN HELO and a Message-ID whose domain doesn't match the sender, and
            // filters score both. Derive it from the From address so it always matches.
            $atPos = strrpos($from, '@');
            if ($atPos !== false) {
                $mail->Hostname = substr($from, $atPos + 1);
            }
            // Drop the "Using PHPMailer x.y.z" X-Mailer header: it names the library and exact
            // version in every message, and it reads as bulk mail. A single space disables it —
            // an empty string would restore the default.
            $mail->XMailer = ' ';
            // Recipients
            $mail->setFrom($from, $mailConfig['from_name'] ?? 'Notifications');
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
            $time = date('Y-m-d H:i:s', time());
            echo "[Email] $time Email send error." . PHP_EOL;
            echo $mail->ErrorInfo . PHP_EOL;
            echo $t->__toString() . PHP_EOL;
            return ['success' => false, 'message' => $mail->ErrorInfo];
        }
    }
}
