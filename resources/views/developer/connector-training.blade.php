@extends('layouts.app')

@section('title', 'Connector Training')
@section('heading', 'Connector Training')

@section('page_actions')
<a href="{{ route('developer.integration-guide') }}" class="btn btn-outline-secondary btn-sm">Full Guide</a>
<a href="{{ route('developer.connectors') }}" class="btn btn-outline-secondary btn-sm">Connectors</a>
@endsection

@push('styles')
<style>
    .tr-wrap .card {
        border: 0;
        box-shadow: 0 .125rem .25rem rgba(15, 39, 68, .08);
        margin-bottom: 1.25rem;
    }
    .tr-wrap .card-header {
        background: #0f2744 !important;
        color: #fff !important;
        font-weight: 700;
        border: 0;
        padding: .85rem 1.15rem;
    }
    .tr-wrap .card-body {
        background: #fff;
        padding: 1.25rem 1.35rem;
        color: #334155;
        font-size: .92rem;
        line-height: 1.55;
    }
    .tr-wrap .card-body h5 {
        font-size: .95rem;
        font-weight: 750;
        color: #0f2744;
        margin: 1rem 0 .4rem;
    }
    .tr-wrap .card-body h5:first-child { margin-top: 0; }
    .tr-wrap .step {
        display: flex;
        gap: .85rem;
        margin: .65rem 0;
        padding: .7rem .85rem;
        background: #f4f7fb;
        border: 1px solid #e8eef5;
        border-radius: .5rem;
    }
    .tr-wrap .n {
        flex: 0 0 28px;
        height: 28px;
        border-radius: 50%;
        background: #0f2744;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: .8rem;
    }
    .tr-wrap .ok { color: #15803d; font-weight: 700; }
    .tr-wrap .bad { color: #b91c1c; font-weight: 700; }
    .tr-wrap code {
        background: #f4f7fb;
        border: 1px solid #e8eef5;
        border-radius: 6px;
        padding: .05rem .35rem;
        font-size: .82rem;
        color: #0f2744;
    }
    .tr-wrap .say {
        border-left: 4px solid #0f2744;
        background: #f4f7fb;
        padding: .7rem .9rem;
        border-radius: 0 .5rem .5rem 0;
        margin: .75rem 0 0;
        font-size: .9rem;
        color: #475569;
    }
    .tr-wrap .table thead th {
        background: #0f2744 !important;
        color: #fff !important;
        font-size: .78rem;
        border-color: #0f2744 !important;
    }
    .tr-wrap .table td {
        vertical-align: middle;
        font-size: .88rem;
    }
    .tr-wrap pre {
        background: #f4f7fb;
        border: 1px solid #e8eef5;
        border-radius: .5rem;
        padding: .75rem .9rem;
        margin: .4rem 0 0;
        white-space: pre-wrap;
        font-size: .82rem;
    }
    @media print {
        .sidebar, .header, .page-header .page-actions { display: none !important; }
    }
</style>
@endpush

@section('content')
<div class="tr-wrap">
    <div class="card shadow-sm border-0">
        <div class="card-header">Overview</div>
        <div class="card-body">
            <p class="mb-2"><strong>Bring employees into this HRM from any third-party tool.</strong></p>
            <p class="mb-2">Connectors are generic — not locked to Salesforce, Zoho, or any brand. Use this page to learn and train others.</p>
            <p class="mb-2">Our HRM is one system. Outside it, any third-party tool can send data (HR / payroll / CRM / custom app).</p>
            <p class="mb-1">There are two ways to bring employees in:</p>
            <ul class="mb-0">
                <li><strong>Path A — Test sync / Postman:</strong> for local practice and training</li>
                <li><strong>Path B — Real company:</strong> their IT team posts dynamic JSON to our connector webhook or API</li>
            </ul>
            <div class="say">
                Trainer says: “Connectors are generic — no fixed brand. Practice first; later their IT will send real data to the same URL.”
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Training Session 1 — Local practice (30 minutes)</div>
        <div class="card-body">
            <div class="step"><span class="n">1</span><div>
                <strong>Log in</strong> as super admin.
            </div></div>

            <div class="step"><span class="n">2</span><div>
                Sidebar → <strong>Developer → Connectors</strong><br>
                Open any connector (or click <strong>Add connector</strong>). A vendor brand name is not required.
            </div></div>

            <div class="step"><span class="n">3</span><div>
                Set Enable = <strong>Active</strong> → set Field map / Test payload → <strong>Save</strong>
            </div></div>

            <div class="step"><span class="n">4</span><div>
                Check the field map. Do <span class="bad">not put employee values here</span>.<br>
                Correct example:
                <code>{"first_name":"fname","email":"office_email"}</code><br>
                Wrong example:
                <code>{"first_name":"Himanshu"}</code><br>
                If unsure → <strong>Reset map</strong> → Save
            </div></div>

            <div class="step"><span class="n">5</span><div>
                Keep <strong>Pull URL empty</strong> (do not click Pull now for local testing).<br>
                Only click <strong>Run test sync</strong>.
            </div></div>

            <div class="step"><span class="n">6</span><div>
                You should see a success message: created / updated.<br>
                Then open the <strong>Employees</strong> list — the new name should appear.
            </div></div>

            <div class="say">
                Trainer says: “Test sync means the system sent a dummy employee itself. That proves the connector is working.”
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Training Session 2 — Dummy data with Postman (20 minutes)</div>
        <div class="card-body">
            <p class="mb-2">Teach this: “how data arrives from outside”</p>

            <div class="step"><span class="n">1</span><div>
                From the Connectors page, copy:
                <ul class="mb-0">
                    <li>Webhook URL</li>
                    <li>Webhook secret (<code>whsec_...</code>)</li>
                </ul>
            </div></div>

            <div class="step"><span class="n">2</span><div>
                In Postman:
                <ul class="mb-0">
                    <li>Method = <strong>POST</strong></li>
                    <li>URL = webhook URL</li>
                    <li>Auth = <strong>No Auth</strong> (do not use Bearer)</li>
                    <li>Header: <code>X-Connector-Secret</code> = secret</li>
                    <li>Header: <code>Content-Type</code> = <code>application/json</code></li>
                </ul>
            </div></div>

            <div class="step"><span class="n">3</span><div>
                Body → raw → JSON:
<pre>{
  "employees": [{
    "employee_id": "TRAIN-001",
    "employee_code": "T001",
    "first_name": "Ravi",
    "last_name": "Kumar",
    "email": "ravi.kumar@example.com",
    "phone": "9999900001",
    "department": "Sales",
    "designation": "Executive",
    "date_of_joining": "2024-01-10"
  }]
}</pre>
            </div></div>

            <div class="step"><span class="n">4</span><div>
                Send → response should show <span class="ok">success</span><br>
                <strong>Ravi Kumar</strong> should appear in Employees.<br>
                Send the same body again → it should update (not create a duplicate).
            </div></div>

            <div class="say">
                Trainer says: “Postman stands in for any third-party tool. In production their system will send the same kind of dynamic JSON — the field map links their keys to our HRM fields.”
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Training Session 3 — What to give a real company (15 minutes)</div>
        <div class="card-body">
            <p class="mb-2">When a client migrates real data from a third-party tool, give them these 5 items:</p>
            <ol>
                <li><strong>Base URL</strong> (production site + <code>/api/v1</code>)</li>
                <li><strong>API Token</strong> (Developer → API Tokens) — for generic sync</li>
                <li><strong>Webhook URL + Secret</strong> (Developer → Connectors — slug-based URL)</li>
                <li><strong>Field mapping sheet</strong> (their fields → our fields)</li>
                <li><strong>Integration Guide</strong> page / Postman collection</li>
            </ol>

            <h5>Tell them clearly</h5>
            <ul class="mb-0">
                <li>Every employee needs a unique <strong>official email</strong></li>
                <li>Every employee needs a stable <strong>external_id</strong> (ID from their source tool)</li>
                <li>Best API: <code>POST /api/v1/employees/upsert</code></li>
                <li>Or connector webhook: <code>POST /api/v1/connectors/{slug}/webhook</code></li>
                <li>Login to this HRM uses <code>office_email</code> + password</li>
            </ul>

            <div class="say">
                Trainer says: “Our job is to open the door (API / Connector). Their IT sends the data. We provide the field map and token.”
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Common mistakes / fixes</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                    <tr>
                        <th>Problem</th>
                        <th>Meaning</th>
                        <th>Fix</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>Invalid connector webhook secret</td>
                        <td>Wrong secret, or Bearer token was used</td>
                        <td>Put the correct <code>whsec_</code> in header <code>X-Connector-Secret</code></td>
                    </tr>
                    <tr>
                        <td>GET method not supported</td>
                        <td>Webhook URL was put in Pull URL</td>
                        <td>Leave Pull URL empty; use Test sync or Postman</td>
                    </tr>
                    <tr>
                        <td>created 0 failed 1</td>
                        <td>Employee values were put in the field map</td>
                        <td>Reset map → Save → Run test sync</td>
                    </tr>
                    <tr>
                        <td>Employee cannot log in</td>
                        <td>Wrong email</td>
                        <td>Log in with <code>office_email</code>; default password <code>Welcome@123</code></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Trainer script (1-minute speech)</div>
        <div class="card-body">
            <div class="say mt-0">
                “This HRM has generic Connectors and an API so outside systems can send employees in.
                Locally we test with Test Sync and Postman — no fixed vendor login is required.
                For a real company, their IT posts employee JSON to our webhook URL or upsert API (dynamic fields + field map).
                We give them the token, webhook secret, field map, and this training / integration guide.
                Simple rule: unique official email, stable external id, test first, then go live.”
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Practice checklist</div>
        <div class="card-body">
            <ul class="mb-0">
                <li>☐ Enabled connector and saved</li>
                <li>☐ Understood Reset map</li>
                <li>☐ Run test sync succeeded</li>
                <li>☐ Record appeared in Employees list</li>
                <li>☐ Created 1 employee via Postman</li>
                <li>☐ Sent same data again → updated (no duplicate)</li>
                <li>☐ Remembers the 5-item client handoff list</li>
            </ul>
        </div>
    </div>
</div>
@endsection
