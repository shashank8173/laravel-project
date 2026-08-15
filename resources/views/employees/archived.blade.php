@extends('layouts.app')

@section('title', 'Former Employees')
@section('heading', 'Former / Archived Employees')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pay-slip.css') }}">
<style>
    .arch-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .arch-wrap .arch-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .arch-wrap .arch-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .arch-wrap .arch-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .arch-wrap .arch-panel-body { padding:1rem 1.15rem; }
    .arch-wrap .arch-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .arch-wrap .arch-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .arch-wrap .arch-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .arch-wrap .arch-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .arch-wrap .avatar {
        width:42px; height:42px; border-radius:12px; object-fit:cover; border:1px solid var(--line);
    }
    .arch-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .arch-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .arch-wrap .name { font-weight:750; color:var(--ink); margin:0; }
    .arch-wrap .sub { font-size:.8rem; color:var(--muted); }

    #archDetailModal {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44;
    }
    #archDetailModal .modal-content { border:0; border-radius:18px; overflow:hidden; }
    #archDetailModal .modal-header {
        border:0; background:linear-gradient(135deg,#fff8f2,#fff); padding:1.1rem 1.25rem;
    }
    #archDetailModal .arch-tabs {
        display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem;
        padding:.35rem; background:var(--soft); border:1px solid var(--line); border-radius:14px;
    }
    #archDetailModal .arch-tab-btn {
        border:1px solid transparent; background:transparent; color:var(--muted);
        font-weight:700; padding:.55rem 1rem; border-radius:10px; cursor:pointer;
    }
    #archDetailModal .arch-tab-btn.active {
        background:#fff; color:#c2410c; border-color:#ffd7b0;
        box-shadow:0 2px 8px rgba(15,39,68,.06);
    }
    #archDetailModal .arch-panel-tab { display:none; }
    #archDetailModal .arch-panel-tab.active { display:block; }
    #archDetailModal .detail-grid {
        display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.75rem 1rem;
    }
    @media (max-width:767px) {
        #archDetailModal .detail-grid { grid-template-columns:1fr; }
    }
    #archDetailModal .detail-item {
        border:1px solid var(--line); border-radius:12px; padding:.7rem .85rem; background:#fafbfd;
    }
    #archDetailModal .detail-item .k {
        display:block; font-size:.68rem; text-transform:uppercase; letter-spacing:.04em;
        color:var(--muted); font-weight:700; margin-bottom:.15rem;
    }
    #archDetailModal .detail-item .v { color:var(--ink); font-weight:650; word-break:break-word; }
    #archDetailModal .hero-photo {
        width:120px; height:120px; border-radius:18px; object-fit:cover;
        border:3px solid #fff; box-shadow:0 8px 20px rgba(15,39,68,.12);
    }
    #archDetailModal .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    #archPayslipMount { max-height:65vh; overflow:auto; background:#f4f7fb; padding:1rem; border-radius:12px; }
    @media print {
        body * { visibility:hidden !important; }
        #archPayslipMount, #archPayslipMount * { visibility:visible !important; }
        #archPayslipMount { position:absolute; left:0; top:0; width:100%; max-height:none; background:#fff; padding:0; }
    }
</style>
@endpush

@section('content')
@php $total = method_exists($employees, 'total') ? $employees->total() : $employees->count(); @endphp
<div class="arch-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="arch-metric">
                <div class="k">Former employees</div>
                <p class="v">{{ $total }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="arch-metric">
                <div class="k">Showing</div>
                <p class="v">{{ $employees->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="arch-metric">
                <div class="k">On this page</div>
                <p class="v" style="font-size:1rem;padding-top:.4rem;">Details + salary slip</p>
            </div>
        </div>
    </div>

    <div class="arch-panel mb-3">
        <div class="arch-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search</h5>
        </div>
        <div class="arch-panel-body">
            <form method="GET" action="{{ route('employees.archived') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / email / emp ID / mobile</label>
                    <input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="Search former employees...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('employees.archived') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="arch-panel">
        <div class="arch-panel-head">
            <h5>Former employees</h5>
            <span class="small text-muted">{{ $total }} record(s)</span>
        </div>
        <div class="arch-panel-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:56px;"></th>
                        <th>Employee</th>
                        <th>Contact</th>
                        <th>Department</th>
                        <th>Joined</th>
                        <th>Left</th>
                        <th>Salary</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>
                                <img src="{{ $employee->profile_image_url }}" alt="" class="avatar">
                            </td>
                            <td>
                                <p class="name">{{ $employee->full_name }}</p>
                                <div class="sub">{{ $employee->emp_id ?: 'ID '.$employee->id }} · {{ $employee->designation?->name ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $employee->email ?: '—' }}</div>
                                <div class="sub">{{ $employee->mobile1 ?: '—' }}</div>
                            </td>
                            <td>{{ $employee->department?->name ?? '—' }}</td>
                            <td>{{ optional($employee->doj)->format('d M Y') ?? '—' }}</td>
                            <td>{{ optional($employee->created_at)->format('d M Y') ?? '—' }}</td>
                            <td>₹{{ number_format((float) ($employee->salary ?? 0), 0) }}</td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary arch-open"
                                    data-url="{{ route('employees.archived.details', $employee) }}"
                                    data-tab="details">
                                    <i class="fa-regular fa-eye"></i> Details
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning arch-open"
                                    data-url="{{ route('employees.archived.details', $employee) }}"
                                    data-tab="salary">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> Salary
                                </button>
                                <form method="POST" action="{{ route('employees.archived.restore', $employee) }}" class="d-inline"
                                      onsubmit="return confirm('Restore this employee to active list?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">Restore</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted py-4 text-center">No archived employees.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $employees->links() }}</div>
        </div>
    </div>
</div>

{{-- Detail + Salary modal --}}
<div class="modal fade" id="archDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-3">
                    <img id="archImg" src="" alt="" class="hero-photo">
                    <div>
                        <h5 class="modal-title fw-bold mb-0" id="archName">—</h5>
                        <div class="text-muted small" id="archRole">—</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="arch-tabs" role="tablist">
                    <button type="button" class="arch-tab-btn active" data-arch-tab="details">
                        <i class="fa-regular fa-user me-1"></i> Full details
                    </button>
                    <button type="button" class="arch-tab-btn" data-arch-tab="salary">
                        <i class="fa-solid fa-file-invoice-dollar me-1"></i> Salary and slip
                    </button>
                </div>

                <div class="arch-panel-tab active" id="archTabDetails" data-arch-panel="details">
                    <div id="archDetailsGrid" class="detail-grid"></div>
                </div>

                <div class="arch-panel-tab" id="archTabSalary" data-arch-panel="salary">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Gross salary (₹)</label>
                            <div class="input-group">
                                <input type="number" min="0" step="0.01" id="archSalaryInput" class="form-control">
                                <button type="button" class="btn add-btn" id="archSalarySave">Save</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Month</label>
                            <select id="archMonth" class="form-select">
                                @for($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" @selected($m === (int) now()->month)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Year</label>
                            <select id="archYear" class="form-select">
                                @for($y = (int) now()->year; $y >= (int) now()->year - 8; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary w-100" id="archGenSlip">
                                Generate
                            </button>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="archPrintSlip" disabled>
                            <i class="fa-solid fa-print"></i> Print
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="archDownloadSlip" disabled>
                            <i class="fa-solid fa-download"></i> Download PDF
                        </button>
                        <span class="small text-muted align-self-center" id="archSlipHint">Pick month/year and generate slip.</span>
                    </div>
                    <div id="archPayslipMount">
                        <div class="text-muted text-center py-5">Salary slip preview will appear here.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <form method="POST" id="archRestoreForm" action="#" class="me-auto" onsubmit="return confirm('Restore this employee?')">
                    @csrf
                    <button class="btn btn-outline-success">Restore employee</button>
                </form>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script>
(function () {
    const csrf = @json(csrf_token());
    let current = null;
    const modalEl = document.getElementById('archDetailModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    const fields = [
        ['emp_id', 'Employee ID'],
        ['email', 'Email'],
        ['office_email', 'Office email'],
        ['mobile1', 'Mobile'],
        ['mobile2', 'Mobile 2'],
        ['fathers_name', "Father's name"],
        ['dob', 'Date of birth'],
        ['doj', 'Date of joining'],
        ['archived_on', 'Last / archived date'],
        ['department', 'Department'],
        ['designation', 'Designation'],
        ['job_title', 'Job title'],
        ['employee_type', 'Employee type'],
        ['work_location', 'Work location'],
        ['salary', 'Salary', (v) => '₹' + Number(v || 0).toLocaleString('en-IN')],
        ['gender', 'Gender'],
        ['bgroup', 'Blood group'],
        ['marital_status', 'Marital status'],
        ['marriage_anniversary', 'Marriage anniversary'],
        ['experience', 'Experience'],
        ['probation_period', 'Probation (months)'],
        ['probation_status', 'Probation status'],
        ['religion', 'Religion'],
        ['nationality', 'Nationality'],
        ['attendance_id', 'Attendance ID'],
        ['current_address', 'Current address'],
        ['permanent_address', 'Permanent address'],
        ['pincode', 'Pincode'],
        ['city_id', 'City ID'],
        ['state_id', 'State ID'],
        ['house_type', 'House type'],
        ['staying_current_residence', 'Staying current residence'],
        ['living_current_city', 'Living in current city'],
        ['bank_name', 'Bank'],
        ['account_number', 'Account no.'],
        ['ifsc', 'IFSC'],
        ['status_label', 'Status'],
        ['other_detail', 'Other details'],
        ['added_date', 'Added date'],
        ['update_date', 'Update date'],
    ];

    function val(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v);
    }

    function renderDetails(data) {
        const grid = document.getElementById('archDetailsGrid');
        grid.innerHTML = fields.map(([key, label, fmt]) => {
            const raw = data[key];
            const display = fmt ? fmt(raw) : val(raw);
            return `<div class="detail-item"><span class="k">${label}</span><span class="v">${display}</span></div>`;
        }).join('');
    }

    function resetSlip() {
        document.getElementById('archPayslipMount').innerHTML =
            '<div class="text-muted text-center py-5">Salary slip preview will appear here.</div>';
        document.getElementById('archPrintSlip').disabled = true;
        document.getElementById('archDownloadSlip').disabled = true;
        document.getElementById('archSlipHint').textContent = 'Pick month/year and generate slip.';
    }

    function switchArchTab(name) {
        document.querySelectorAll('#archDetailModal .arch-tab-btn').forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.archTab === name);
        });
        document.querySelectorAll('#archDetailModal .arch-panel-tab').forEach((panel) => {
            panel.classList.toggle('active', panel.dataset.archPanel === name);
        });
    }

    document.querySelectorAll('#archDetailModal .arch-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => switchArchTab(btn.dataset.archTab));
    });

    document.querySelectorAll('.arch-open').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = btn.dataset.url;
            btn.disabled = true;
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) throw new Error('Failed to load');
                current = await res.json();
                document.getElementById('archImg').src = current.image;
                document.getElementById('archName').textContent = current.full_name || '—';
                document.getElementById('archRole').textContent =
                    [current.designation, current.department, current.emp_id || ('ID ' + current.id)]
                        .filter(Boolean).join(' · ');
                document.getElementById('archSalaryInput').value = current.salary || 0;
                document.getElementById('archRestoreForm').action = current.restore_url;
                renderDetails(current);
                resetSlip();
                applyAttendancePeriod(current.attendance_months || []);
                switchArchTab(btn.dataset.tab || 'details');
                modal.show();
            } catch (e) {
                alert('Could not load employee details.');
            } finally {
                btn.disabled = false;
            }
        });
    });

    document.getElementById('archSalarySave')?.addEventListener('click', async () => {
        if (!current) return;
        const salary = document.getElementById('archSalaryInput').value;
        const res = await fetch(current.salary_url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ salary }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            alert(data.message || 'Could not update salary.');
            return;
        }
        current.salary = data.salary;
        renderDetails(current);
        document.getElementById('archSlipHint').textContent = 'Salary saved. Re-generate slip if needed.';
    });

    function applyAttendancePeriod(months) {
        const monthEl = document.getElementById('archMonth');
        const yearEl = document.getElementById('archYear');
        if (!months.length) {
            document.getElementById('archSlipHint').textContent =
                'No attendance found for this employee. Slip will use 0 present days for selected month.';
            return;
        }

        // Ensure years from attendance exist in dropdown
        const years = [...new Set(months.map((r) => r.year))];
        years.forEach((y) => {
            if (![...yearEl.options].some((o) => Number(o.value) === y)) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                yearEl.appendChild(opt);
            }
        });

        const latest = months[0];
        yearEl.value = String(latest.year);
        monthEl.value = String(latest.month);
        document.getElementById('archSlipHint').textContent =
            `Latest attendance: ${latest.month}/${latest.year} (${latest.count} punches). Change month/year then Generate.`;
    }

    document.getElementById('archGenSlip')?.addEventListener('click', async () => {
        if (!current) return;
        const month = document.getElementById('archMonth').value;
        const year = document.getElementById('archYear').value;
        const salary = document.getElementById('archSalaryInput').value || 0;
        const mount = document.getElementById('archPayslipMount');
        mount.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin"></i> Generating from real attendance…</div>';
        document.getElementById('archSlipHint').textContent =
            `Loading attendance for ${month}/${year}…`;

        const url = current.payslip_url
            + '?month=' + encodeURIComponent(month)
            + '&year=' + encodeURIComponent(year)
            + '&salary=' + encodeURIComponent(salary);
        try {
            const res = await fetch(url, { headers: { 'Accept': 'text/html' } });
            if (!res.ok) throw new Error('fail');
            mount.innerHTML = await res.text();
            document.getElementById('archPrintSlip').disabled = false;
            document.getElementById('archDownloadSlip').disabled = false;

            const months = current.attendance_months || [];
            const hit = months.find((r) => String(r.month) === String(month) && String(r.year) === String(year));
            document.getElementById('archSlipHint').textContent = hit
                ? `Slip ready for ${month}/${year} · ${hit.count} punch day(s) from attendance.`
                : `Slip ready for ${month}/${year} · no punches in attendance for this period.`;
        } catch (e) {
            mount.innerHTML = '<div class="alert alert-danger mb-0">Could not generate payslip for selected month/year.</div>';
            document.getElementById('archPrintSlip').disabled = true;
            document.getElementById('archDownloadSlip').disabled = true;
            document.getElementById('archSlipHint').textContent = 'Generation failed.';
        }
    });

    document.getElementById('archPrintSlip')?.addEventListener('click', () => window.print());

    document.getElementById('archDownloadSlip')?.addEventListener('click', async () => {
        const el = document.getElementById('salarySlip');
        if (!el || !window.html2canvas || !window.jspdf) {
            alert('PDF libraries not loaded.');
            return;
        }
        const canvas = await html2canvas(el, { scale: 2, useCORS: true });
        const img = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();
        const ratio = Math.min(pageWidth / canvas.width, pageHeight / canvas.height);
        const w = canvas.width * ratio;
        const h = canvas.height * ratio;
        pdf.addImage(img, 'PNG', (pageWidth - w) / 2, 8, w, h);
        const name = (current?.full_name || 'employee').replace(/\s+/g, '_');
        pdf.save('Salary_Slip_' + name + '.pdf');
    });
})();
</script>
@endpush
