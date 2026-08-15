@extends('layouts.app')

@section('title', 'Expenses Management')
@section('heading', 'Expenses Management')

@section('page_actions')
<button type="button" class="btn add-btn me-2" data-bs-toggle="modal" data-bs-target="#expenseModal">
    <i class="fa-solid fa-plus"></i> Add Expense
</button>
<a href="{{ route('expenses.admin.pdf', request()->only(['status','employee','company','month','week','from_date','to_date'])) }}"
   class="btn btn-outline-danger me-2">
    <i class="fa-solid fa-file-pdf"></i> Download PDF
</a>
<button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#manageCategoriesModal">
    Categories
</button>
<button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal" data-bs-target="#manageCompaniesModal">
    Companies
</button>
<button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#shareModal">
    Share
</button>
@endsection

@push('styles')
<style>
    .ex-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44;
        --card:#fff; --panel-head:linear-gradient(180deg,#fff,#fafbfd); --input-bg:#fff; --hover:#fafbfd;
        color:var(--ink);
    }
    .ex-wrap .ex-panel { background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; color:var(--ink); }
    .ex-wrap .ex-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:var(--panel-head);
    }
    .ex-wrap .ex-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .ex-wrap .ex-panel-body { padding:1.15rem; background:var(--card); color:var(--ink); }
    .ex-wrap .ex-metric {
        border:1px solid var(--line); border-radius:14px; background:var(--card); padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden; color:var(--ink);
    }
    .ex-wrap .ex-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .ex-wrap .ex-metric.is-pending::before { background:#f59e0b; }
    .ex-wrap .ex-metric.is-ok::before { background:#16a34a; }
    .ex-wrap .ex-metric.is-bad::before { background:#ef4444; }
    .ex-wrap .ex-metric .k { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .ex-wrap .ex-metric .v { font-size:1.45rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .ex-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ex-wrap .form-select, .ex-wrap .form-control {
        background:var(--input-bg); color:var(--ink); border-color:var(--line);
    }
    .ex-wrap .table { color:var(--ink); --bs-table-bg:transparent; --bs-table-color:var(--ink); --bs-table-border-color:var(--line); --bs-table-hover-bg:var(--hover); --bs-table-hover-color:var(--ink); }
    .ex-wrap .table > :not(caption) > * > * { vertical-align:middle; background:transparent; color:var(--ink); border-color:var(--line); }
    .ex-wrap .table thead th { background:var(--soft); color:var(--muted); }
    .ex-wrap .badge-pending { background:#fef3c7; color:#b45309; }
    .ex-wrap .badge-approved { background:#dcfce7; color:#15803d; }
    .ex-wrap .badge-rejected { background:#fee2e2; color:#b91c1c; }
    .ex-wrap .ex-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700;
    }
    .ex-wrap .row-advance { background:rgba(244,63,94,.12) !important; }
    .ex-wrap .ex-month-chip {
        display:flex; justify-content:space-between; gap:.75rem; border:1px solid var(--line);
        border-radius:12px; padding:.65rem .85rem; background:var(--soft); margin-bottom:.5rem; color:var(--ink);
    }
    .ex-wrap .ex-remain-ok { background:#dcfce7; color:#15803d; }
    .ex-wrap .ex-remain-bad { background:#fee2e2; color:#b91c1c; }
    .ex-wrap .ex-notice-body p { margin:0 0 .75rem; color:var(--ink); font-size:.92rem; }
    .ex-wrap .ex-notice-body .ex-note { margin:0; font-size:.85rem; color:var(--muted); }
    .ex-wrap .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .ex-wrap .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
    .ex-wrap .advance-acc .accordion-item {
        border:1px solid var(--line); border-radius:12px; overflow:hidden; margin-bottom:.55rem; background:var(--card);
        color:var(--ink);
    }
    .ex-wrap .advance-acc .accordion-item:last-child { margin-bottom:0; }
    .ex-wrap .advance-acc .accordion-button {
        background:var(--card) !important; color:var(--ink) !important; box-shadow:none; font-size:.95rem; padding:.85rem 1rem;
        gap:.65rem; align-items:center;
    }
    .ex-wrap .advance-acc .accordion-button:not(.collapsed) {
        background:var(--hover) !important; color:var(--ink) !important; box-shadow:none;
    }
    .ex-wrap .advance-acc .accordion-button:focus { box-shadow:none; border-color:transparent; }
    .ex-wrap .advance-acc .accordion-button::after {
        margin-left:.25rem;
        filter:none;
    }
    .ex-wrap .advance-acc .accordion-body { padding:.25rem 1rem 1rem; background:var(--card); color:var(--ink); }
    .ex-wrap .advance-acc .adv-type {
        font-size:.65rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase;
        color:var(--muted); flex:0 0 auto;
    }
    .ex-wrap .advance-acc .adv-name { font-weight:650; color:var(--ink); }
    .ex-wrap .advance-acc .adv-hint { font-size:.8rem; margin:0 0 .65rem; }
    .ex-wrap .advance-acc .adv-hint.is-ok { color:#15803d; }
    .ex-wrap .advance-acc .adv-hint.is-bad { color:#b91c1c; }

    html[data-theme="dark"] .ex-wrap {
        --ink:#e8eef8; --muted:#a8b6cc; --line:#243044; --soft:#1a2232; --card:#141b27;
        --panel-head:linear-gradient(180deg,#171e2c,#141b27); --input-bg:#0f1520; --hover:#1a2232;
    }
    html[data-theme="dark-blue"] .ex-wrap {
        --ink:#eaf2ff; --muted:#9db4d4; --line:#1a3358; --soft:#102240; --card:#0c1a31;
        --panel-head:linear-gradient(180deg,#0e1f3c,#0c1a31); --input-bg:#081528; --hover:#102240;
    }
    html[data-theme="light"] .ex-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --card:#ffffff;
        --panel-head:linear-gradient(180deg,#fff,#fafbfd); --input-bg:#ffffff; --hover:#fafbfd;
    }
    html[data-theme="dark"] .ex-wrap .advance-acc .accordion-button::after,
    html[data-theme="dark-blue"] .ex-wrap .advance-acc .accordion-button::after {
        filter: invert(1) grayscale(100%);
    }
    html[data-theme="dark"] .ex-wrap .btn-notice,
    html[data-theme="dark-blue"] .ex-wrap .btn-notice {
        background:rgba(245,158,11,.16); color:#fbbf24; border-color:rgba(245,158,11,.45);
    }
    html[data-theme="dark"] .ex-wrap .btn-notice:hover,
    html[data-theme="dark-blue"] .ex-wrap .btn-notice:hover {
        background:rgba(245,158,11,.28); color:#fde68a; border-color:#f59e0b;
    }
    html[data-theme="dark"] .ex-wrap .row-advance,
    html[data-theme="dark-blue"] .ex-wrap .row-advance {
        background:rgba(244,63,94,.18) !important;
    }
</style>
@endpush

@section('content')
@php
    $f = $filters;
    $qs = array_filter([
        'status' => $f['status'] ?: null,
        'employee' => $f['employee'] ?: null,
        'company' => $f['company'] ?: null,
        'month' => $f['month'] ?: null,
        'week' => $f['week'] ?: null,
        'from_date' => $f['from_date'] ?: null,
        'to_date' => $f['to_date'] ?: null,
    ], fn ($v) => $v !== null && $v !== '' && $v !== 0);
@endphp
<div class="ex-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="ex-metric">
                <div class="k">Filtered total</div>
                <p class="v">₹{{ number_format($totalAmount, 0) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ex-metric is-pending">
                <div class="k">Pending</div>
                <p class="v">{{ $counts['pending'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ex-metric is-ok">
                <div class="k">Approved</div>
                <p class="v">{{ $counts['approved'] }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="ex-metric is-bad">
                <div class="k">Rejected</div>
                <p class="v">{{ $counts['rejected'] }}</p>
            </div>
        </div>
    </div>

    <div class="ex-panel mb-3">
        <div class="ex-panel-head">
            <h5><i class="fa-solid fa-filter me-1" style="color:var(--accent)"></i> Filters</h5>
            @if(!empty($qs))
                <a href="{{ route('expenses.admin') }}" class="btn btn-sm btn-outline-secondary">Clear all</a>
            @endif
        </div>
        <div class="ex-panel-body">
            <form method="GET" action="{{ route('expenses.admin') }}" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(['Pending','Approved','Rejected'] as $s)
                            <option value="{{ $s }}" @selected(($f['status'] ?? '') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Employee</label>
                    <x-employee-select
                        name="employee"
                        :employees="$employees"
                        :selected="$f['employee']"
                        placeholder="All (search…)"
                    />
                </div>
                <div class="col-md-2">
                    <label class="form-label">Company</label>
                    <select name="company" class="form-select">
                        <option value="">All</option>
                        @foreach($companies as $com)
                            <option value="{{ $com->id }}" @selected((int)$f['company'] === (int)$com->id)>{{ $com->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Month</label>
                    <select name="month" class="form-select">
                        <option value="">All</option>
                        @foreach($months as $m)
                            <option value="{{ $m }}" @selected(($f['month'] ?? '') === $m)>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $m)->format('M Y') }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">Week</label>
                    <select name="week" class="form-select">
                        <option value="">All</option>
                        @foreach(['1','2','3','4','5'] as $w)
                            <option value="{{ $w }}" @selected(($f['week'] ?? '') === $w)>W{{ $w }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ $f['from_date'] }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ $f['to_date'] }}">
                </div>
                <div class="col-md-1">
                    <button class="btn add-btn w-100">Go</button>
                </div>
            </form>
        </div>
    </div>

    <div class="ex-panel mb-3">
        <div class="ex-panel-head">
            <h5>Monthly totals (filtered)</h5>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#advanceNoticeModal">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="ex-panel-body">
            @if($monthlyTotals->isNotEmpty())
                <div class="row g-2">
                    @foreach($monthlyTotals->take(8) as $month => $amount)
                        <div class="col-md-3">
                            <div class="ex-month-chip">
                                <span>{{ $month === 'unknown' ? '—' : \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</span>
                                <strong>₹{{ number_format($amount, 0) }}</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mb-0 text-muted small">No monthly totals for the current filters.</p>
            @endif
        </div>
    </div>

    @if(!empty($advanceBreakdowns))
    <div class="ex-panel mb-3">
        <div class="ex-panel-head">
            <h5><i class="fa-solid fa-wallet me-1" style="color:var(--accent)"></i> Advance balance (employees &amp; companies)</h5>
            <button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#advanceNoticeModal">
                <i class="fa-solid fa-circle-info me-1"></i> Notice
            </button>
        </div>
        <div class="ex-panel-body pt-2">
            <div class="accordion advance-acc" id="advanceBreakdownAccordion">
                @foreach($advanceBreakdowns as $idx => $row)
                    @php
                        $isPlus = $row['remaining'] >= 0;
                        $remainClass = $isPlus ? 'ex-remain-ok' : 'ex-remain-bad';
                        $cid = 'advBreak'.$idx;
                        $hint = $isPlus
                            ? ($row['remaining'] > 0
                                ? 'They still hold the company advance'
                                : 'Settled — company amount returned / balanced')
                            : 'Only expenses / overspent — showing minus';
                        $typeLabel = ($row['type'] ?? '') === 'company' ? 'Company' : 'Employee';
                    @endphp
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="h{{ $cid }}">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $cid }}">
                                <span class="adv-type">{{ $typeLabel }}</span>
                                <span class="adv-name">{{ $row['name'] }}</span>
                                <span class="ex-pill {{ $remainClass }} ms-auto me-2">
                                    {{ $isPlus ? '+' : '−' }} ₹{{ number_format(abs($row['remaining']), 2) }}
                                </span>
                            </button>
                        </h2>
                        <div id="{{ $cid }}" class="accordion-collapse collapse" data-bs-parent="#advanceBreakdownAccordion">
                            <div class="accordion-body">
                                <p class="adv-hint {{ $isPlus ? 'is-ok' : 'is-bad' }}">{{ $hint }}</p>
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead><tr><th>Category</th><th class="text-end">Total</th></tr></thead>
                                        <tbody>
                                        @forelse($row['categories'] as $cat)
                                            <tr class="{{ strtoupper($cat['name']) === 'ADVANCE DISBURSEMENT AMOUNT' ? 'row-advance' : '' }}">
                                                <td>{{ $cat['name'] }}</td>
                                                <td class="text-end">₹{{ number_format($cat['total'], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="text-muted">No category totals.</td></tr>
                                        @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th>Advance Disbursement</th>
                                                <th class="text-end">₹{{ number_format($row['advance'], 2) }}</th>
                                            </tr>
                                            <tr>
                                                <th>Total Expenses (excl. advance)</th>
                                                <th class="text-end">₹{{ number_format($row['expenses'], 2) }}</th>
                                            </tr>
                                            <tr>
                                                <th>Remaining Amount</th>
                                                <th class="text-end">
                                                    <span class="ex-pill {{ $remainClass }}">
                                                        {{ $isPlus ? '+' : '−' }} ₹{{ number_format(abs($row['remaining']), 2) }}
                                                    </span>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="ex-panel">
        <div class="ex-panel-head">
            <h5>Expense history</h5>
            <span class="small text-muted">{{ $expenses->total() }} record(s)</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Entity</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Amount</th>
                    <th>Payment</th>
                    <th>Receipt</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($expenses as $expense)
                    @php
                        $isAdvance = $expense->isAdvanceDisbursement();
                        $status = $expense->status ?: 'Pending';
                        $badge = match($status) {
                            'Approved' => 'badge-approved',
                            'Rejected' => 'badge-rejected',
                            default => 'badge-pending',
                        };
                    @endphp
                    <tr class="{{ $isAdvance ? 'row-advance' : '' }}">
                        <td>
                            <div class="fw-semibold" style="color:var(--ink);">{{ $expense->entityName() }}</div>
                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit(strip_tags((string)$expense->description), 40) }}</div>
                            @if($isAdvance)
                                <div class="small" style="color:#b91c1c;font-weight:600;">Advance — not deletable</div>
                            @endif
                        </td>
                        <td>{{ optional($expense->expense_date)->format('d M Y') }}</td>
                        <td>{{ $expense->category?->name ?? '—' }}</td>
                        <td class="fw-bold">₹{{ number_format((float)$expense->amount, 2) }}</td>
                        <td>
                            <div>{{ $expense->payment_method ?: '—' }}</div>
                            <div class="small text-muted">{{ $expense->reference_id ?: '' }}</div>
                        </td>
                        <td>
                            @if($expense->receiptUrl())
                                <a href="{{ $expense->receiptUrl() }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                            @else
                                —
                            @endif
                        </td>
                        <td><span class="ex-pill {{ $badge }}">{{ $status }}</span></td>
                        <td class="text-end text-nowrap">
                            @if($status === 'Pending')
                                <form method="POST" action="{{ route('expenses.status', $expense) }}" class="d-inline">
                                    @csrf @method('PATCH')
                                    @foreach($qs as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                                    <button name="status" value="Approved" class="btn btn-sm btn-success">Approve</button>
                                    <button name="status" value="Rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                </form>
                            @endif
                            <button type="button" class="btn btn-sm btn-outline-secondary edit-expense-btn"
                                data-bs-toggle="modal" data-bs-target="#expenseModal"
                                data-url="{{ route('expenses.admin.update', $expense) }}"
                                data-employee="{{ $expense->employee_id }}"
                                data-company="{{ $expense->company_id }}"
                                data-category="{{ $expense->category_id }}"
                                data-date="{{ optional($expense->expense_date)->format('Y-m-d') }}"
                                data-amount="{{ $expense->amount }}"
                                data-description="{{ e($expense->description) }}"
                                data-payment="{{ $expense->payment_method }}"
                                data-reference="{{ $expense->reference_id }}">
                                Edit
                            </button>
                            @unless($isAdvance)
                                <form method="POST" action="{{ route('expenses.admin.destroy', $expense) }}" class="d-inline"
                                      onsubmit="return confirm('Delete this expense?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">No expenses found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $expenses->links() }}</div>
    </div>
</div>

{{-- Add / Edit expense --}}
<div class="modal fade" id="expenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" id="expenseForm" action="{{ route('expenses.admin.store') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_method" id="expenseMethod" value="POST">
                @foreach($qs as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="expenseModalTitle">Add Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body ex-wrap">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Employee</label>
                            <x-employee-select
                                name="employee_id"
                                id="expenseEmployee"
                                :employees="$employees"
                                placeholder="— None — (search…)"
                            />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <select name="company_id" id="expenseCompany" class="form-select">
                                <option value="">— None —</option>
                                @foreach($companies as $com)
                                    <option value="{{ $com->id }}">{{ $com->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="expenseCategory" class="form-select" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date</label>
                            <input type="date" name="expense_date" id="expenseDate" class="form-control" required value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" min="0" name="amount" id="expenseAmount" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment method</label>
                            <select name="payment_method" id="expensePayment" class="form-select">
                                <option value="">—</option>
                                @foreach($paymentMethods as $pm)
                                    <option value="{{ $pm }}">{{ $pm }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reference ID</label>
                            <input type="text" name="reference_id" id="expenseReference" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="expenseDescription" class="form-control js-rich-editor" rows="3"
                                      placeholder="Click to write expense description…"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Receipt</label>
                            <input type="file" name="receipt" class="form-control" accept="image/*,.pdf">
                        </div>
                    </div>
                    <div class="small text-muted mt-2">Select either an employee or a company (or both if needed by process).</div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn" id="expenseSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Categories --}}
<div class="modal fade" id="manageCategoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Manage categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ex-wrap">
                <form method="POST" action="{{ route('expenses.categories.store') }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-8"><input type="text" name="name" class="form-control" placeholder="New category name" required></div>
                    <div class="col-md-4"><button class="btn add-btn w-100">Add</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Name</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @foreach($categories as $cat)
                            @php $isAdvCat = $cat->isAdvanceDisbursement(); @endphp
                            <tr class="{{ $isAdvCat ? 'row-advance' : '' }}">
                                <td>
                                    @if($isAdvCat)
                                        <div class="fw-semibold">{{ $cat->name }}</div>
                                        <div class="small text-muted">System category — cannot rename or delete</div>
                                    @else
                                        <form method="POST" action="{{ route('expenses.categories.update', $cat) }}" class="d-flex gap-2">
                                            @csrf @method('PUT')
                                            <input type="text" name="name" class="form-control form-control-sm" value="{{ $cat->name }}" required>
                                            <button class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @unless($isAdvCat)
                                        <form method="POST" action="{{ route('expenses.categories.destroy', $cat) }}"
                                              onsubmit="return confirm('Delete category?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @else
                                        <span class="ex-pill badge-rejected">Protected</span>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Expense companies --}}
<div class="modal fade" id="manageCompaniesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Manage expense companies</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ex-wrap">
                <form method="POST" action="{{ route('expenses.companies.store') }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-md-8"><input type="text" name="name" class="form-control" placeholder="New company name" required></div>
                    <div class="col-md-4"><button class="btn add-btn w-100">Add</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Name</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                        @foreach($companies as $com)
                            <tr>
                                <td>
                                    <form method="POST" action="{{ route('expenses.companies.update', $com) }}" class="d-flex gap-2">
                                        @csrf @method('PUT')
                                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $com->name }}" required>
                                        <button class="btn btn-sm btn-outline-primary">Save</button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('expenses.companies.destroy', $com) }}"
                                          onsubmit="return confirm('Delete company?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Advance notice --}}
<div class="modal fade" id="advanceNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-circle-info me-1" style="color:#f59e0b;"></i> Notice — Advance balance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ex-wrap ex-notice-body">
                <p><strong>Formula:</strong> Remaining = Advance Disbursement − Expenses</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>Balance</th>
                            <th>Color</th>
                            <th>Meaning</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Minus (−)</td>
                            <td><span class="ex-pill ex-remain-bad">Red</span></td>
                            <td>Only expenses are being recorded, or spending is more than the advance received.</td>
                        </tr>
                        <tr>
                            <td>Plus (+)</td>
                            <td><span class="ex-pill ex-remain-ok">Green</span></td>
                            <td>They still hold the company’s advance amount with them.</td>
                        </tr>
                        <tr>
                            <td>0 / Settled</td>
                            <td><span class="ex-pill ex-remain-ok">Green</span></td>
                            <td>The company has returned / balanced the amount.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <p class="ex-note">
                    When only expenses are added → balance shows in red / minus.
                    When the company amount is added under <strong>ADVANCE DISBURSEMENT AMOUNT</strong> → balance turns green.
                    This rule applies to both employees and companies. Advance Disbursement records cannot be deleted.
                </p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Share via official email + PDF attachment --}}
<div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('expenses.admin.share') }}" id="shareExpenseForm">
                @csrf
                @foreach(request()->only(['status','employee','company','month','week','from_date','to_date']) as $key => $value)
                    @if($value !== null && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-bold mb-0">Share expense PDF</h5>
                        <div class="small text-muted">Current filters will be applied to the attached PDF</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">From (official)</label>
                        @if(count($officialFrom))
                            <select name="from_email" class="form-select" required>
                                @foreach($officialFrom as $email)
                                    <option value="{{ $email }}" @selected(strtolower((string) ($docsFrom['email'] ?? '')) === strtolower($email))>{{ $email }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="email" name="from_email" class="form-control" value="{{ $docsFrom['email'] ?? '' }}" placeholder="Configure Email Settings first" required>
                            <div class="form-text text-danger">No official from emails found. Set them under Email Settings.</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <input type="email" name="to_email" class="form-control" placeholder="name@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CC <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="text" name="cc_emails" class="form-control" placeholder="cc1@example.com, cc2@example.com" value="{{ old('cc_emails') }}">
                        <div class="form-text">Separate multiple emails with commas</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" value="Expense report" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Message body</label>
                        <textarea name="body" class="form-control" rows="5" required>Dear Sir/Madam,

Please find the expense report PDF attached as per the selected filters.

Regards,
HR Team</textarea>
                    </div>
                    <div class="small text-muted">
                        <i class="fa-solid fa-paperclip me-1"></i>
                        Expense PDF (current filters) will be attached automatically. No page link will be sent.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn add-btn" id="shareSendBtn">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('expenseForm');
    const methodInput = document.getElementById('expenseMethod');
    const title = document.getElementById('expenseModalTitle');
    const modalEl = document.getElementById('expenseModal');

    function resetAddMode() {
        form.action = @json(route('expenses.admin.store'));
        methodInput.value = 'POST';
        title.textContent = 'Add Expense';
        form.reset();
        document.getElementById('expenseDate').value = @json(now()->toDateString());
    }

    modalEl.addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        if (!btn || !btn.classList.contains('edit-expense-btn')) {
            resetAddMode();
            return;
        }
        form.action = btn.dataset.url;
        methodInput.value = 'PUT';
        title.textContent = 'Edit Expense';
        document.getElementById('expenseEmployee').value = btn.dataset.employee || '';
        document.getElementById('expenseEmployee').dispatchEvent(new Event('change', { bubbles: true }));
        document.getElementById('expenseCompany').value = btn.dataset.company || '';
        document.getElementById('expenseCategory').value = btn.dataset.category || '';
        document.getElementById('expenseDate').value = btn.dataset.date || '';
        document.getElementById('expenseAmount').value = btn.dataset.amount || '';
        document.getElementById('expenseDescription').value = btn.dataset.description || '';
        document.getElementById('expensePayment').value = btn.dataset.payment || '';
        document.getElementById('expenseReference').value = btn.dataset.reference || '';
    });

    document.getElementById('shareExpenseForm')?.addEventListener('submit', function () {
        const btn = document.getElementById('shareSendBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending…';
        }
    });
});
</script>
@endpush
