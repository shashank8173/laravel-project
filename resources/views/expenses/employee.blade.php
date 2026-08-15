@extends('layouts.app')

@section('title', 'My Expenses')
@section('heading', 'My Expenses')

@section('page_actions')
@if(! empty($isAdmin))
<a href="{{ route('expenses.admin') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-clipboard-list me-1"></i> Manage expenses
</a>
@endif
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#submitExpenseModal">
    <i class="fa-solid fa-plus me-1"></i> Submit expense
</button>
@endsection

@push('styles')
<style>
    .ex-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44;
        --card:#fff; --panel-head:linear-gradient(180deg,#fff,#fafbfd); --input-bg:#fff; --table-head:#fafbfd;
        color:var(--ink);
    }

    .ex-wrap .ex-hero {
        border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem;
        background:
            radial-gradient(900px 220px at 0% 0%, rgba(255,155,68,.18), transparent 55%),
            var(--hero-grad, linear-gradient(135deg, #0f2744 0%, #16375f 55%, #1b466f 100%));
        color:#fff; padding:1.15rem 1.3rem;
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
    }
    .ex-wrap .ex-hero h2 { margin:0; font-size:1.25rem; font-weight:800; letter-spacing:-.02em; color:#fff; }
    .ex-wrap .ex-hero p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; color:#fff; }
    .ex-wrap .ex-hero .chip {
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
        border-radius:12px; padding:.55rem .85rem; font-size:.82rem; font-weight:650; color:#fff;
    }

    .ex-wrap .ex-metric {
        display:block; text-decoration:none; color:inherit; height:100%;
        border:1px solid var(--line); border-radius:16px; background:var(--card);
        padding:1rem 1.05rem; position:relative; overflow:hidden;
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    .ex-wrap .ex-metric:hover {
        transform:translateY(-2px); border-color:#ffd0a8;
        box-shadow:0 10px 24px var(--shadow, rgba(15,39,68,.08)); color:inherit;
    }
    .ex-wrap .ex-metric.is-active { border-color:#ffd0a8; box-shadow:0 8px 20px rgba(255,155,68,.12); }
    .ex-wrap .ex-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .ex-wrap .ex-metric.is-blue::before { background:#2563eb; }
    .ex-wrap .ex-metric.is-amber::before { background:#f59e0b; }
    .ex-wrap .ex-metric.is-green::before { background:#16a34a; }
    .ex-wrap .ex-metric.is-red::before { background:#ef4444; }
    .ex-wrap .ex-metric .top { display:flex; justify-content:space-between; align-items:flex-start; gap:.75rem; }
    .ex-wrap .ex-metric .ico {
        width:36px; height:36px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center;
        background:#fff7ed; color:#c2410c; flex:0 0 auto;
    }
    .ex-wrap .ex-metric.is-blue .ico { background:#eff6ff; color:#1d4ed8; }
    .ex-wrap .ex-metric.is-amber .ico { background:#fffbeb; color:#b45309; }
    .ex-wrap .ex-metric.is-green .ico { background:#f0fdf4; color:#15803d; }
    .ex-wrap .ex-metric.is-red .ico { background:#fef2f2; color:#b91c1c; }
    .ex-wrap .ex-metric .k {
        font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin:0;
    }
    .ex-wrap .ex-metric .v {
        font-size:1.45rem; font-weight:800; color:var(--ink); margin:.3rem 0 0; line-height:1.1;
    }
    .ex-wrap .ex-metric .hint { font-size:.75rem; color:var(--muted); margin:.3rem 0 0; }

    .ex-wrap .ex-panel {
        background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; color:var(--ink);
    }
    .ex-wrap .ex-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:var(--panel-head);
    }
    .ex-wrap .ex-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .ex-wrap .ex-panel-head .sub { display:block; font-size:.78rem; color:var(--muted); margin-top:.15rem; }
    .ex-wrap .ex-panel-body { padding:1.1rem 1.15rem; background:var(--card); }

    .ex-wrap .form-label { font-size:.8rem; font-weight:650; color:var(--muted); margin-bottom:.3rem; }
    .ex-wrap .form-control, .ex-wrap .form-select {
        border-radius:11px; border-color:var(--line); padding:.55rem .75rem; color:var(--ink); background:var(--input-bg);
    }
    .ex-wrap .form-control:focus, .ex-wrap .form-select:focus {
        border-color:#ffd0a8; box-shadow:0 0 0 .2rem rgba(255,155,68,.15);
        background:var(--input-bg); color:var(--ink);
    }
    .ex-wrap .btn-submit {
        background:linear-gradient(145deg,#ff9b44,#f07a1a); border:0; color:#fff;
        font-weight:750; border-radius:12px; padding:.65rem 1.15rem;
        box-shadow:0 8px 18px rgba(255,155,68,.28);
    }
    .ex-wrap .btn-submit:hover { filter:brightness(1.03); color:#fff; }

    .ex-wrap .table { color:var(--ink); --bs-table-bg:transparent; --bs-table-color:var(--ink); --bs-table-border-color:var(--line); --bs-table-hover-bg:var(--soft); --bs-table-hover-color:var(--ink); }
    .ex-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .ex-wrap .table thead th {
        font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted);
        font-weight:700; border-bottom-color:var(--line); background:var(--table-head); white-space:nowrap;
    }
    .ex-wrap .table tbody tr:last-child td { border-bottom:0; }
    .ex-wrap .table td { border-color:var(--line); color:var(--ink); font-size:.9rem; background:transparent; }

    .ex-wrap .date-chip {
        display:inline-flex; flex-direction:column; align-items:center; justify-content:center;
        width:46px; height:46px; border-radius:11px; background:var(--soft); color:var(--ink);
        border:1px solid var(--line); flex-shrink:0;
    }
    .ex-wrap .date-chip .m { font-size:.62rem; font-weight:800; text-transform:uppercase; line-height:1; }
    .ex-wrap .date-chip .d { font-size:1.05rem; font-weight:800; line-height:1.1; margin-top:.05rem; }
    .ex-wrap .cat-name { font-weight:750; color:var(--ink); margin:0; }
    .ex-wrap .desc { color:var(--muted); font-size:.8rem; margin:.15rem 0 0; max-width:280px; }
    .ex-wrap .amt {
        font-weight:800; color:var(--ink); font-variant-numeric:tabular-nums; white-space:nowrap;
    }
    .ex-wrap .pill {
        display:inline-flex; align-items:center; padding:.22rem .55rem; border-radius:999px;
        font-size:.72rem; font-weight:700;
    }
    .ex-wrap .pill-pending { background:#fef3c7; color:#b45309; }
    .ex-wrap .pill-ok { background:#dcfce7; color:#15803d; }
    .ex-wrap .pill-bad { background:#fee2e2; color:#b91c1c; }
    .ex-wrap .empty { color:var(--muted); text-align:center; padding:2rem .5rem; font-size:.9rem; }
    .ex-wrap .btn-ghost {
        border:1px solid var(--line); color:var(--ink); background:var(--card); font-weight:650;
        border-radius:10px; font-size:.8rem; padding:.35rem .75rem; text-decoration:none;
    }
    .ex-wrap .btn-ghost:hover { border-color:#ffd0a8; background:var(--soft); color:var(--ink); }
    .ex-wrap .receipt-link {
        font-size:.8rem; font-weight:700; color:#0b5cab; text-decoration:none;
    }
    .ex-wrap .receipt-link:hover { text-decoration:underline; }
    .ex-wrap .filter-bar {
        display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; margin-bottom:0;
    }

    #submitExpenseModal .modal-content { border:0; border-radius:18px; overflow:hidden; background:var(--card, #fff); color:var(--ink, #0f2744); }
    #submitExpenseModal .modal-header {
        background:linear-gradient(135deg,#0f2744,#1b466f); color:#fff; border:0; padding:1rem 1.2rem;
    }
    #submitExpenseModal .modal-header .btn-close { filter:invert(1); }
    #submitExpenseModal .modal-title { font-weight:800; font-size:1.05rem; }
    #submitExpenseModal .modal-body { padding:1.2rem; background:var(--card, #fff); color:var(--ink, #0f2744); }
    #submitExpenseModal .modal-footer { border-top:1px solid var(--line, #e8eef5); padding:1rem 1.2rem; background:var(--card, #fff); }

    html[data-theme="dark"] .ex-wrap {
        --ink:#e8eef8; --muted:#a8b6cc; --line:#243044; --soft:#1a2232; --card:#141b27;
        --panel-head:linear-gradient(180deg,#171e2c,#141b27); --input-bg:#0f1520; --table-head:#171e2c;
    }
    html[data-theme="dark-blue"] .ex-wrap {
        --ink:#eaf2ff; --muted:#9db4d4; --line:#1a3358; --soft:#102240; --card:#0c1a31;
        --panel-head:linear-gradient(180deg,#0e1f3c,#0c1a31); --input-bg:#081528; --table-head:#0e1f3c;
    }
    html[data-theme="light"] .ex-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --card:#ffffff;
        --panel-head:linear-gradient(180deg,#fff,#fafbfd); --input-bg:#ffffff; --table-head:#fafbfd;
    }
    html[data-theme="dark"] .ex-wrap .receipt-link,
    html[data-theme="dark-blue"] .ex-wrap .receipt-link { color:#7dd3fc; }
</style>
@endpush

@section('content')
@php
    $parseDate = function ($value) {
        try {
            return $value ? \Carbon\Carbon::parse($value) : null;
        } catch (\Throwable) {
            return null;
        }
    };
    $openSubmitModal = $errors->any() || old('category_id') || old('amount') || old('description');
@endphp
<div class="ex-wrap">
    <div class="ex-hero">
        <div>
            <h2>My expenses</h2>
            <p>Submit claims with receipts and track approval status.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="chip">
                <i class="fa-solid fa-indian-rupee-sign me-1"></i>
                Approved ₹{{ number_format($stats['approved_amount'] ?? 0, 0) }}
            </span>
            <button type="button" class="btn add-btn btn-sm" data-bs-toggle="modal" data-bs-target="#submitExpenseModal">
                <i class="fa-solid fa-plus me-1"></i> Submit expense
            </button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <a href="{{ route('expenses.mine') }}" class="ex-metric {{ $status === null || $status === '' ? 'is-active' : '' }} is-blue">
                <div class="top">
                    <div>
                        <p class="k">Total</p>
                        <p class="v">{{ $stats['total'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-receipt"></i></span>
                </div>
                <p class="hint">₹{{ number_format($stats['amount'] ?? 0, 0) }} claimed</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('expenses.mine', ['status' => 'Pending']) }}" class="ex-metric {{ $status === 'Pending' ? 'is-active' : '' }} is-amber">
                <div class="top">
                    <div>
                        <p class="k">Pending</p>
                        <p class="v">{{ $stats['pending'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-hourglass-half"></i></span>
                </div>
                <p class="hint">Awaiting review</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('expenses.mine', ['status' => 'Approved']) }}" class="ex-metric {{ $status === 'Approved' ? 'is-active' : '' }} is-green">
                <div class="top">
                    <div>
                        <p class="k">Approved</p>
                        <p class="v">{{ $stats['approved'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-circle-check"></i></span>
                </div>
                <p class="hint">Ready for payout</p>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('expenses.mine', ['status' => 'Rejected']) }}" class="ex-metric {{ $status === 'Rejected' ? 'is-active' : '' }} is-red">
                <div class="top">
                    <div>
                        <p class="k">Rejected</p>
                        <p class="v">{{ $stats['rejected'] }}</p>
                    </div>
                    <span class="ico"><i class="fa-solid fa-circle-xmark"></i></span>
                </div>
                <p class="hint">Not approved</p>
            </a>
        </div>
    </div>

    <div class="ex-panel">
        <div class="ex-panel-head">
            <div>
                <h5><i class="fa-solid fa-list me-1" style="color:var(--accent)"></i> My claims</h5>
                <span class="sub">{{ $expenses->total() }} record(s){{ $status ? ' · '.$status : '' }}</span>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <form method="GET" action="{{ route('expenses.mine') }}" class="filter-bar">
                    <select name="status" class="form-select form-select-sm" style="width:auto;min-width:140px;" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach(['Pending', 'Approved', 'Rejected'] as $s)
                            <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @if($status)
                        <a href="{{ route('expenses.mine') }}" class="btn btn-ghost">Clear</a>
                    @endif
                </form>
                <button type="button" class="btn add-btn btn-sm" data-bs-toggle="modal" data-bs-target="#submitExpenseModal">
                    <i class="fa-solid fa-plus me-1"></i> Submit
                </button>
            </div>
        </div>
        <div class="ex-panel-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-3">Date</th>
                            <th>Category</th>
                            <th>Amount</th>
                            <th class="text-center">Status</th>
                            <th class="pe-3 text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($expenses as $expense)
                        @php
                            $date = $parseDate($expense->expense_date);
                            $st = strtolower((string) $expense->status);
                            $pill = match ($st) {
                                'approved' => 'pill-ok',
                                'rejected' => 'pill-bad',
                                default => 'pill-pending',
                            };
                            $receipt = $expense->receiptUrl();
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="date-chip">
                                        <span class="m">{{ $date ? $date->format('M') : '—' }}</span>
                                        <span class="d">{{ $date ? $date->format('d') : '?' }}</span>
                                    </span>
                                    <div>
                                        <div class="cat-name" style="font-size:.88rem;">{{ $date ? $date->format('D, d M Y') : '—' }}</div>
                                        <div class="desc">{{ $date ? $date->diffForHumans() : '' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="cat-name">{{ $expense->category?->name ?? '—' }}</p>
                                @if($expense->description)
                                    <div class="desc" title="{{ strip_tags((string) $expense->description) }}">{{ \Illuminate\Support\Str::limit(strip_tags((string) $expense->description), 56) }}</div>
                                @endif
                            </td>
                            <td><span class="amt">₹{{ number_format((float) $expense->amount, 2) }}</span></td>
                            <td class="text-center">
                                <span class="pill {{ $pill }}">{{ $expense->status }}</span>
                            </td>
                            <td class="pe-3 text-end">
                                @if($receipt)
                                    <a href="{{ $receipt }}" target="_blank" rel="noopener" class="receipt-link">
                                        <i class="fa-solid fa-paperclip me-1"></i>View
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">
                                No expenses yet.
                                <button type="button" class="btn btn-link p-0 align-baseline" data-bs-toggle="modal" data-bs-target="#submitExpenseModal">
                                    Submit your first claim
                                </button>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($expenses->hasPages())
                <div class="p-3 border-top">{{ $expenses->links() }}</div>
            @endif
        </div>
    </div>
</div>

{{-- Submit expense modal --}}
<div class="modal fade" id="submitExpenseModal" tabindex="-1" aria-labelledby="submitExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" id="expenseForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="submitExpenseModalLabel">
                        <i class="fa-solid fa-plus me-1"></i> Submit expense
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ex-wrap">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="" disabled @selected(! old('category_id'))>Select category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Expense date</label>
                            <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Amount (₹)</label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control js-rich-editor" rows="3"
                                  placeholder="Click to describe this expense…">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Receipt <span class="text-muted">(optional)</span></label>
                        <input type="file" name="receipt" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp,.gif">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-submit">
                        <i class="fa-solid fa-paper-plane me-1"></i> Submit claim
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    @if($openSubmitModal)
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('submitExpenseModal');
        if (el && window.bootstrap?.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(el).show();
        }
    });
    @endif
})();
</script>
@endpush
