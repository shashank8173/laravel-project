@extends('layouts.app')

@section('title', 'API Tokens')
@section('heading', 'API Tokens')

@section('page_actions')
<a href="{{ route('developer.optional-setup') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fa-solid fa-sliders me-1"></i> Optional Setup
</a>
@endsection

@push('styles')
<style>
    .api-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .api-wrap .api-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.2rem 1.35rem;
    }
    .api-wrap .api-hero h2 { margin:0; font-size:1.25rem; font-weight:800; }
    .api-wrap .api-hero p { margin:.35rem 0 0; opacity:.88; font-size:.9rem; max-width:720px; }
    .api-wrap .api-hero code {
        background:rgba(255,255,255,.14); padding:.1rem .4rem; border-radius:6px; font-size:.82rem;
    }
    .api-wrap .api-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
    }
    .api-wrap .api-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .api-wrap .api-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .api-wrap .api-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .api-wrap .api-panel-body { padding:1.15rem; }
    .api-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .api-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
        border-radius:10px; padding:.45rem 1rem;
    }
    .api-wrap .token-box {
        background:#0f2744; color:#e8eef5; border-radius:12px; padding:1rem 1.1rem;
        font-family:ui-monospace,Consolas,monospace; font-size:.86rem; word-break:break-all;
    }
    .api-wrap .pill {
        display:inline-flex; padding:.22rem .6rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .api-wrap .pill-ok { background:#dcfce7; color:#15803d; }
    .api-wrap .pill-off { background:#fee2e2; color:#b91c1c; }
    .api-wrap .endpoint {
        background:var(--soft); border:1px solid var(--line); border-radius:10px; padding:.55rem .75rem;
        font-family:ui-monospace,Consolas,monospace; font-size:.8rem; margin-bottom:.45rem;
    }
    .api-wrap .endpoint .m { color:#c2410c; font-weight:700; display:inline-block; min-width:3.2rem; }
</style>
@endpush

@section('content')
<div class="api-wrap">
    <div class="api-hero">
        <h2>Employee API tokens</h2>
        <p>
            Create a Bearer token for any third-party tool, middleware, or Postman.
            Base URL: <code>{{ $baseUrl }}</code>
        </p>
    </div>

    @if(session('plain_token'))
        <div class="alert alert-warning border-0 shadow-sm">
            <strong>Copy this token now.</strong> It is shown only once.
            <div class="token-box mt-2" id="plainToken">{{ session('plain_token') }}</div>
            <button type="button" class="btn btn-sm btn-dark mt-2" onclick="navigator.clipboard.writeText(document.getElementById('plainToken').innerText)">
                Copy token
            </button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="api-panel">
                <div class="api-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-key me-1" style="color:var(--accent)"></i> Create token</h5>
                        <span class="sub">Super admin only</span>
                    </div>
                </div>
                <div class="api-panel-body">
                    <form method="POST" action="{{ route('developer.api-tokens.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Token name</label>
                            <input type="text" name="name" class="form-control" required
                                   placeholder="Payroll sync / partner API" value="{{ old('name') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Abilities</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="*" id="abAll" checked>
                                <label class="form-check-label" for="abAll">Full access (*)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="employees:read" id="abRead">
                                <label class="form-check-label" for="abRead">employees:read</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="abilities[]" value="employees:write" id="abWrite">
                                <label class="form-check-label" for="abWrite">employees:write</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Expires in days (optional)</label>
                            <input type="number" name="expires_days" class="form-control" min="1" max="3650"
                                   placeholder="Leave blank = never" value="{{ old('expires_days') }}">
                        </div>
                        <button class="btn btn-save w-100"><i class="fa-solid fa-plus me-1"></i> Generate token</button>
                    </form>
                </div>
            </div>

            <div class="api-panel">
                <div class="api-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-book me-1" style="color:var(--accent)"></i> Endpoints</h5>
                        <span class="sub">Use Authorization: Bearer TOKEN</span>
                    </div>
                </div>
                <div class="api-panel-body">
                    <div class="endpoint"><span class="m">GET</span> {{ $baseUrl }}/health</div>
                    <div class="endpoint"><span class="m">GET</span> {{ $baseUrl }}/employees</div>
                    <div class="endpoint"><span class="m">GET</span> {{ $baseUrl }}/employees/{id}</div>
                    <div class="endpoint"><span class="m">POST</span> {{ $baseUrl }}/employees</div>
                    <div class="endpoint"><span class="m">POST</span> {{ $baseUrl }}/employees/upsert</div>
                    <div class="endpoint"><span class="m">PUT</span> {{ $baseUrl }}/employees/{id}</div>
                    <div class="endpoint"><span class="m">DEL</span> {{ $baseUrl }}/employees/{id}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="api-panel">
                <div class="api-panel-head">
                    <div>
                        <h5><i class="fa-solid fa-list me-1" style="color:var(--accent)"></i> Issued tokens</h5>
                        <span class="sub">{{ $tokens->count() }} token(s)</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th class="ps-3">Name</th>
                            <th>Prefix</th>
                            <th>Abilities</th>
                            <th>Status</th>
                            <th>Last used</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($tokens as $token)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $token->name }}</div>
                                    <div class="small text-muted">
                                        by {{ $token->creator?->full_name ?: '—' }}
                                        · {{ $token->created_at?->format('d M Y H:i') }}
                                    </div>
                                </td>
                                <td><code>{{ $token->token_prefix }}…</code></td>
                                <td class="small">{{ implode(', ', $token->abilities ?? ['*']) }}</td>
                                <td>
                                    @if($token->isActive())
                                        <span class="pill pill-ok">Active</span>
                                    @else
                                        <span class="pill pill-off">{{ $token->revoked_at ? 'Revoked' : 'Expired' }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $token->last_used_at?->format('d M Y H:i') ?: 'Never' }}
                                </td>
                                <td class="text-end pe-3 text-nowrap">
                                    @if($token->isActive())
                                        <form method="POST" action="{{ route('developer.api-tokens.revoke', $token) }}" class="d-inline"
                                              onsubmit="return confirm('Revoke this token?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning">Revoke</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('developer.api-tokens.destroy', $token) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this token permanently?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No API tokens yet.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
