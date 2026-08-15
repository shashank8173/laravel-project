<?php

namespace App\Services;

use App\Models\GreetingImage;
use App\Models\GreetingSetting;

class GreetingMailBuilder
{
    public function __construct(private GreetingSetting $settings) {}

    public static function make(?GreetingSetting $settings = null): self
    {
        return new self($settings ?? GreetingSetting::current());
    }

    /**
     * @return array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}
     */
    public function birthdayCard(string $name, string $email = ''): array
    {
        return $this->buildCard(
            heading: $this->ensureNameInText(
                $this->settings->fillPlaceholders((string) $this->settings->birthday_heading, $name, $email),
                $name,
                'Happy Birthday'
            ),
            employeeName: $name,
            message: $this->settings->fillPlaceholders((string) $this->settings->birthday_message, $name, $email),
            footer: $this->settings->fillPlaceholders((string) $this->settings->birthday_footer, $name, $email),
            accent: '#ff6f61',
            type: 'birthday',
            imageAlt: 'Birthday Celebration'
        );
    }

    /**
     * @return array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}
     */
    public function birthdayAlert(string $name, string $email): array
    {
        $message = $this->settings->fillPlaceholders((string) $this->settings->birthday_alert_message, $name, $email);
        if (! $this->textMentionsName($message, $name)) {
            $message = "Dear Team,\n\nToday is {$name}'s birthday!".($email !== '' ? " Wish them at {$email}." : '')."\n\n".$message;
        }

        return $this->buildCard(
            heading: $this->ensureNameInText(
                $this->settings->fillPlaceholders((string) $this->settings->birthday_alert_heading, $name, $email),
                $name,
                'Birthday Alert'
            ),
            employeeName: $name,
            message: $message,
            footer: $this->settings->fillPlaceholders((string) $this->settings->birthday_footer, $name, $email),
            accent: '#ff6f61',
            type: 'birthday',
            imageAlt: 'Birthday Alert'
        );
    }

    /**
     * @return array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}
     */
    public function anniversaryCard(string $name, string $email = ''): array
    {
        return $this->buildCard(
            heading: $this->ensureNameInText(
                $this->settings->fillPlaceholders((string) $this->settings->anniversary_heading, $name, $email),
                $name,
                'Congratulations'
            ),
            employeeName: $name,
            message: $this->settings->fillPlaceholders((string) $this->settings->anniversary_message, $name, $email),
            footer: $this->settings->fillPlaceholders((string) $this->settings->anniversary_footer, $name, $email),
            accent: '#4caf50',
            type: 'anniversary',
            imageAlt: 'Work Anniversary'
        );
    }

    /**
     * @return array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}
     */
    public function anniversaryAlert(string $name, string $email): array
    {
        $message = $this->settings->fillPlaceholders((string) $this->settings->anniversary_alert_message, $name, $email);
        if (! $this->textMentionsName($message, $name)) {
            $message = "Dear Team,\n\nToday is {$name}'s work anniversary!".($email !== '' ? " Congratulate them at {$email}." : '')."\n\n".$message;
        }

        return $this->buildCard(
            heading: $this->ensureNameInText(
                $this->settings->fillPlaceholders((string) $this->settings->anniversary_alert_heading, $name, $email),
                $name,
                'Work Anniversary Alert'
            ),
            employeeName: $name,
            message: $message,
            footer: $this->settings->fillPlaceholders((string) $this->settings->anniversary_footer, $name, $email),
            accent: '#4caf50',
            type: 'anniversary',
            imageAlt: 'Work Anniversary Alert'
        );
    }

    /**
     * @return array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}
     */
    public function holidayCard(string $holidayName): array
    {
        $heading = $this->settings->fillPlaceholders(
            (string) $this->settings->holiday_heading,
            '',
            '',
            $holidayName
        );
        if (! $this->textMentionsName($heading, $holidayName)) {
            $heading = trim($heading) === '' ? "Happy {$holidayName}!" : rtrim($heading, " \t\n\r\0\x0B!.").' — '.$holidayName;
        }

        return $this->buildCard(
            heading: $heading,
            employeeName: $holidayName,
            message: $this->settings->fillPlaceholders((string) $this->settings->holiday_message, '', '', $holidayName),
            footer: $this->settings->fillPlaceholders((string) $this->settings->holiday_footer, '', '', $holidayName),
            accent: '#ff9800',
            type: 'holiday',
            imageAlt: 'Holiday Celebration'
        );
    }

    /**
     * @return array{html:string,inline:array<int, array{path:string,cid:string,name:string}>}
     */
    private function buildCard(
        string $heading,
        string $employeeName,
        string $message,
        string $footer,
        string $accent,
        string $type,
        string $imageAlt
    ): array {
        $inline = [];
        $imageSrc = null;
        $image = GreetingImage::randomActive($type);
        $path = $image?->absolutePath();

        if ($path && is_file($path)) {
            $cid = 'greeting_'.$type.'_'.substr(md5($path), 0, 8);
            $imageSrc = 'cid:'.$cid;
            $inline[] = [
                'path' => $path,
                'cid' => $cid,
                'name' => basename($path),
            ];
        }

        return [
            'html' => $this->cardHtml($heading, $employeeName, $message, $footer, $accent, $imageSrc, $imageAlt),
            'inline' => $inline,
        ];
    }

    private function ensureNameInText(string $text, string $name, string $fallbackPrefix): string
    {
        $text = trim($text);
        if ($text === '') {
            return "{$fallbackPrefix}, {$name}!";
        }

        if ($this->textMentionsName($text, $name)) {
            return $text;
        }

        return rtrim($text, " \t\n\r\0\x0B!.").', '.$name.'!';
    }

    private function textMentionsName(string $text, string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return true;
        }

        return stripos($text, $name) !== false;
    }

    private function cardHtml(
        string $heading,
        string $employeeName,
        string $message,
        string $footer,
        string $accent,
        ?string $imageSrc,
        string $imageAlt
    ): string {
        $heading = e($heading);
        $employeeNameSafe = e($employeeName);
        $messageHtml = nl2br(e($message));
        $footer = e($footer);
        $imageBlock = '';

        if ($imageSrc) {
            $safeSrc = e($imageSrc);
            $safeAlt = e($imageAlt);
            $imageBlock = "<img src=\"{$safeSrc}\" alt=\"{$safeAlt}\" style=\"width:100%;max-width:560px;border-radius:10px;margin:16px 0;display:block;\">";
        }

        return <<<HTML
<div style="font-family:Arial,sans-serif;text-align:center;padding:20px;border:1px solid #ddd;border-radius:10px;max-width:600px;margin:auto;background-color:#f9f9f9;">
    <h1 style="color:{$accent};margin:0 0 8px;">{$heading}</h1>
    <p style="font-size:22px;font-weight:700;color:#0f2744;margin:0 0 14px;">{$employeeNameSafe}</p>
    <p style="font-size:18px;color:#333;line-height:1.5;margin:0 0 8px;">{$messageHtml}</p>
    {$imageBlock}
    <p style="font-size:16px;color:#555;margin:12px 0 0;">{$footer}</p>
</div>
HTML;
    }
}
