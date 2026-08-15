<?php

namespace App\Http\Controllers;

use App\Models\EmailConfiguration;
use App\Services\HrmMailer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailSettingsController extends Controller
{
    /** @var array<string, array{label:string,help:?string,type:string}> */
    private array $fields = [
        'SMTP_HOST' => ['label' => 'SMTP Host', 'help' => 'SMTP server address for sending emails', 'type' => 'text'],
        'SMTP_PORT' => ['label' => 'SMTP Port', 'help' => 'Usually 587 (STARTTLS) or 465 (SSL)', 'type' => 'number'],
        'SMTP_USERNAME' => ['label' => 'SMTP Username', 'help' => 'Mailbox used to authenticate', 'type' => 'email'],
        'SMTP_PASSWORD' => ['label' => 'SMTP Password', 'help' => 'Keep this secure', 'type' => 'password'],
        'SMTP_SECURE' => ['label' => 'Encryption', 'help' => null, 'type' => 'select'],
        'FROM_EMAIL' => ['label' => 'Default From Email', 'help' => 'Appears as sender for system notifications', 'type' => 'email'],
        'FROM_NAME' => ['label' => 'Default From Name', 'help' => 'Display name for default sender', 'type' => 'text'],
        'DOCS_FROM_EMAIL' => ['label' => 'Documents From Email', 'help' => 'Official From address for salary slips, expense PDFs and other documents', 'type' => 'email'],
        'DOCS_FROM_NAME' => ['label' => 'Documents From Name', 'help' => 'Display name when sending official documents', 'type' => 'text'],
        'OFFICIAL_FROM_EMAILS' => ['label' => 'Additional Official From Emails', 'help' => 'Comma-separated official emails allowed as From when sending documents', 'type' => 'textarea'],
        'RESIGNATION_RECIPIENTS' => ['label' => 'Recipients', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'RESIGNATION_CC' => ['label' => 'CC', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'HARASSMENT_RECIPIENTS' => ['label' => 'Recipients', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'SUPPORT_TO' => ['label' => 'Primary recipient', 'help' => 'Main support mailbox for new tickets and updates', 'type' => 'email'],
        'SUPPORT_CC' => ['label' => 'CC Emails', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'ONBOARDING_RECIPIENTS' => ['label' => 'Recipients', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'ONBOARDING_CC' => ['label' => 'CC', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'NOTICE_PERIOD_RECIPIENTS' => ['label' => 'Recipients', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'NOTICE_PERIOD_CC' => ['label' => 'CC', 'help' => 'Comma-separated', 'type' => 'textarea'],
        'EVENING_REMINDER_EMAIL' => ['label' => 'Evening Reminder Email', 'help' => 'Primary recipient for evening punch-out reminders', 'type' => 'email'],
    ];

    /** @var array<string, array{title:string,keys:array<int,string>}> */
    private array $sections = [
        'smtp' => [
            'title' => 'SMTP configuration',
            'keys' => ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'SMTP_SECURE'],
        ],
        'from' => [
            'title' => 'Default sender (From)',
            'keys' => ['FROM_EMAIL', 'FROM_NAME'],
        ],
        'docs' => [
            'title' => 'Official From emails — send documents',
            'keys' => ['DOCS_FROM_EMAIL', 'DOCS_FROM_NAME', 'OFFICIAL_FROM_EMAILS'],
        ],
        'resignation' => [
            'title' => 'Resignation notifications',
            'keys' => ['RESIGNATION_RECIPIENTS', 'RESIGNATION_CC'],
        ],
        'harassment' => [
            'title' => 'Harassment / complaints',
            'keys' => ['HARASSMENT_RECIPIENTS'],
        ],
        'support' => [
            'title' => 'Support tickets',
            'keys' => ['SUPPORT_TO', 'SUPPORT_CC'],
        ],
        'onboarding' => [
            'title' => 'Onboarding',
            'keys' => ['ONBOARDING_RECIPIENTS', 'ONBOARDING_CC'],
        ],
        'notice' => [
            'title' => 'Notice period',
            'keys' => ['NOTICE_PERIOD_RECIPIENTS', 'NOTICE_PERIOD_CC'],
        ],
        'reminders' => [
            'title' => 'Reminders',
            'keys' => ['EVENING_REMINDER_EMAIL'],
        ],
    ];

    public function index(): View
    {
        $configs = EmailConfiguration::allCached();
        if (! empty($configs['SMTP_PASSWORD'])) {
            $configs['SMTP_PASSWORD'] = EmailConfiguration::getValue('SMTP_PASSWORD', '');
        }
        $officialFrom = EmailConfiguration::officialFromEmails();
        $docsFrom = EmailConfiguration::docsFrom();

        return view('settings.email', [
            'configs' => $configs,
            'fields' => $this->fields,
            'sections' => $this->sections,
            'officialFrom' => $officialFrom,
            'docsFrom' => $docsFrom,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'section' => ['nullable', 'string'],
            'configs' => ['required', 'array'],
            'configs.*' => ['nullable', 'string'],
        ]);

        $section = $data['section'] ?? null;
        $keys = array_keys($this->fields);

        if ($section && isset($this->sections[$section])) {
            $keys = $this->sections[$section]['keys'];
        }

        foreach ($keys as $key) {
            if (array_key_exists($key, $data['configs'])) {
                EmailConfiguration::setValue($key, trim((string) $data['configs'][$key]));
            }
        }

        $label = ($section && isset($this->sections[$section]))
            ? $this->sections[$section]['title']
            : 'Email configuration';

        return redirect()
            ->to(route('settings.email').($section ? '#section-'.$section : ''))
            ->with('success', $label.' saved.');
    }

    public function test(Request $request, HrmMailer $mailer): RedirectResponse
    {
        $data = $request->validate([
            'recipient' => ['required', 'email'],
            'from_mode' => ['nullable', 'in:default,docs'],
        ]);

        $forDocs = ($data['from_mode'] ?? 'default') === 'docs';
        $from = $forDocs ? EmailConfiguration::docsFrom() : [
            'email' => EmailConfiguration::getValue('FROM_EMAIL'),
            'name' => EmailConfiguration::getValue('FROM_NAME'),
        ];

        $ok = $mailer->send(
            $data['recipient'],
            'Email Configuration Test',
            '<h3>Test Email</h3><p>This is a test email from Leadforgrow HRM.</p>'
            .'<p><strong>From mode:</strong> '.($forDocs ? 'Documents / official' : 'Default notifications').'</p>'
            .'<p>Your SMTP settings are working.</p>',
            [],
            null,
            null,
            [
                'for_documents' => $forDocs,
                'from_email' => $from['email'] ?? null,
                'from_name' => $from['name'] ?? null,
            ]
        );

        return $ok
            ? redirect()->to(route('settings.email').'#section-test')->with('success', 'Test email sent to '.$data['recipient'].' (from '.($from['email'] ?? 'SMTP').')')
            : back()->withErrors(['recipient' => 'Failed to send test email. Check SMTP settings.']);
    }
}
