@extends('layouts.app')

@section('title', 'HRM Analytics')
@section('heading', 'HRM Analytics')

@push('styles')
<style>
    .an-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .an-wrap .an-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; height:100%;
    }
    .an-wrap .an-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:.75rem; flex-wrap:wrap;
        padding:.9rem 1.1rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .an-wrap .an-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:.98rem; }
    .an-wrap .an-panel-head .sub { font-size:.75rem; color:var(--muted); }
    .an-wrap .an-panel-body { padding:1rem 1.1rem 1.15rem; }
    .an-wrap .an-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.05rem; height:100%;
        position:relative; overflow:hidden;
    }
    .an-wrap .an-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .an-wrap .an-metric.is-blue::before { background:#2563eb; }
    .an-wrap .an-metric.is-green::before { background:#16a34a; }
    .an-wrap .an-metric.is-amber::before { background:#f59e0b; }
    .an-wrap .an-metric.is-red::before { background:#ef4444; }
    .an-wrap .an-metric.is-slate::before { background:#64748b; }
    .an-wrap .an-metric .k { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .an-wrap .an-metric .v { font-size:1.4rem; font-weight:800; color:var(--ink); margin:.15rem 0 0; line-height:1.15; }
    .an-wrap .an-metric .hint { font-size:.72rem; color:var(--muted); margin-top:.2rem; }
    .an-wrap .chart-box { position:relative; height:260px; }
    .an-wrap .chart-box.is-tall { height:300px; }
    .an-wrap .section-title {
        font-size:.78rem; font-weight:750; letter-spacing:.04em; text-transform:uppercase;
        color:var(--muted); margin:1.25rem 0 .75rem;
    }
</style>
@endpush

@section('content')
@php
    $s = $stats;
    $c = $charts;
@endphp
<div class="an-wrap">
    <div class="section-title">Overview</div>
    <div class="row g-3 mb-1">
        <div class="col-6 col-lg-3">
            <div class="an-metric">
                <div class="k">Active employees</div>
                <p class="v">{{ number_format($s['active_employees']) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-slate">
                <div class="k">Inactive / archived</div>
                <p class="v">{{ number_format($s['inactive_employees'] + $s['archived_employees']) }}</p>
                <div class="hint">Inactive {{ $s['inactive_employees'] }} · Archived {{ $s['archived_employees'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-green">
                <div class="k">Attendance today</div>
                <p class="v">{{ number_format($s['attendance_today']) }}</p>
                <div class="hint">Still in · {{ $s['still_in_today'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-blue">
                <div class="k">Logins today</div>
                <p class="v">{{ number_format($s['employee_logins_today'] + $s['admin_logins_today']) }}</p>
                <div class="hint">Employees {{ $s['employee_logins_today'] }} · Admins {{ $s['admin_logins_today'] }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-amber">
                <div class="k">Pending leaves</div>
                <p class="v">{{ number_format($s['pending_leaves']) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-amber">
                <div class="k">Open tickets</div>
                <p class="v">{{ number_format($s['open_tickets']) }}</p>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-amber">
                <div class="k">Pending expenses</div>
                <p class="v">{{ number_format($s['pending_expenses']) }}</p>
                <div class="hint">Month total ₹{{ number_format($s['expense_amount_month'], 0) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="an-metric is-red">
                <div class="k">Pending resignations</div>
                <p class="v">{{ number_format($s['pending_resignations']) }}</p>
            </div>
        </div>
    </div>

    <div class="section-title">Workforce &amp; login</div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>Employees mix</h5>
                    <span class="sub">Pie</span>
                </div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartEmployeesPie"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>Logins today</h5>
                    <span class="sub">Pie</span>
                </div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartLoginPie"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>By department</h5>
                    <span class="sub">Top 10</span>
                </div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartDepartments"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="section-title">Attendance &amp; access trends</div>
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>Attendance (last 14 days)</h5>
                    <span class="sub">Line</span>
                </div>
                <div class="an-panel-body"><div class="chart-box is-tall"><canvas id="chartAttendance"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>Employee logins (last 7 days)</h5>
                    <span class="sub">Bar</span>
                </div>
                <div class="an-panel-body"><div class="chart-box is-tall"><canvas id="chartEmployeeLogin"></canvas></div></div>
            </div>
        </div>
        <div class="col-12">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>Admin login / logout (last 7 days)</h5>
                    <span class="sub">Grouped bar</span>
                </div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartAdminLogin"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="section-title">Leaves, tickets, expenses &amp; resignations</div>
    <div class="row g-3">
        <div class="col-md-4">
            <div class="an-panel">
                <div class="an-panel-head"><h5>Leave status</h5><span class="sub">Doughnut</span></div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartLeavesPie"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="an-panel">
                <div class="an-panel-head"><h5>Ticket status</h5><span class="sub">Doughnut</span></div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartTicketsPie"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="an-panel">
                <div class="an-panel-head"><h5>Expense status</h5><span class="sub">Doughnut</span></div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartExpensesPie"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="an-panel">
                <div class="an-panel-head"><h5>Resignations</h5><span class="sub">Pie</span></div>
                <div class="an-panel-body"><div class="chart-box"><canvas id="chartResignationsPie"></canvas></div></div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="an-panel">
                <div class="an-panel-head">
                    <h5>Leave applications — {{ $year }}</h5>
                    <span class="sub">Monthly bar</span>
                </div>
                <div class="an-panel-body"><div class="chart-box is-tall"><canvas id="chartLeavesMonth"></canvas></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const charts = @json($charts);
    const colors = {
        orange: '#ff9b44',
        navy: '#0f2744',
        blue: '#2563eb',
        green: '#16a34a',
        amber: '#f59e0b',
        red: '#ef4444',
        slate: '#64748b',
        purple: '#7c3aed',
        teal: '#0d9488',
        soft: ['#ff9b44', '#2563eb', '#16a34a', '#f59e0b', '#ef4444', '#7c3aed', '#0d9488', '#64748b', '#db2777', '#0891b2']
    };

    const baseOpts = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
        }
    };

    function pie(id, payload, type = 'pie') {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type,
            data: {
                labels: payload.labels,
                datasets: [{
                    data: payload.data,
                    backgroundColor: colors.soft.slice(0, payload.labels.length),
                    borderWidth: 0
                }]
            },
            options: baseOpts
        });
    }

    pie('chartEmployeesPie', charts.employeesPie);
    pie('chartLoginPie', charts.loginPie);
    pie('chartLeavesPie', charts.leavesPie, 'doughnut');
    pie('chartTicketsPie', charts.ticketsPie, 'doughnut');
    pie('chartExpensesPie', charts.expensesPie, 'doughnut');
    pie('chartResignationsPie', charts.resignationsPie);

    new Chart(document.getElementById('chartDepartments'), {
        type: 'bar',
        data: {
            labels: charts.departmentsBar.labels,
            datasets: [{
                label: 'Employees',
                data: charts.departmentsBar.data,
                backgroundColor: colors.orange,
                borderRadius: 8,
                maxBarThickness: 28
            }]
        },
        options: {
            ...baseOpts,
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
                y: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartAttendance'), {
        type: 'line',
        data: {
            labels: charts.attendanceLine.labels,
            datasets: [{
                label: 'Clock-ins',
                data: charts.attendanceLine.data,
                borderColor: colors.blue,
                backgroundColor: 'rgba(37,99,235,.15)',
                fill: true,
                tension: .35,
                pointRadius: 3,
                pointBackgroundColor: colors.blue
            }]
        },
        options: {
            ...baseOpts,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartEmployeeLogin'), {
        type: 'bar',
        data: {
            labels: charts.employeeLoginBar.labels,
            datasets: [{
                label: 'Employee logins',
                data: charts.employeeLoginBar.data,
                backgroundColor: colors.teal,
                borderRadius: 8,
                maxBarThickness: 36
            }]
        },
        options: {
            ...baseOpts,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartAdminLogin'), {
        type: 'bar',
        data: {
            labels: charts.adminLoginBar.labels,
            datasets: [
                {
                    label: 'Login',
                    data: charts.adminLoginBar.login,
                    backgroundColor: colors.green,
                    borderRadius: 6,
                    maxBarThickness: 28
                },
                {
                    label: 'Logout',
                    data: charts.adminLoginBar.logout,
                    backgroundColor: colors.slate,
                    borderRadius: 6,
                    maxBarThickness: 28
                }
            ]
        },
        options: {
            ...baseOpts,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });

    new Chart(document.getElementById('chartLeavesMonth'), {
        type: 'bar',
        data: {
            labels: charts.leavesBar.labels,
            datasets: [{
                label: 'Applications',
                data: charts.leavesBar.data,
                backgroundColor: colors.purple,
                borderRadius: 8,
                maxBarThickness: 32
            }]
        },
        options: {
            ...baseOpts,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#eef2f7' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
