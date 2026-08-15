<?php

namespace App\Services;

use App\Models\EmailConfiguration;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class HrmMailer
{
    /**
     * @param  array<int, string>  $cc
     * @param  array{
     *   from_email?:?string,
     *   from_name?:?string,
     *   for_documents?:bool,
     *   inline_images?:array<int, array{path:string,cid:string,name?:string}>,
     *   bcc?:array<int, string>
     * }  $options
     */
    public function send(
        string $to,
        string $subject,
        string $html,
        array $cc = [],
        ?string $attachmentPath = null,
        ?string $attachmentName = null,
        array $options = []
    ): bool {
        $host = EmailConfiguration::getValue('SMTP_HOST');
        $username = EmailConfiguration::getValue('SMTP_USERNAME');
        $password = EmailConfiguration::getValue('SMTP_PASSWORD');
        $port = (int) EmailConfiguration::getValue('SMTP_PORT', '587');
        $secure = strtolower((string) EmailConfiguration::getValue('SMTP_SECURE', 'tls'));

        [$fromEmail, $fromName] = $this->resolveFrom($options);

        if (! $host || ! $username || ! $password) {
            Log::warning('HrmMailer: SMTP not configured');

            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->Port = $port;
            $mail->Timeout = 20;
            $mail->SMTPKeepAlive = false;

            if (in_array($secure, ['ssl', 'tls', 'starttls'], true)) {
                $mail->SMTPSecure = $secure === 'starttls' ? PHPMailer::ENCRYPTION_STARTTLS : $secure;
            }

            $mail->setFrom($fromEmail ?: $username, $fromName ?: 'Leadforgrow HRM');
            $mail->addAddress($to);

            foreach ($cc as $ccEmail) {
                if (filter_var($ccEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->addCC($ccEmail);
                }
            }

            foreach ($options['bcc'] ?? [] as $bccEmail) {
                $bccEmail = trim((string) $bccEmail);
                if ($bccEmail !== '' && strcasecmp($bccEmail, $to) !== 0 && filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
                    $mail->addBCC($bccEmail);
                }
            }

            if ($attachmentPath && is_file($attachmentPath)) {
                $mail->addAttachment($attachmentPath, $attachmentName ?: basename($attachmentPath));
            }

            foreach ($options['inline_images'] ?? [] as $img) {
                $path = (string) ($img['path'] ?? '');
                $cid = (string) ($img['cid'] ?? '');
                if ($path !== '' && $cid !== '' && is_file($path)) {
                    $mail->addEmbeddedImage($path, $cid, $img['name'] ?? basename($path));
                }
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = strip_tags($html);
            $mail->send();

            return true;
        } catch (MailException|\Throwable $e) {
            Log::error('HrmMailer failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send with official documents From address (salary slips, PDFs, etc.).
     *
     * @param  array<int, string>  $cc
     */
    public function sendDocument(
        string $to,
        string $subject,
        string $html,
        array $cc = [],
        ?string $attachmentPath = null,
        ?string $attachmentName = null,
        ?string $fromEmail = null
    ): bool {
        return $this->send($to, $subject, $html, $cc, $attachmentPath, $attachmentName, [
            'for_documents' => true,
            'from_email' => $fromEmail,
        ]);
    }

    /**
     * @param  array{from_email?:?string,from_name?:?string,for_documents?:bool}  $options
     * @return array{0:?string,1:?string}
     */
    private function resolveFrom(array $options): array
    {
        $overrideEmail = trim((string) ($options['from_email'] ?? ''));
        $overrideName = trim((string) ($options['from_name'] ?? ''));
        $forDocuments = (bool) ($options['for_documents'] ?? false);

        $defaultEmail = EmailConfiguration::getValue('FROM_EMAIL', EmailConfiguration::getValue('SMTP_USERNAME'));
        $defaultName = EmailConfiguration::getValue('FROM_NAME', 'Leadforgrow HRM');

        if ($forDocuments) {
            $docsEmail = trim((string) EmailConfiguration::getValue('DOCS_FROM_EMAIL', ''));
            $docsName = trim((string) EmailConfiguration::getValue('DOCS_FROM_NAME', ''));

            $email = $overrideEmail !== '' ? $overrideEmail : ($docsEmail !== '' ? $docsEmail : $defaultEmail);
            $name = $overrideName !== '' ? $overrideName : ($docsName !== '' ? $docsName : $defaultName);

            // Allow only configured official emails when an override is provided.
            if ($overrideEmail !== '' && ! EmailConfiguration::isOfficialFromEmail($overrideEmail)) {
                $email = $docsEmail !== '' ? $docsEmail : $defaultEmail;
            }

            return [$email, $name];
        }

        return [
            $overrideEmail !== '' ? $overrideEmail : $defaultEmail,
            $overrideName !== '' ? $overrideName : $defaultName,
        ];
    }
}
