@php
    $payslip = $payslip ?? \App\Models\PayslipSetting::current();
    $logoUrl = $payslip->logoUrl();
    $signatureUrl = $payslip->signatureUrl();
@endphp
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
        <span class="badge bg-secondary ms-2" style="font-size:.65rem;vertical-align:middle;">Former</span>
    </div>

    <div class="employee-details">
        <div class="normal-details">
            <p class="slip-section-label">Employee details</p>
            <div class="slip-row"><span class="k">Name</span><span class="v">{{ $employee->full_name }}</span></div>
            <div class="slip-row"><span class="k">Designation</span><span class="v">{{ $employee->designation->name ?? '—' }}</span></div>
            <div class="slip-row"><span class="k">Department</span><span class="v">{{ $employee->department->name ?? '—' }}</span></div>
            <div class="slip-row"><span class="k">Gross salary</span><span class="v">₹{{ number_format((float) $calc['salary'], 2) }}</span></div>
            <div class="slip-row"><span class="k">Location</span><span class="v">{{ $payslip->location ?: '—' }}</span></div>
            <div class="slip-row"><span class="k">Working days</span><span class="v">{{ $calc['total_working_days'] }}</span></div>
            <div class="slip-row"><span class="k">Punches (real)</span><span class="v">{{ $punchDays ?? max(0, (int) $calc['present_days'] - (int) $calc['total_holidays']) }}</span></div>
            <div class="slip-row"><span class="k">Present (incl. holidays)</span><span class="v">{{ $calc['present_days'] }}</span></div>
            <div class="slip-row"><span class="k">Holidays credited</span><span class="v">{{ $calc['total_holidays'] }}</span></div>
            <div class="slip-row"><span class="k">LOP</span><span class="v">{{ $calc['lop'] }}</span></div>
        </div>
        <div class="bank-details">
            <p class="slip-section-label">Bank & ID</p>
            <div class="slip-row"><span class="k">Employee ID</span><span class="v">{{ $employee->emp_id ?: $employee->id }}</span></div>
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
                <div class="slip-line"><p class="name">Basic</p><p class="amt">₹{{ number_format($calc['basic'], 2) }}</p></div>
                <div class="slip-line"><p class="name">HRA</p><p class="amt">₹{{ number_format($calc['hra'], 2) }}</p></div>
                <div class="slip-line"><p class="name">Medical Allowance</p><p class="amt">₹{{ number_format($calc['medical_allowance'], 2) }}</p></div>
                <div class="slip-line"><p class="name">Conveyance Allowance</p><p class="amt">₹{{ number_format($calc['conveyance_allowance'], 2) }}</p></div>
                <div class="slip-line"><p class="name">Special Allowance</p><p class="amt">₹{{ number_format($calc['special_allowance'], 2) }}</p></div>
            </div>
            <div class="total-earning">
                <div class="total_allowance">Total Earnings</div>
                <div class="total_allowance">₹{{ number_format($calc['total_allowance'], 2) }}</div>
            </div>
            <div class="net_pay">
                <div>Net Pay</div>
                <div>₹{{ number_format($calc['net_pay'], 2) }}</div>
            </div>
        </div>
        <div class="right-side">
            <div class="right-heading">
                <div>Deductions</div>
                <div>Amount</div>
            </div>
            <div class="deduction-heading">
                <div class="slip-line"><p class="name">Normal Late Fine</p><p class="amt">₹{{ number_format(($calc['normal_late'] ?? 0) * ($calc['normal_fine'] ?? 0), 2) }}</p></div>
                <div class="slip-line"><p class="name">Extra Late Fine</p><p class="amt">₹{{ number_format(($calc['late_extra'] ?? 0) * ($calc['extra_fine'] ?? 0), 2) }}</p></div>
                <div class="slip-line"><p class="name">Half Day Fine</p><p class="amt">₹{{ number_format(($calc['half_day_all'] ?? 0) * ($calc['half_day_fine'] ?? 0), 2) }}</p></div>
                <div class="slip-line"><p class="name">LOP Deduction</p><p class="amt">₹{{ number_format(max(0, ($calc['total_working_days'] ?? 0) - ($calc['present_days'] ?? 0)) * ($calc['per_day_salary'] ?? 0), 2) }}</p></div>
                <div class="slip-line"><p class="name slip-empty">—</p><p class="amt slip-empty">—</p></div>
            </div>
            <div class="total-deduction">
                <div>Total Deductions</div>
                <div>₹{{ number_format($calc['total_deduction'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="slip-footer">
        <div class="salary_in_word">
            <span class="words-label">Amount in words</span>
            Rupees {{ $calc['pay_in_words'] }} Only
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
