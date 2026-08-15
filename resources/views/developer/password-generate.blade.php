@extends('layouts.app')

@section('title', 'Password Generate')
@section('heading', 'Employee Password Management')

@section('page_actions')
<button type="button" class="btn btn-outline-danger" id="resetSelected" disabled>
    <i class="fa-solid fa-key"></i> Reset selected
</button>
@endsection

@push('styles')
<style>
    .pg-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .pg-wrap .pg-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .pg-wrap .pg-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .pg-wrap .pg-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .pg-wrap .pg-panel-body { padding:1rem 1.15rem; }
    .pg-wrap .pg-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .pg-wrap .pg-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .pg-wrap .pg-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .pg-wrap .pg-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .pg-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .pg-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .pg-wrap .pwd-group { display:flex; gap:.35rem; align-items:center; min-width:180px; }
    .pg-wrap .pwd-group .form-control {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        font-size:.82rem;
    }
    .pg-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .pg-wrap .sub { font-size:.8rem; color:var(--muted); }
    .pg-wrap .toast-new {
        position:fixed; right:1rem; bottom:1rem; z-index:1080; max-width:360px;
        background:#0f2744; color:#fff; border-radius:14px; padding:1rem 1.1rem;
        box-shadow:0 12px 30px rgba(15,39,68,.25); display:none;
    }
    .pg-wrap .toast-new.show { display:block; }
    .pg-wrap .toast-new code {
        display:block; margin-top:.45rem; padding:.45rem .6rem; border-radius:8px;
        background:rgba(255,255,255,.12); color:#ffd7b0; font-size:.95rem;
    }
</style>
@endpush

@section('content')
<div class="pg-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="pg-metric">
                <div class="k">Employees listed</div>
                <p class="v">{{ $employees->total() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pg-metric">
                <div class="k">Selected</div>
                <p class="v" id="selectedCount">0</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="pg-metric">
                <div class="k">Access</div>
                <p class="v" style="font-size:1rem;padding-top:.4rem;">Developer · reset</p>
            </div>
        </div>
    </div>

    <div class="pg-panel mb-3">
        <div class="pg-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search employees</h5>
        </div>
        <div class="pg-panel-body">
            <form method="GET" action="{{ route('developer.passwords') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / email / mobile / emp ID</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search…">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('developer.passwords') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="pg-panel">
        <div class="pg-panel-head">
            <h5>Password list</h5>
            <span class="small text-muted">Plaintext compatible with legacy login</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0" id="employeeTable">
                <thead>
                <tr>
                    <th style="width:44px;"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                    <th>Employee</th>
                    <th>Official Email</th>
                    <th>Mobile</th>
                    <th>Password</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($employees as $employee)
                    <tr data-id="{{ $employee->id }}">
                        <td>
                            <input type="checkbox" class="form-check-input selectEmp" value="{{ $employee->id }}">
                        </td>
                        <td>
                            <p class="name">{{ $employee->full_name }}</p>
                            <div class="sub">{{ $employee->emp_id ?: 'ID '.$employee->id }}</div>
                        </td>
                        <td>{{ $employee->office_email ?: '—' }}</td>
                        <td>{{ $employee->mobile1 ?: '—' }}</td>
                        <td>
                            <div class="pwd-group">
                                @php $plain = $employee->plainPassword(); @endphp
                                <input type="password" class="form-control form-control-sm password-field"
                                       value="{{ $plain ?? '' }}"
                                       placeholder="{{ $plain === null ? 'Hashed — reset to view' : '' }}"
                                       readonly>
                                <button type="button" class="btn btn-sm btn-outline-secondary togglePassword" title="Show/hide">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-warning resetSingle"
                                    data-url="{{ route('developer.passwords.reset', $employee) }}">
                                Reset
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No employees found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-top">{{ $employees->links() }}</div>
    </div>

    <div class="toast-new" id="pwdToast">
        <div class="fw-bold" id="pwdToastTitle">Password updated</div>
        <code id="pwdToastValue"></code>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = @json(csrf_token());
    const bulkUrl = @json(route('developer.passwords.bulk'));
    const selectAll = document.getElementById('selectAll');
    const resetSelected = document.getElementById('resetSelected');
    const selectedCount = document.getElementById('selectedCount');
    const toast = document.getElementById('pwdToast');
    const toastTitle = document.getElementById('pwdToastTitle');
    const toastValue = document.getElementById('pwdToastValue');

    function refreshSelected() {
        const n = document.querySelectorAll('.selectEmp:checked').length;
        selectedCount.textContent = String(n);
        resetSelected.disabled = n === 0;
    }

    function showToast(title, password) {
        toastTitle.textContent = title;
        toastValue.textContent = password;
        toast.classList.add('show');
        clearTimeout(showToast._t);
        showToast._t = setTimeout(() => toast.classList.remove('show'), 6000);
    }

    selectAll?.addEventListener('change', function () {
        document.querySelectorAll('.selectEmp').forEach((el) => { el.checked = selectAll.checked; });
        refreshSelected();
    });

    document.querySelectorAll('.selectEmp').forEach((el) => {
        el.addEventListener('change', refreshSelected);
    });

    document.querySelectorAll('.togglePassword').forEach((btn) => {
        btn.addEventListener('click', function () {
            const field = this.closest('.pwd-group').querySelector('.password-field');
            field.type = field.type === 'password' ? 'text' : 'password';
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    });

    document.querySelectorAll('.resetSingle').forEach((btn) => {
        btn.addEventListener('click', async function () {
            if (!confirm('Reset password for this employee?')) return;
            btn.disabled = true;
            try {
                const res = await fetch(btn.dataset.url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Failed');
                const row = btn.closest('tr');
                const field = row.querySelector('.password-field');
                field.value = data.password;
                field.type = 'text';
                showToast('New password · ' + (data.name || ''), data.password);
            } catch (e) {
                alert('Could not reset password.');
            } finally {
                btn.disabled = false;
            }
        });
    });

    resetSelected?.addEventListener('click', async function () {
        const ids = [...document.querySelectorAll('.selectEmp:checked')].map((el) => Number(el.value));
        if (!ids.length) {
            alert('No employees selected');
            return;
        }
        if (!confirm('Reset passwords for ' + ids.length + ' selected employee(s)?')) return;

        resetSelected.disabled = true;
        try {
            const res = await fetch(bulkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ selected_ids: ids }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Failed');

            (data.updated || []).forEach((row) => {
                const tr = document.querySelector('tr[data-id="' + row.id + '"]');
                if (!tr) return;
                const field = tr.querySelector('.password-field');
                field.value = row.password;
                field.type = 'text';
            });

            showToast(data.message || 'Passwords updated', (data.updated || []).map((u) => u.password).join(' · '));
            document.querySelectorAll('.selectEmp:checked').forEach((el) => { el.checked = false; });
            if (selectAll) selectAll.checked = false;
            refreshSelected();
        } catch (e) {
            alert('Could not reset selected passwords.');
            refreshSelected();
        }
    });

    refreshSelected();
})();
</script>
@endpush
