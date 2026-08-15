@extends('layouts.app')

@section('title', 'Calculate Salary')
@section('heading', 'Calculate Salary')

@section('page_actions')
<a href="{{ route('attendance.all', array_filter(['employee_id' => optional($employee)->id, 'month' => $month, 'year' => $year])) }}" class="btn btn-outline-secondary me-2">Attendance Report</a>
<a href="{{ route('salary.index') }}" class="btn btn-outline-secondary">Salary Management</a>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/pay-slip.css') }}">
<style>
    .sal-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .sal-wrap .sal-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .sal-wrap .sal-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .sal-wrap .sal-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .sal-wrap .sal-panel-body { padding:1rem 1.15rem; }

    .sal-wrap .sal-filter {
        display:flex; flex-wrap:wrap; gap:.65rem; align-items:end;
    }
    .sal-wrap .sal-field { flex:1 1 160px; min-width:140px; }
    .sal-wrap .sal-field label {
        font-size:.75rem; font-weight:600; color:var(--muted); margin-bottom:.25rem; display:block;
    }
    .sal-wrap .sal-actions { flex:0 0 auto; }

    .sal-wrap .sal-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem;
        height:100%; position:relative; overflow:hidden;
        transition:transform .15s ease, box-shadow .15s ease;
    }
    .sal-wrap .sal-metric:hover { transform:translateY(-2px); box-shadow:0 8px 22px rgba(15,39,68,.07); }
    .sal-wrap .sal-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px;
    }
    .sal-wrap .sal-metric.holidays::before { background:#64748b; }
    .sal-wrap .sal-metric.working::before { background:#0ea5e9; }
    .sal-wrap .sal-metric.present::before { background:#16a34a; }
    .sal-wrap .sal-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; margin-bottom:.3rem;
    }
    .sal-wrap .sal-metric .v { font-size:1.6rem; font-weight:800; color:var(--ink); margin:0; line-height:1; }

    .sal-wrap .sal-chip-grid {
        display:grid; grid-template-columns:repeat(5, minmax(0,1fr)); gap:.65rem;
    }
    @media (max-width:991px) {
        .sal-wrap .sal-chip-grid { grid-template-columns:repeat(2, minmax(0,1fr)); }
    }
    .sal-wrap .sal-chip {
        background:var(--soft); border:1px solid var(--line); border-radius:12px; padding:.75rem .85rem;
    }
    .sal-wrap .sal-chip .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.03em; color:var(--muted); font-weight:700; }
    .sal-wrap .sal-chip .v { font-weight:750; color:var(--ink); margin:0; font-size:.95rem; }
    .sal-wrap .sal-chip.final { background:#fff7ed; border-color:#ffd7b0; }
    .sal-wrap .sal-chip.final .v { color:#c2410c; font-size:1.05rem; }

    .sal-wrap .sal-result {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        background:linear-gradient(135deg,#fff8f2,#fff); border:1px solid #ffe1c4;
        border-radius:14px; padding:1rem 1.15rem; margin-top:1rem;
    }
    .sal-wrap .sal-result .label { color:var(--muted); font-size:.85rem; font-weight:600; margin:0; }
    .sal-wrap .sal-result .amount { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; }

    .sal-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .sal-wrap .btn-sal {
        border-radius:10px; font-weight:650; display:inline-flex; align-items:center; gap:.4rem;
    }

    .sal-wrap .sal-panel-body:has(#salarySlip) { padding:1rem; background:var(--soft); }
    #salarySlip { box-shadow:0 10px 28px rgba(15,39,68,.06); }

    .sal-wrap .sal-upload-card {
        border:1px dashed #d5deea; border-radius:12px; background:var(--soft);
        padding:.85rem; height:100%;
    }
    .sal-wrap .sal-upload-card .preview {
        width:100%; height:88px; object-fit:contain; background:#fff;
        border:1px solid var(--line); border-radius:10px; margin-bottom:.65rem;
    }
    .sal-wrap .sal-upload-card .preview.sign { height:64px; }
    .sal-wrap .sal-upload-card .empty {
        height:88px; display:flex; align-items:center; justify-content:center;
        color:var(--muted); font-size:.8rem; background:#fff; border:1px solid var(--line);
        border-radius:10px; margin-bottom:.65rem;
    }
    .sal-wrap .sal-upload-card .empty.sign { height:64px; }

    @media (min-width:1200px) {
        .sal-wrap .sal-filter { flex-wrap:nowrap; }
        .sal-wrap .sal-field { flex:1 1 0; min-width:0; }
    }
</style>
@endpush

@section('content')
@php
    $payslip = $payslip ?? \App\Models\PayslipSetting::current();
    $logoUrl = $payslip->logoUrl();
    $signatureUrl = $payslip->signatureUrl();
@endphp
<div class="sal-wrap">
    <div class="sal-panel mb-3">
        <div class="sal-panel-head">
            <h5><i class="fa-solid fa-calculator me-1" style="color:var(--accent)"></i> Load salary calculation</h5>
        </div>
        <div class="sal-panel-body">
            <form method="GET" action="{{ route('salary.calculate') }}" class="sal-filter">
                <div class="sal-field" style="flex:1.6 1 220px;">
                    <label>Employee</label>
                    <x-employee-select
                        name="id"
                        :employees="$employees"
                        :selected="optional($employee)->id"
                        :required="true"
                    />
                </div>
                <div class="sal-field">
                    <label>Month</label>
                    <select name="month" class="form-select" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected((int)$month === $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="sal-field">
                    <label>Year</label>
                    <select name="year" class="form-select" required>
                        @for($y = now()->year - 5; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected((int)$year === $y)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="sal-actions">
                    <button class="btn add-btn btn-sal"><i class="fa-solid fa-bolt"></i> Load Calculation</button>
                </div>
            </form>
        </div>
    </div>

    <div class="sal-panel mb-3">
        <div class="sal-panel-head">
            <h5><i class="fa-solid fa-building me-1" style="color:var(--accent)"></i> Company details for salary slip</h5>
            <span class="small text-muted">Edit here — updates logo, address &amp; digital signature on slip</span>
        </div>
        <div class="sal-panel-body">
            <form method="POST" action="{{ route('salary.payslip-settings') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Company name</label>
                        <input type="text" name="company_name" class="form-control" required
                               value="{{ old('company_name', $payslip->company_name) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">CIN</label>
                        <input type="text" name="cin" class="form-control"
                               value="{{ old('cin', $payslip->cin) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Location (on slip)</label>
                        <input type="text" name="location" class="form-control"
                               value="{{ old('location', $payslip->location) }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Company address</label>
                        <textarea name="company_address" class="form-control" rows="3"
                                  placeholder="Each line appears separately on the slip">{{ old('company_address', $payslip->company_address) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Signatory name</label>
                        <input type="text" name="signatory_name" class="form-control"
                               value="{{ old('signatory_name', $payslip->signatory_name) }}">
                        <label class="form-label mt-2">Footer note</label>
                        <textarea name="footer_note" class="form-control" rows="2">{{ old('footer_note', $payslip->footer_note) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="sal-upload-card">
                            <label class="form-label mb-1">Company logo</label>
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo" class="preview" id="logoPreview">
                            @else
                                <div class="empty" id="logoPreviewEmpty">No logo uploaded</div>
                                <img src="" alt="" class="preview d-none" id="logoPreview">
                            @endif
                            <input type="file" name="logo" id="logoInput" class="form-control form-control-sm" accept="image/*">
                            @if($payslip->logo_path)
                                <div class="form-check mt-2 mb-0">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogo">
                                    <label class="form-check-label small" for="removeLogo">Remove current logo</label>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="sal-upload-card">
                            <label class="form-label mb-1">Digital signature</label>
                            @if($signatureUrl)
                                <img src="{{ $signatureUrl }}" alt="Signature" class="preview sign" id="signPreview">
                            @else
                                <div class="empty sign" id="signPreviewEmpty">No signature uploaded</div>
                                <img src="" alt="" class="preview sign d-none" id="signPreview">
                            @endif
                            <input type="file" name="signature" id="signInput" class="form-control form-control-sm" accept="image/*">
                            @if($payslip->signature_path)
                                <div class="form-check mt-2 mb-0">
                                    <input class="form-check-input" type="checkbox" name="remove_signature" value="1" id="removeSign">
                                    <label class="form-check-label small" for="removeSign">Remove current signature</label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button class="btn add-btn btn-sal"><i class="fa-solid fa-floppy-disk"></i> Save company details</button>
                </div>
            </form>
        </div>
    </div>

@if(! $employee || ! $calc)
    <div class="sal-panel">
        <div class="sal-panel-body text-center py-5">
            <div class="mb-2" style="font-size:2rem;opacity:.45;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <h5 class="fw-bold mb-1" style="color:var(--ink)">Select employee to begin</h5>
            <p class="text-muted mb-0">Choose employee, month and year to calculate salary and generate payslip.</p>
        </div>
    </div>
@else
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="sal-metric holidays">
                <div class="k">Total Holidays</div>
                <p class="v">{{ $calc['total_holidays'] }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sal-metric working">
                <div class="k">Working Days</div>
                <p class="v">{{ $calc['total_working_days'] }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sal-metric present">
                <div class="k">Present Days</div>
                <p class="v">{{ $calc['present_days'] }}</p>
            </div>
        </div>
    </div>

    <div class="sal-panel mb-3">
        <div class="sal-panel-head">
            <h5>Quick breakdown</h5>
            <span class="small text-muted">{{ $employee->full_name }} · {{ $calc['month_name'] }} {{ $year }}</span>
        </div>
        <div class="sal-panel-body">
            <div class="sal-chip-grid">
                <div class="sal-chip"><div class="k">Normal Late</div><p class="v">{{ $calc['total_late'] }}</p></div>
                <div class="sal-chip"><div class="k">Extra Late</div><p class="v">{{ $calc['late_extra'] }}</p></div>
                <div class="sal-chip"><div class="k">Half Days</div><p class="v">{{ $calc['half_day_all'] }}</p></div>
                <div class="sal-chip"><div class="k">Leaves</div><p class="v">{{ $calc['leave_days'] }}</p></div>
                <div class="sal-chip"><div class="k">Normal Fine</div><p class="v">₹{{ $calc['normal_fine'] }}</p></div>
                <div class="sal-chip"><div class="k">Extra Fine</div><p class="v">₹{{ $calc['extra_fine'] }}</p></div>
                <div class="sal-chip"><div class="k">Half Day Fine</div><p class="v">₹{{ number_format($calc['half_day_fine'], 2) }}</p></div>
                <div class="sal-chip"><div class="k">Per Day</div><p class="v">₹{{ number_format($calc['per_day_salary'], 2) }}</p></div>
                <div class="sal-chip"><div class="k">Gross Salary</div><p class="v">₹{{ number_format($calc['salary'], 2) }}</p></div>
                <div class="sal-chip final"><div class="k">Final Salary</div><p class="v">₹{{ number_format($calc['after_deduction'], 2) }}</p></div>
            </div>
        </div>
    </div>

    <div class="sal-panel mb-3">
        <div class="sal-panel-head">
            <h5>Adjust & recalculate</h5>
            <span class="small text-muted">Editable fields update payslip instantly</span>
        </div>
        <div class="sal-panel-body">
            <form id="salaryForm">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="salary">Salary</label>
                        <input type="number" class="form-control" id="salary" value="{{ $calc['salary'] }}" min="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="totalDays">Total Days</label>
                        <input type="number" class="form-control" id="totalDays" value="{{ $calc['total_working_days'] }}" min="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="presentDays">Present Days</label>
                        <input type="number" class="form-control" id="presentDays" value="{{ $calc['present_days'] }}" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="half_day_all">Half Day Allowance</label>
                        <input type="number" class="form-control" id="half_day_all" value="{{ $calc['half_day_all'] }}" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="normal_late">Normal Late</label>
                        <input type="number" class="form-control" id="normal_late" value="{{ $calc['normal_late'] }}" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="late_extra">Late Extra</label>
                        <input type="number" class="form-control" id="late_extra" value="{{ $calc['late_extra'] }}" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="normal_fine">Normal Fine</label>
                        <input type="number" class="form-control" id="normal_fine" value="{{ $calc['normal_fine'] }}" min="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="extra_fine">Extra Fine</label>
                        <input type="number" class="form-control" id="extra_fine" value="{{ $calc['extra_fine'] }}" min="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="half_day_fine">Half Day Fine</label>
                        <input type="number" class="form-control" id="half_day_fine" value="{{ $calc['half_day_fine'] }}" min="0" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="perDaySalary">Per Day Salary</label>
                        <input type="number" class="form-control" id="perDaySalary" value="{{ $calc['per_day_salary'] }}" min="0" readonly>
                    </div>
                    <input type="hidden" id="email" value="{{ $employee->officialEmail() }}">
                    <input type="hidden" id="employee_id" value="{{ $employee->id }}">
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <button type="button" class="btn add-btn btn-sal" id="calculateButton"><i class="fa-solid fa-rotate"></i> Recalculate</button>
                </div>
            </form>

            <div class="sal-result">
                <div>
                    <p class="label">Calculated salary after deductions</p>
                    <p class="amount">₹<span id="resultSalary">{{ number_format($calc['after_deduction'], 2) }}</span></p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" id="downloadSalarySlip" class="btn btn-outline-secondary btn-sal"><i class="fa-solid fa-download"></i> Download Slip</button>
                    <button type="button" id="submitButton" class="btn btn-info text-white btn-sal"><i class="fa-solid fa-paper-plane"></i> Generate & Send PDF</button>
                    <i id="loadingIcon" class="fas fa-spinner fa-spin align-self-center" style="display:none;"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="sal-panel mb-3">
        <div class="sal-panel-head">
            <h5>Payslip preview</h5>
            <span class="small text-muted">Printable / PDF ready</span>
        </div>
        <div class="sal-panel-body">
            <div id="salarySlip">
                <div class="slip-topbar"></div>

                <div class="salary-header">
                    <div class="salary-logo">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $payslip->company_name }}">
                        @endif
                    </div>
                    <div class="company-title">
                        <h1 class="company-name">{{ $payslip->company_name }}</h1>
                        <p class="company-address">
                            @foreach($payslip->addressLines() as $line)
                                {{ $line }}@if(! $loop->last)<br>@endif
                            @endforeach
                            @if($payslip->cin)
                                @if(count($payslip->addressLines()))<br>@endif
                                CIN: {{ $payslip->cin }}
                            @endif
                        </p>
                    </div>
                    <div class="slip-period-badge">
                        <span class="lbl">Pay period</span>
                        <span class="val">{{ $calc['month_name'] }} {{ $year }}</span>
                    </div>
                </div>

                <div class="salary-title">
                    Salary Slip · {{ $employee->full_name }}
                </div>

                <div class="employee-details">
                    <div class="normal-details">
                        <p class="slip-section-label">Employee details</p>
                        <div class="slip-row"><span class="k">Name</span><span class="v">{{ $employee->full_name }}</span></div>
                        <div class="slip-row"><span class="k">Designation</span><span class="v">{{ $employee->designation->name ?? '—' }}</span></div>
                        <div class="slip-row"><span class="k">Department</span><span class="v">{{ $employee->department->name ?? '—' }}</span></div>
                        <div class="slip-row"><span class="k">Location</span><span class="v">{{ $payslip->location ?: '—' }}</span></div>
                        <div class="slip-row"><span class="k">Work Days</span><span class="v">{{ $calc['total_working_days'] }}</span></div>
                        <div class="slip-row"><span class="k">LOP</span><span class="v"><span id="lop">{{ $calc['lop'] }}</span></span></div>
                    </div>
                    <div class="bank-details">
                        <p class="slip-section-label">Bank & ID</p>
                        <div class="slip-row"><span class="k">Employee ID</span><span class="v">{{ $employee->emp_id }}</span></div>
                        <div class="slip-row">
                            <span class="k">Date of joining</span>
                            <span class="v">{{ $employee->doj ? $employee->doj->format('d M Y') : '—' }}</span>
                        </div>
                        <div class="slip-row"><span class="k">Bank</span><span class="v">{{ $employee->bankDetail->bank_name ?? '—' }}</span></div>
                        <div class="slip-row"><span class="k">Account No.</span><span class="v">{{ $employee->bankDetail->account_number ?? '—' }}</span></div>
                        <div class="slip-row"><span class="k">IFSC</span><span class="v">{{ $employee->bankDetail->ifsc ?? '—' }}</span></div>
                    </div>
                </div>

                <div class="salary-structure">
                    <div class="left-side">
                        <div class="left-heading">
                            <div>Earnings</div>
                            <div>Amount</div>
                        </div>
                        <div class="earning-heading">
                            <div class="slip-line"><p class="name">Basic</p><p class="amt" id="basic">₹{{ number_format($calc['basic'], 2) }}</p></div>
                            <div class="slip-line"><p class="name">HRA</p><p class="amt" id="hra">₹{{ number_format($calc['hra'], 2) }}</p></div>
                            <div class="slip-line"><p class="name">Medical Allowance</p><p class="amt" id="medical_allowance">₹{{ number_format($calc['medical_allowance'], 2) }}</p></div>
                            <div class="slip-line"><p class="name">Conveyance Allowance</p><p class="amt" id="conveyance_allowance">₹{{ number_format($calc['conveyance_allowance'], 2) }}</p></div>
                            <div class="slip-line"><p class="name">Special Allowance</p><p class="amt" id="special_allowance">₹{{ number_format($calc['special_allowance'], 2) }}</p></div>
                        </div>
                        <div class="total-earning">
                            <div class="total_allowance">Total Earnings</div>
                            <div id="total_allowance" class="total_allowance">₹{{ number_format($calc['total_allowance'], 2) }}</div>
                        </div>
                        <div class="net_pay">
                            <div>Net Pay</div>
                            <div><span id="net-salary">₹{{ number_format($calc['net_pay'], 2) }}</span></div>
                        </div>
                    </div>
                    <div class="right-side">
                        <div class="right-heading">
                            <div>Deductions</div>
                            <div>Amount</div>
                        </div>
                        <div class="deduction-heading">
                            <div class="slip-line"><p class="name">Normal Late Fine</p><p class="amt" id="ded_normal_late">₹{{ number_format(($calc['normal_late'] ?? 0) * ($calc['normal_fine'] ?? 0), 2) }}</p></div>
                            <div class="slip-line"><p class="name">Extra Late Fine</p><p class="amt" id="ded_extra_late">₹{{ number_format(($calc['late_extra'] ?? 0) * ($calc['extra_fine'] ?? 0), 2) }}</p></div>
                            <div class="slip-line"><p class="name">Half Day Fine</p><p class="amt" id="ded_half_day">₹{{ number_format(($calc['half_day_all'] ?? 0) * ($calc['half_day_fine'] ?? 0), 2) }}</p></div>
                            <div class="slip-line"><p class="name">LOP Deduction</p><p class="amt" id="ded_lop">₹{{ number_format(max(0, ($calc['total_working_days'] ?? 0) - ($calc['present_days'] ?? 0)) * ($calc['per_day_salary'] ?? 0), 2) }}</p></div>
                            <div class="slip-line"><p class="name slip-empty">—</p><p class="amt slip-empty">—</p></div>
                        </div>
                        <div class="total-deduction">
                            <div>Total Deductions</div>
                            <div id="total-deduction">₹{{ number_format($calc['total_deduction'], 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="slip-footer">
                    <div class="salary_in_word">
                        <span class="words-label">Amount in words</span>
                        Rupees <span id="salary-in-word">{{ $calc['pay_in_words'] }}</span> Only
                    </div>
                    <div class="slip-meta">
                        <p class="slip-note">{{ $payslip->footer_note ?: 'This is a computer-generated salary slip. Please contact HR for any discrepancies.' }}</p>
                        <div class="slip-sign">
                            @if($signatureUrl)
                                <img src="{{ $signatureUrl }}" alt="Digital signature" class="slip-signature-img">
                            @endif
                            <div class="line">{{ $payslip->signatory_name ?: 'Authorized Signatory' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
@if($employee && $calc)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sendUrl = @json(route('salary.send-slip'));
    const csrf = @json(csrf_token());

    function round(value) {
        return Math.round(value * 100) / 100;
    }

    function numberToWords(num) {
        const belowTwenty = [
            'Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        const aboveThousand = ['', 'Thousand', 'Million', 'Billion'];
        num = Math.round(num);
        if (num === 0) return 'Zero';

        function helper(n) {
            if (n < 20) return belowTwenty[n];
            if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + belowTwenty[n % 10] : '');
            if (n < 1000) return belowTwenty[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' ' + helper(n % 100) : '');
            return '';
        }

        let word = '';
        let i = 0;
        while (num > 0) {
            if (num % 1000 !== 0) {
                word = helper(num % 1000) + (aboveThousand[i] ? ' ' + aboveThousand[i] : '') + (word ? ' ' + word : '');
            }
            num = Math.floor(num / 1000);
            i++;
        }
        return word.trim();
    }

    function roundAndConvertToWords(decimal) {
        return numberToWords(Math.round(decimal));
    }

    document.getElementById('calculateButton').addEventListener('click', function () {
        const salary = parseFloat(document.getElementById('salary').value) || 0;
        const totalDays = parseFloat(document.getElementById('totalDays').value) || 0;
        let presentDays = parseFloat(document.getElementById('presentDays').value) || 0;
        const halfDayAll = parseFloat(document.getElementById('half_day_all').value) || 0;
        const normalLate = parseFloat(document.getElementById('normal_late').value) || 0;
        const lateExtra = parseFloat(document.getElementById('late_extra').value) || 0;
        const normalFine = parseFloat(document.getElementById('normal_fine').value) || 0;
        const extraFine = parseFloat(document.getElementById('extra_fine').value) || 0;
        const halfDayFine = parseFloat(document.getElementById('half_day_fine').value) || 0;
        const perDaySalary = parseFloat(document.getElementById('perDaySalary').value) || 0;

        if (presentDays > totalDays) presentDays = totalDays;
        const absentDays = totalDays - presentDays;
        document.getElementById('lop').textContent = absentDays;

        const normalLateAmt = normalLate * normalFine;
        const extraLateAmt = lateExtra * extraFine;
        const halfDayAmt = halfDayAll * halfDayFine;
        const lopAmt = absentDays * perDaySalary;
        const salaryDeductions = normalLateAmt + extraLateAmt + halfDayAmt + lopAmt;
        const salaryAfterDeductions = salary - salaryDeductions;

        document.getElementById('resultSalary').textContent = salaryAfterDeductions.toFixed(2);
        document.getElementById('net-salary').textContent = '₹' + salaryAfterDeductions.toFixed(2);
        document.getElementById('salary-in-word').textContent = roundAndConvertToWords(salaryAfterDeductions);

        const basic = salary * 0.40;
        const hra = basic * 0.50;
        const medical_allowance = 800;
        const conveyance_allowance = 1200;
        const special_allowance = salary - (basic + hra + medical_allowance + conveyance_allowance);
        const total_allowance = basic + hra + medical_allowance + conveyance_allowance + special_allowance;

        document.getElementById('total_allowance').textContent = '₹' + round(total_allowance).toFixed(2);
        document.getElementById('total-deduction').textContent = '₹' + round(salaryDeductions).toFixed(2);
        document.getElementById('basic').textContent = '₹' + basic.toFixed(2);
        document.getElementById('hra').textContent = '₹' + hra.toFixed(2);
        document.getElementById('medical_allowance').textContent = '₹' + medical_allowance.toFixed(2);
        document.getElementById('conveyance_allowance').textContent = '₹' + conveyance_allowance.toFixed(2);
        document.getElementById('special_allowance').textContent = '₹' + special_allowance.toFixed(2);

        const setDed = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = '₹' + round(val).toFixed(2);
        };
        setDed('ded_normal_late', normalLateAmt);
        setDed('ded_extra_late', extraLateAmt);
        setDed('ded_half_day', halfDayAmt);
        setDed('ded_lop', lopAmt);
    });

    async function generatePDF() {
        const salarySlip = document.getElementById('salarySlip');
        if (!salarySlip) {
            alert('Salary slip not found!');
            return;
        }
        const canvas = await html2canvas(salarySlip, { scale: 2, useCORS: true });
        const imgData = canvas.toDataURL('image/png');
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF();
        const imgWidth = 190;
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
        pdf.save('Salary_Slip.pdf');
    }

    document.getElementById('downloadSalarySlip').addEventListener('click', generatePDF);

    document.getElementById('submitButton').addEventListener('click', async function () {
        const salarySlip = document.getElementById('salarySlip');
        const submitButton = document.getElementById('submitButton');
        const loadingIcon = document.getElementById('loadingIcon');
        if (!salarySlip) {
            alert('Salary slip not found!');
            return;
        }

        if (loadingIcon) loadingIcon.style.display = 'inline-block';
        if (submitButton) submitButton.disabled = true;

        try {
            const canvas = await html2canvas(salarySlip, { scale: 2, useCORS: true });
            const imgData = canvas.toDataURL('image/jpeg', 0.7);
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4', compress: true });
            const imgWidth = 190;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            pdf.addImage(imgData, 'JPEG', 10, 10, imgWidth, imgHeight, undefined, 'FAST');
            const pdfBlob = pdf.output('blob');

            const email = document.getElementById('email').value;
            const employeeId = document.getElementById('employee_id')?.value || '';
            if (!email) {
                alert('Official email missing for this employee. Set office_email first.');
                return;
            }

            const formData = new FormData();
            formData.append('pdf', pdfBlob, 'salary_slip.pdf');
            formData.append('email', email);
            if (employeeId) formData.append('employee_id', employeeId);
            formData.append('_token', csrf);

            const response = await fetch(sendUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await response.json();
            if (data.success) {
                alert('Salary slip sent successfully!');
            } else {
                alert('Failed to send salary slip: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error(error);
            alert('An error occurred while sending the salary slip.');
        } finally {
            if (loadingIcon) loadingIcon.style.display = 'none';
            if (submitButton) submitButton.disabled = false;
        }
    });
});
</script>
@endif
<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindPreview(inputId, imgId, emptyId) {
        const input = document.getElementById(inputId);
        const img = document.getElementById(imgId);
        const empty = document.getElementById(emptyId);
        if (!input || !img) return;
        input.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) return;
            const url = URL.createObjectURL(file);
            img.src = url;
            img.classList.remove('d-none');
            if (empty) empty.classList.add('d-none');
        });
    }
    bindPreview('logoInput', 'logoPreview', 'logoPreviewEmpty');
    bindPreview('signInput', 'signPreview', 'signPreviewEmpty');
});
</script>
@endpush
