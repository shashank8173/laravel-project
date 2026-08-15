@extends('layouts.app')

@section('title', 'Integration Guide')
@section('heading', 'Integration Guide')

@section('page_actions')
<a href="{{ route('developer.connectors') }}" class="btn btn-outline-secondary btn-sm">Connectors</a>
<a href="{{ route('developer.api-tokens') }}" class="btn btn-outline-secondary btn-sm">API Tokens</a>
@endsection

@push('styles')
<style>
    .ig-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; max-width:980px; }
    .ig-wrap .ig-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background: linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%);
        color:#fff; padding:1.25rem 1.35rem;
    }
    .ig-wrap .ig-hero h2 { margin:0; font-size:1.3rem; font-weight:800; }
    .ig-wrap .ig-hero p { margin:.4rem 0 0; opacity:.9; font-size:.92rem; }
    .ig-wrap .ig-panel {
        background:#fff; border:1px solid var(--line); border-radius:16px; padding:1.15rem 1.25rem; margin-bottom:1rem;
    }
    .ig-wrap h3 { font-size:1.05rem; font-weight:800; color:var(--ink); margin:0 0 .65rem; }
    .ig-wrap h4 { font-size:.92rem; font-weight:750; color:var(--ink); margin:1rem 0 .4rem; }
    .ig-wrap p, .ig-wrap li { color:#334155; font-size:.9rem; line-height:1.55; }
    .ig-wrap code, .ig-wrap pre {
        background:var(--soft); border:1px solid var(--line); border-radius:8px; font-size:.8rem;
    }
    .ig-wrap code { padding:.1rem .35rem; }
    .ig-wrap pre { padding:.85rem 1rem; overflow:auto; white-space:pre-wrap; }
    .ig-wrap table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .ig-wrap th, .ig-wrap td { border:1px solid var(--line); padding:.5rem .65rem; text-align:left; vertical-align:top; }
    .ig-wrap th { background:var(--soft); color:var(--muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.03em; }
    .ig-wrap .pill { display:inline-block; background:#fff7ed; color:#c2410c; border-radius:999px; padding:.15rem .55rem; font-size:.72rem; font-weight:700; }
    @media print {
        .sidebar, .header, .page-header .page-actions, .sidebar-overlay { display:none !important; }
        .ig-wrap { max-width:100%; }
    }
</style>
@endpush

@section('content')
@php
    $base = rtrim((string) config('app.url'), '/').'/api/v1';
@endphp
<div class="ig-wrap">
    <div class="ig-hero">
        <h2>Leadforgrow HRM — Employee Integration Guide</h2>
        <p>Share this with IT / middleware teams pushing employees from any third-party HR / payroll / CRM tool.</p>
    </div>

    <div class="ig-panel">
        <h3>1. What we support</h3>
        <p>You can push employee master data into Leadforgrow HRM in two ways:</p>
        <ol>
            <li><strong>REST Employee API</strong> — generic create / update / upsert (any source system).</li>
            <li><strong>Connectors (webhooks)</strong> — create named connectors; any tool posts dynamic JSON; field map converts their keys to our HRM fields.</li>
        </ol>
        <p><span class="pill">Recommended for migration</span> use <code>POST {{ $base }}/employees/upsert</code> or a connector webhook URL from Developer → Connectors.</p>
    </div>

    <div class="ig-panel">
        <h3>2. Documents / credentials we must give the client</h3>
        <table>
            <thead>
            <tr><th>Document / item</th><th>Why needed</th><th>Where in HRM</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>Base API URL</td>
                <td>All calls go here</td>
                <td><code>{{ $base }}</code></td>
            </tr>
            <tr>
                <td>API Token (Bearer)</td>
                <td>Auth for Employee REST API</td>
                <td>Developer → API Tokens</td>
            </tr>
            <tr>
                <td>Connector webhook URL</td>
                <td>Per-system inbound endpoint</td>
                <td>Developer → Connectors</td>
            </tr>
            <tr>
                <td>Connector webhook secret</td>
                <td>Header <code>X-Connector-Secret</code></td>
                <td>Developer → Connectors</td>
            </tr>
            <tr>
                <td>Field mapping sheet</td>
                <td>Their fields → our fields</td>
                <td>See section 5 below</td>
            </tr>
            <tr>
                <td>Sample Postman collection</td>
                <td>Quick validation</td>
                <td><code>postman/Leadforgrow-HRM-Employee-API.postman_collection.json</code></td>
            </tr>
            <tr>
                <td>This Integration Guide</td>
                <td>End-to-end steps</td>
                <td>Developer → Integration Guide (this page)</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="ig-panel">
        <h3>3. Auth</h3>
        <h4>Employee API</h4>
        <pre>Authorization: Bearer hrm_xxxxxxxxxxxx
Accept: application/json
Content-Type: application/json</pre>
        <h4>Connector webhook</h4>
        <pre>X-Connector-Secret: whsec_xxxxxxxxxxxx
Content-Type: application/json

# Do NOT use Bearer token for connector webhooks</pre>
    </div>

    <div class="ig-panel">
        <h3>4. API endpoints to share</h3>
        <table>
            <thead>
            <tr><th>Method</th><th>Endpoint</th><th>Purpose</th></tr>
            </thead>
            <tbody>
            <tr><td>GET</td><td><code>{{ $base }}/health</code></td><td>Connectivity check (no auth)</td></tr>
            <tr><td>GET</td><td><code>{{ $base }}/employees</code></td><td>List employees</td></tr>
            <tr><td>GET</td><td><code>{{ $base }}/employees/{id}</code></td><td>Get one</td></tr>
            <tr><td>POST</td><td><code>{{ $base }}/employees</code></td><td>Create</td></tr>
            <tr><td>POST</td><td><code>{{ $base }}/employees/upsert</code></td><td><strong>Create or update</strong> (best for sync)</td></tr>
            <tr><td>PUT/PATCH</td><td><code>{{ $base }}/employees/{id}</code></td><td>Update by id</td></tr>
            <tr><td>DELETE</td><td><code>{{ $base }}/employees/{id}</code></td><td>Archive employee</td></tr>
            <tr><td>POST</td><td><code>{{ $base }}/connectors/{slug}/webhook</code></td><td>Generic connector (slug from Developer → Connectors)</td></tr>
            </tbody>
        </table>
    </div>

    <div class="ig-panel">
        <h3>5. Field mapping (give this sheet to client)</h3>
        <table>
            <thead>
            <tr><th>Leadforgrow field</th><th>Required?</th><th>Notes</th></tr>
            </thead>
            <tbody>
            <tr><td><code>fname</code></td><td>Yes (create)</td><td>First name</td></tr>
            <tr><td><code>lname</code></td><td>No</td><td>Last name</td></tr>
            <tr><td><code>office_email</code></td><td>Yes (create / login)</td><td>Official email — used for HRM login</td></tr>
            <tr><td><code>external_id</code></td><td>Strongly recommended</td><td>Stable ID from the source tool (any third-party primary key)</td></tr>
            <tr><td><code>emp_id</code></td><td>Recommended</td><td>Employee code</td></tr>
            <tr><td><code>mobile1</code></td><td>No</td><td>Mobile</td></tr>
            <tr><td><code>department</code></td><td>No</td><td>Name — auto-created if missing</td></tr>
            <tr><td><code>designation</code> / <code>job_title</code></td><td>No</td><td>Job role</td></tr>
            <tr><td><code>doj</code>, <code>dob</code></td><td>No</td><td><code>YYYY-MM-DD</code></td></tr>
            <tr><td><code>salary</code></td><td>No</td><td>String/number as text</td></tr>
            <tr><td><code>password</code></td><td>No</td><td>Default <code>Welcome@123</code> if omitted</td></tr>
            </tbody>
        </table>

        <h4>Upsert match order</h4>
        <ol>
            <li><code>external_id</code></li>
            <li>then <code>office_email</code></li>
            <li>then <code>emp_id</code></li>
        </ol>
        <p>Always send a stable <code>external_id</code> from the source HRM so re-sync updates instead of duplicating.</p>
    </div>

    <div class="ig-panel">
        <h3>6. Sample upsert payload (generic API)</h3>
<pre>{
  "external_id": "SF-003XX000004TMM2",
  "emp_id": "EMP1001",
  "fname": "Himanshu",
  "lname": "Singh",
  "office_email": "himanshu.singh@company.com",
  "mobile1": "9876543210",
  "department": "Engineering",
  "designation": "Developer",
  "job_title": "Software Developer",
  "doj": "2024-06-01",
  "salary": "55000",
  "role": "user"
}</pre>
    </div>

    <div class="ig-panel">
        <h3>7. Real-time migration steps for client IT</h3>
        <ol>
            <li>Get from Leadforgrow admin: <strong>API token</strong> and/or <strong>connector webhook + secret</strong>.</li>
            <li>Map source fields using the sheet in section 5.</li>
            <li>Build middleware / Flow / Zapier / custom job from any source tool → our webhook or upsert API. Payload shape is dynamic; map fields on the connector.</li>
            <li>First run: full employee export → upsert all active employees.</li>
            <li>Ongoing: send create/update events in near real-time to the same endpoint.</li>
            <li>Validate in Leadforgrow: Employees list + login with <code>office_email</code>.</li>
        </ol>
    </div>

    <div class="ig-panel">
        <h3>8. Checklist before go-live</h3>
        <ul>
            <li>Production base URL uses HTTPS</li>
            <li>API token / webhook secret stored securely (not in frontend JS)</li>
            <li>Every employee has unique <code>office_email</code></li>
            <li>Every employee has stable <code>external_id</code></li>
            <li>Departments/designations naming agreed</li>
            <li>Test upsert twice (2nd call must update, not duplicate)</li>
            <li>Inactive/exited employees archived (DELETE API or status mapping)</li>
        </ul>
    </div>

    <div class="ig-panel">
        <h3>9. What Leadforgrow admin prepares</h3>
        <ol>
            <li>Developer → <strong>API Tokens</strong> → create token (abilities <code>*</code>) → share once.</li>
            <li>Developer → <strong>Connectors</strong> → Add connector → set field map + enable → share webhook URL + secret.</li>
            <li>Send this Integration Guide page (or print/PDF).</li>
            <li>Send Postman collection for self-test.</li>
            <li>On go-live, rotate secrets if needed.</li>
        </ol>
    </div>
</div>
@endsection
