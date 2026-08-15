@extends('layouts.app')

@section('title', 'Connectors')
@section('heading', 'Connectors')

@section('page_actions')
<a href="{{ route('developer.connector-training') }}" class="btn btn-outline-secondary btn-sm">Training</a>
<a href="{{ route('developer.api-tokens') }}" class="btn btn-outline-secondary btn-sm">API Tokens</a>
@endsection

@push('styles')
<style>
    .cn-wrap .card {
        border: 0;
        box-shadow: 0 .125rem .25rem rgba(15, 39, 68, .08);
        margin-bottom: 1.25rem;
    }
    .cn-wrap .card-header {
        background: #0f2744 !important;
        color: #fff !important;
        border: 0;
        padding: .85rem 1.15rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
    }
    .cn-wrap .card-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1rem;
        color: #fff !important;
    }
    .cn-wrap .card-header .sub {
        display: block;
        font-size: .78rem;
        opacity: .85;
        margin-top: .2rem;
        font-weight: 400;
    }
    .cn-wrap .card-body { padding: 1.15rem 1.25rem; background: #fff; }
    .cn-wrap .form-label { font-size: .8rem; font-weight: 600; color: #6b7c93; }
    .cn-wrap .help { font-size: .75rem; color: #6b7c93; margin-top: .25rem; }
    .cn-wrap .pill { display: inline-flex; padding: .22rem .65rem; border-radius: 999px; font-size: .72rem; font-weight: 700; }
    .cn-wrap .pill-on { background: #dcfce7; color: #15803d; }
    .cn-wrap .pill-off { background: #f1f5f9; color: #64748b; }
    .cn-wrap .btn-save {
        background: #ff9b44; border-color: #ff9b44; color: #fff; font-weight: 700;
        border-radius: 10px; padding: .4rem .9rem;
    }
    .cn-wrap code.url, .cn-wrap .secret {
        display: block; border-radius: 10px; padding: .55rem .75rem; font-size: .78rem; word-break: break-all;
    }
    .cn-wrap code.url { background: #f4f7fb; border: 1px solid #e8eef5; color: #0f2744; }
    .cn-wrap .secret { background: #0f2744; color: #e8eef5; font-family: ui-monospace, Consolas, monospace; }
    .cn-wrap .table thead th {
        background: #0f2744 !important;
        color: #fff !important;
        font-size: .78rem;
        border-color: #0f2744 !important;
    }
</style>
@endpush

@section('content')
<div class="cn-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header">
            <div>
                <h5>Connectors (any third-party tool)</h5>
                <span class="sub">Create a connector, share its webhook URL + secret with any tool. Incoming JSON is mapped dynamically into employees.</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('developer.connectors.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-8">
                    <label class="form-label">Connector name</label>
                    <input type="text" name="name" class="form-control" required placeholder="Main HR Sync">
                    <div class="help">Name it anything — “Payroll sync”, “HR import”, “Partner feed”…</div>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-save w-100">Create connector</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($connectors as $connector)
        @php $cfg = $connector->config_array; @endphp
        <div class="card shadow-sm border-0" id="c-{{ $connector->slug }}">
            <div class="card-header">
                <div>
                    <h5><i class="fa-solid fa-plug me-1"></i> {{ $connector->name }}</h5>
                    <span class="sub">Slug: {{ $connector->slug }} · accepts dynamic JSON from any source</span>
                </div>
                <span class="pill {{ $connector->enabled ? 'pill-on' : 'pill-off' }}">
                    {{ $connector->enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('developer.connectors.update', $connector) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Display name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $connector->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Enable</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="enabled" value="1" @checked(old('enabled', $connector->enabled))>
                                <label class="form-check-label">Active</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Webhook URL (give this to any third-party tool)</label>
                            <code class="url">{{ $connector->webhookUrl() }}</code>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Webhook secret</label>
                            <div class="secret">{{ $connector->webhook_secret }}</div>
                            <div class="help">Header <code>X-Connector-Secret</code></div>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="rotate_secret" value="1" id="rot-{{ $connector->id }}">
                                <label class="form-check-label" for="rot-{{ $connector->id }}">Rotate secret on save</label>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Optional Pull URL</label>
                            <input type="text" name="config_pull_url" class="form-control"
                                   value="{{ $cfg['pull_url'] ?? '' }}"
                                   placeholder="Leave empty for webhook-only / local test">
                            <div class="help">Only if a third-party feed URL returns JSON employees.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pull Authorization</label>
                            <input type="text" name="config_pull_auth" class="form-control" placeholder="Bearer … (optional)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Field map (source key → HRM field) — dynamic</label>
                        <textarea name="field_map_json" rows="9" class="form-control font-monospace @error('field_map_json') is-invalid @enderror">{{ old('field_map_json', json_encode($connector->field_map ?: $defaultMap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        @error('field_map_json')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="help">
                            Map whatever keys the third-party sends. Example:
                            <code>{"first_name":"fname","email":"office_email","employee_id":"external_id"}</code>
                            If payload already uses HRM keys (<code>fname</code>, <code>office_email</code>), they are accepted dynamically too.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Test payload (dynamic JSON for “Run test sync”)</label>
                        <textarea name="test_payload_json" rows="10" class="form-control font-monospace">{{ old('test_payload_json', json_encode($cfg['test_payload'] ?? $samplePayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
                        <div class="help">Change this anytime — not locked to a fixed vendor shape.</div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button class="btn btn-save" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Save</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('reset-{{ $connector->id }}').submit();">Reset map</button>
                        @if($connector->last_sync_at)
                            <span class="small text-muted ms-auto">
                                Last sync: {{ $connector->last_sync_at->format('d M Y H:i') }}
                                · {{ $connector->last_sync_status }}
                                · {{ $connector->last_sync_message }}
                            </span>
                        @endif
                    </div>
                </form>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <form method="POST" action="{{ route('developer.connectors.test', $connector) }}">
                        @csrf
                        <button class="btn btn-outline-primary btn-sm">Run test sync</button>
                    </form>
                    <form method="POST" action="{{ route('developer.connectors.pull', $connector) }}">
                        @csrf
                        <button class="btn btn-outline-success btn-sm">Pull now</button>
                    </form>
                    <form method="POST" action="{{ route('developer.connectors.destroy', $connector) }}" onsubmit="return confirm('Delete this connector?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm">Delete</button>
                    </form>
                </div>

                <form id="reset-{{ $connector->id }}" method="POST" action="{{ route('developer.connectors.reset-map', $connector) }}" class="d-none">@csrf</form>
            </div>
        </div>
    @endforeach

    <div class="card shadow-sm border-0">
        <div class="card-header">
            <div>
                <h5><i class="fa-solid fa-clock-rotate-left me-1"></i> Recent sync logs</h5>
                <span class="sub">Inbound webhooks and pulls</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th class="ps-3">When</th>
                    <th>Connector</th>
                    <th>Direction</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Updated</th>
                    <th>Failed</th>
                </tr>
                </thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="ps-3 small">{{ $log->created_at?->format('d M Y H:i') }}</td>
                        <td>{{ $log->connector?->name }}</td>
                        <td>{{ $log->direction }}</td>
                        <td>{{ $log->status }}</td>
                        <td>{{ $log->created_count }}</td>
                        <td>{{ $log->updated_count }}</td>
                        <td>{{ $log->failed_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No syncs yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
