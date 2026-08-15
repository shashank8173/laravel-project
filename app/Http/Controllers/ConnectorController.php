<?php

namespace App\Http\Controllers;

use App\Models\Connector;
use App\Services\Connectors\ConnectorManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ConnectorController extends Controller
{
    public function __construct(private ConnectorManager $manager) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $this->manager->ensureDefaults();

        $connectors = Connector::query()->orderBy('name')->get();
        foreach ($connectors as $connector) {
            $connector->ensureSlug();
            $connector->ensureWebhookSecret();
        }

        $defaultMap = $this->manager->driver()->defaultFieldMap();
        $samplePayload = $this->manager->driver()->samplePayload();

        $logs = \App\Models\ConnectorSyncLog::query()
            ->with('connector:id,slug,name')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('developer.connectors', compact('connectors', 'defaultMap', 'samplePayload', 'logs'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $base = Str::slug($data['name']) ?: 'connector';
        $slug = $base;
        $i = 1;
        while (Connector::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        Connector::query()->create([
            'type' => 'generic',
            'slug' => $slug,
            'name' => trim($data['name']),
            'enabled' => false,
            'webhook_secret' => 'whsec_'.Str::random(40),
            'field_map' => $this->manager->driver()->defaultFieldMap(),
        ]);

        return back()->with('success', 'Connector created. Enable it and share the webhook URL with any third-party tool.');
    }

    public function update(Request $request, Connector $connector): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
            'field_map_json' => ['nullable', 'string'],
            'test_payload_json' => ['nullable', 'string'],
            'config_pull_url' => ['nullable', 'string', 'max:500'],
            'config_pull_auth' => ['nullable', 'string', 'max:1000'],
            'rotate_secret' => ['nullable', 'boolean'],
        ]);

        $map = $connector->field_map ?: $this->manager->driver()->defaultFieldMap();
        if (isset($data['field_map_json'])) {
            $decoded = json_decode((string) $data['field_map_json'], true);
            if (! is_array($decoded)) {
                return back()->withErrors(['field_map_json' => 'Field map must be valid JSON object.'])->withInput();
            }

            $allowedTargets = [
                'fname', 'lname', 'office_email', 'email', 'mobile1', 'mobile2',
                'emp_id', 'external_id', 'department', 'designation', 'job_title',
                'doj', 'dob', 'salary', 'role', 'status', 'employee_type', 'work_location',
                'current_address', 'permanent_address', 'password', 'status_label',
            ];

            $invalid = [];
            foreach ($decoded as $source => $target) {
                $target = trim((string) $target);
                if ($target === '' || ! in_array($target, $allowedTargets, true)) {
                    $invalid[] = (string) $source.' → '.$target;
                }
            }

            if ($invalid !== []) {
                return back()->withErrors([
                    'field_map_json' => 'Map source keys to HRM fields (fname, office_email…), not employee values. Invalid: '
                        .implode('; ', array_slice($invalid, 0, 5)),
                ])->withInput();
            }

            $map = $decoded;
        }

        $config = $connector->config_array;
        if (array_key_exists('config_pull_url', $data)) {
            $pullUrl = trim((string) ($data['config_pull_url'] ?? ''));
            if ($pullUrl !== '' && str_contains($pullUrl, '/connectors/') && str_contains($pullUrl, '/webhook')) {
                return back()->withErrors([
                    'config_pull_url' => 'Pull URL cannot be the webhook URL. Leave empty, or use another JSON feed URL.',
                ])->withInput();
            }
            if ($pullUrl === '' || str_contains($pullUrl, 'your-middleware.example.com')) {
                unset($config['pull_url']);
            } else {
                $config['pull_url'] = $pullUrl;
            }
        }
        if (! empty($data['config_pull_auth'])) {
            $config['pull_auth'] = trim((string) $data['config_pull_auth']);
        }
        if (isset($data['test_payload_json']) && trim((string) $data['test_payload_json']) !== '') {
            $testPayload = json_decode((string) $data['test_payload_json'], true);
            if (! is_array($testPayload)) {
                return back()->withErrors(['test_payload_json' => 'Test payload must be valid JSON.'])->withInput();
            }
            $config['test_payload'] = $testPayload;
        }

        $connector->name = trim($data['name']);
        $connector->enabled = $request->boolean('enabled');
        $connector->field_map = $map;
        $connector->type = 'generic';
        $connector->setConfigArray($config);

        if ($request->boolean('rotate_secret')) {
            $connector->webhook_secret = 'whsec_'.Str::random(40);
        }

        $connector->save();

        return back()->with('success', 'Connector saved.');
    }

    public function destroy(Connector $connector): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (Connector::query()->count() <= 1) {
            return back()->withErrors(['connector' => 'Keep at least one connector.']);
        }

        $connector->syncLogs()->delete();
        $connector->delete();

        return back()->with('success', 'Connector deleted.');
    }

    public function pull(Connector $connector): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $connector->enabled) {
            return back()->withErrors(['connector' => 'Enable the connector before pulling.']);
        }

        $pullUrl = trim((string) ($connector->config_array['pull_url'] ?? ''));
        if ($pullUrl === '' || str_contains($pullUrl, '/connectors/')) {
            return back()->withErrors([
                'connector' => 'For local testing use “Run test sync” or Postman webhook. Pull needs a separate JSON URL.',
            ]);
        }

        try {
            $result = $this->manager->pull($connector);
        } catch (\Throwable $e) {
            return back()->withErrors(['connector' => $e->getMessage()]);
        }

        return back()->with(
            'success',
            sprintf(
                'Pull finished (%s): created %d, updated %d, failed %d.',
                $result['log']->status,
                $result['log']->created_count,
                $result['log']->updated_count,
                $result['log']->failed_count
            )
        );
    }

    public function testSample(Request $request, Connector $connector): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        if (! $connector->enabled) {
            $connector->enabled = true;
            $connector->save();
        }
        $connector->ensureWebhookSecret();

        $custom = null;
        if ($request->filled('test_payload_json')) {
            $custom = json_decode((string) $request->input('test_payload_json'), true);
            if (! is_array($custom)) {
                return back()->withErrors(['test_payload_json' => 'Test payload must be valid JSON.']);
            }
        } elseif (! empty($connector->config_array['test_payload']) && is_array($connector->config_array['test_payload'])) {
            $custom = $connector->config_array['test_payload'];
        }

        $result = $this->manager->runTest($connector, $custom);

        $summary = sprintf(
            'Test sync (%s): created %d, updated %d, failed %d.',
            $result['log']->status,
            $result['log']->created_count,
            $result['log']->updated_count,
            $result['log']->failed_count
        );

        if ($result['log']->failed_count > 0) {
            $firstError = collect($result['results'])->firstWhere('status', 'error');
            $detail = '';
            if (is_array($firstError)) {
                if (! empty($firstError['errors']) && is_array($firstError['errors'])) {
                    $detail = collect($firstError['errors'])->flatten()->implode(' ');
                } elseif (! empty($firstError['message'])) {
                    $detail = (string) $firstError['message'];
                }
            }

            return back()->withErrors([
                'connector' => trim($summary.' '.$detail),
            ]);
        }

        return back()->with('success', $summary);
    }

    public function resetMap(Connector $connector): RedirectResponse
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        $connector->update(['field_map' => $this->manager->driver()->defaultFieldMap()]);

        return back()->with('success', 'Field map reset to generic defaults.');
    }
}
