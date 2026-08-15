<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\EmailConfiguration;
use App\Services\AttendanceGeofence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OptionalSetupController extends Controller
{
    public function index(AttendanceGeofence $geofence): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $items = $this->checklist($geofence);
        $done = collect($items)->where('ready', true)->count();
        $total = count($items);
        $percent = $total > 0 ? (int) round(($done / $total) * 100) : 0;

        $company = Company::query()->orderBy('id')->first();
        $coords = $geofence->officeCoordinates();

        $form = [
            'latitude' => $coords
                ? number_format($coords['lat'], 7, '.', '').','.number_format($coords['lng'], 7, '.', '')
                : trim((string) ($company?->latitude ?? '')),
            'geofence_enabled' => $this->geofenceEnabled() ? '1' : '0',
            'geofence_radius' => (string) $this->geofenceRadius(),
            'SUPPORT_TO' => (string) EmailConfiguration::getValue('SUPPORT_TO', ''),
            'SUPPORT_CC' => (string) EmailConfiguration::getValue('SUPPORT_CC', ''),
            'HARASSMENT_RECIPIENTS' => (string) EmailConfiguration::getValue('HARASSMENT_RECIPIENTS', ''),
            'ONBOARDING_RECIPIENTS' => (string) EmailConfiguration::getValue('ONBOARDING_RECIPIENTS', ''),
            'ONBOARDING_CC' => (string) EmailConfiguration::getValue('ONBOARDING_CC', ''),
        ];

        return view('settings.optional-setup', compact('items', 'done', 'total', 'percent', 'form', 'company'));
    }

    public function update(Request $request, AttendanceGeofence $geofence): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $section = $request->validate([
            'section' => ['required', 'in:geofence,support,harassment,onboarding'],
        ])['section'];

        match ($section) {
            'geofence' => $this->saveGeofence($request),
            'support' => $this->saveEmails($request, ['SUPPORT_TO', 'SUPPORT_CC']),
            'harassment' => $this->saveEmails($request, ['HARASSMENT_RECIPIENTS']),
            'onboarding' => $this->saveEmails($request, ['ONBOARDING_RECIPIENTS', 'ONBOARDING_CC']),
        };

        return redirect()
            ->to(route('developer.optional-setup').'#'.$section)
            ->with('success', 'Optional setup saved.');
    }

    /**
     * @return list<array{key:string,title:string,ready:bool,detail:string,action:string}>
     */
    public static function checklistStatus(?AttendanceGeofence $geofence = null): array
    {
        $geofence ??= app(AttendanceGeofence::class);

        return (new self)->checklist($geofence);
    }

    /**
     * @return list<array{key:string,title:string,ready:bool,detail:string,action:string}>
     */
    private function checklist(AttendanceGeofence $geofence): array
    {
        $coords = $geofence->officeCoordinates();
        $enabled = $this->geofenceEnabled();
        $radius = $this->geofenceRadius();

        $supportTo = trim((string) EmailConfiguration::getValue('SUPPORT_TO', ''));
        if ($supportTo === '') {
            $supportTo = trim((string) (EmailConfiguration::docsFrom()['email'] ?? EmailConfiguration::getValue('FROM_EMAIL') ?? ''));
        }
        $supportCc = EmailConfiguration::recipients('SUPPORT_CC');
        $harassment = EmailConfiguration::recipients('HARASSMENT_RECIPIENTS');
        $onboarding = EmailConfiguration::recipients('ONBOARDING_RECIPIENTS');

        return [
            [
                'key' => 'geofence',
                'title' => 'Punch geofence (office location)',
                'ready' => $coords !== null,
                'detail' => $coords
                    ? sprintf(
                        'Office set at %.5f, %.5f · %s · radius %d m',
                        $coords['lat'],
                        $coords['lng'],
                        $enabled ? 'enabled' : 'disabled',
                        $radius
                    )
                    : 'Set office lat,lng so punch-in works only near office. Leave empty to allow punch anywhere.',
                'action' => 'Configure location',
            ],
            [
                'key' => 'support',
                'title' => 'Support ticket emails',
                'ready' => $supportTo !== '' && filter_var($supportTo, FILTER_VALIDATE_EMAIL),
                'detail' => $supportTo !== '' && filter_var($supportTo, FILTER_VALIDATE_EMAIL)
                    ? 'To: '.$supportTo.($supportCc !== [] ? ' · CC: '.implode(', ', $supportCc) : '')
                    : 'Set SUPPORT_TO so new tickets and updates reach your support mailbox.',
                'action' => 'Configure support',
            ],
            [
                'key' => 'harassment',
                'title' => 'Harassment / POSH recipients',
                'ready' => $harassment !== [],
                'detail' => $harassment !== []
                    ? 'Alerts go to: '.implode(', ', $harassment)
                    : 'Complaints still save; add ICC/HR emails to get notified.',
                'action' => 'Configure recipients',
            ],
            [
                'key' => 'onboarding',
                'title' => 'Onboarding recipients',
                'ready' => $onboarding !== [],
                'detail' => $onboarding !== []
                    ? 'Alerts go to: '.implode(', ', $onboarding)
                    : 'Steps still update; add HR emails to get notified when a step is completed.',
                'action' => 'Configure recipients',
            ],
        ];
    }

    private function saveGeofence(Request $request): void
    {
        $data = $request->validate([
            'latitude' => ['nullable', 'string', 'max:100'],
            'geofence_enabled' => ['nullable', 'in:0,1'],
            'geofence_radius' => ['required', 'integer', 'min:50', 'max:50000'],
        ]);

        $latRaw = trim((string) ($data['latitude'] ?? ''));
        $company = Company::query()->orderBy('id')->first();

        if ($company) {
            $company->update([
                'latitude' => $latRaw !== '' ? $latRaw : null,
            ]);
        } elseif ($latRaw !== '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'latitude' => 'Add company details first on the Companies page, then set geofence coordinates.',
            ]);
        }

        EmailConfiguration::setValue(
            'GEOFENCE_ENABLED',
            ($data['geofence_enabled'] ?? '1') === '1' ? '1' : '0',
            'Attendance punch geofence on/off'
        );
        EmailConfiguration::setValue(
            'GEOFENCE_RADIUS_METERS',
            (string) $data['geofence_radius'],
            'Punch allowed radius in meters'
        );
    }

    /**
     * @param  list<string>  $keys
     */
    private function saveEmails(Request $request, array $keys): void
    {
        $rules = [];
        foreach ($keys as $key) {
            $rules['configs.'.$key] = ['nullable', 'string', 'max:2000'];
        }
        $data = $request->validate($rules);
        $configs = $data['configs'] ?? [];

        foreach ($keys as $key) {
            $value = trim((string) ($configs[$key] ?? ''));
            if ($key === 'SUPPORT_TO' && $value !== '' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'configs.SUPPORT_TO' => 'Primary support email must be valid.',
                ]);
            }
            EmailConfiguration::setValue($key, $value);
        }
    }

    private function geofenceEnabled(): bool
    {
        $stored = EmailConfiguration::getValue('GEOFENCE_ENABLED');
        if ($stored !== null && $stored !== '') {
            return in_array(strtolower(trim($stored)), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) config('hrm.geofence_enabled', true);
    }

    private function geofenceRadius(): int
    {
        $stored = EmailConfiguration::getValue('GEOFENCE_RADIUS_METERS');
        if ($stored !== null && $stored !== '' && is_numeric($stored)) {
            return max(50, (int) $stored);
        }

        return max(50, (int) config('hrm.geofence_radius_meters', 500));
    }
}
