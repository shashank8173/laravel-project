@extends('layouts.app')

@section('title', $employee->full_name)
@section('heading', 'Employee Profile')

@section('page_actions')
<a href="{{ route('employees.index') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-arrow-left me-1"></i> Employees
</a>
<a href="{{ route('employees.edit', $employee) }}" class="btn add-btn">
    <i class="fa-solid fa-pen me-1"></i> Edit
</a>
@endsection

@push('styles')
<style>
    .ep-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; --card:#fff; }
    .ep-wrap .ep-panel { background:var(--card); border:1px solid var(--line); border-radius:18px; overflow:hidden; height:100%; }
    .ep-wrap .ep-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:var(--soft);
    }
    .ep-wrap .ep-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .ep-wrap .ep-panel-body { padding:1.15rem; background:var(--card); }
    .ep-wrap .hero {
        border:1px solid var(--line); border-radius:18px; background:var(--card); overflow:hidden; margin-bottom:1rem;
    }
    .ep-wrap .hero-top {
        padding:1.25rem 1.35rem; display:flex; gap:1rem; align-items:center; flex-wrap:wrap;
        background:linear-gradient(135deg, rgba(255,155,68,.12), transparent);
        border-bottom:1px solid var(--line);
    }
    .ep-wrap .hero-avatar {
        width:72px; height:72px; border-radius:50%; object-fit:cover;
        border:3px solid #ffe0c2; background:var(--soft);
    }
    .ep-wrap .hero-name { font-size:1.25rem; font-weight:800; color:var(--ink); margin:0 0 .2rem; }
    .ep-wrap .hero-sub { color:var(--muted); font-size:.86rem; margin:0; }
    .ep-wrap .ep-pill {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px; font-size:.72rem; font-weight:700; text-transform:capitalize;
    }
    .ep-wrap .role-user { background:#eef2ff; color:#4338ca; }
    .ep-wrap .role-admin { background:#dcfce7; color:#15803d; }
    .ep-wrap .role-super { background:#fef3c7; color:#b45309; }
    .ep-wrap .st-on { background:#dcfce7; color:#15803d; }
    .ep-wrap .st-off { background:#e2e8f0; color:#475569; }
    .ep-wrap .meta-grid {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.85rem 1.1rem;
        padding:1.15rem 1.35rem; background:var(--card);
    }
    .ep-wrap .meta-grid .k { font-size:.72rem; color:var(--muted); font-weight:600; text-transform:uppercase; letter-spacing:.03em; }
    .ep-wrap .meta-grid .v { font-size:.95rem; color:var(--ink); font-weight:700; margin-top:.15rem; word-break:break-word; }
    .ep-wrap .info-row {
        display:grid; grid-template-columns:140px 1fr; gap:.5rem; padding:.55rem 0; border-bottom:1px solid var(--line);
        font-size:.92rem;
    }
    .ep-wrap .info-row:last-child { border-bottom:0; }
    .ep-wrap .info-row .k { color:var(--muted); font-weight:600; }
    .ep-wrap .info-row .v { color:var(--ink); font-weight:650; }
    .ep-wrap .list-item {
        display:flex; justify-content:space-between; gap:.75rem; padding:.7rem 0; border-bottom:1px solid var(--line);
    }
    .ep-wrap .list-item:last-child { border-bottom:0; padding-bottom:0; }
    .ep-wrap .list-item .t { font-weight:700; color:var(--ink); margin:0; }
    .ep-wrap .list-item .d { font-size:.78rem; color:var(--muted); margin:.1rem 0 0; }
    .ep-wrap .empty { color:var(--muted); font-size:.9rem; padding:.5rem 0; }
    .ep-wrap .avatar-wrap { position:relative; flex-shrink:0; }
    .ep-wrap .avatar-cam {
        position:absolute; right:0; bottom:0; width:32px; height:32px; border-radius:50%;
        border:2px solid var(--card); background:var(--accent); color:#fff;
        display:grid; place-items:center; cursor:pointer; font-size:.8rem;
        box-shadow:0 4px 10px rgba(255,155,68,.35);
    }
    .ep-wrap .avatar-cam:hover { filter:brightness(1.05); color:#fff; }
    .ep-wrap .avatar-cam input { display:none; }

    html[data-theme="dark"] .ep-wrap {
        --ink:#e8eef8; --muted:#a8b6cc; --line:#243044;
        --card:#141b27; --soft:#1a2232;
    }
    html[data-theme="dark-blue"] .ep-wrap {
        --ink:#eaf2ff; --muted:#9db4d4; --line:#1a3358;
        --card:#0c1a31; --soft:#102240;
    }
    html[data-theme="light"] .ep-wrap {
        --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5;
        --card:#ffffff; --soft:#f4f7fb;
    }
</style>
@endpush

@section('content')
@php
    $roleClass = match (strtolower(trim((string) $employee->role))) {
        'admin' => 'role-admin',
        'super admin' => 'role-super',
        default => 'role-user',
    };
    $active = (int) $employee->status === 1;
@endphp
<div class="ep-wrap">
    <div class="hero">
        <div class="hero-top">
            <div class="avatar-wrap">
                <img src="{{ $employee->profile_image_url }}" class="hero-avatar" alt="" id="empPhotoPreview"
                     onerror="this.src='{{ asset('assets/img/profiles/avatar-02.jpg') }}'">
                <label class="avatar-cam" title="Update photo">
                    <i class="fa-solid fa-camera"></i>
                    <input type="file" id="empPhotoPick" accept="image/jpeg,image/png,image/webp,image/gif">
                </label>
            </div>
            <div style="flex:1;min-width:200px;">
                <p class="hero-name">{{ $employee->full_name }}</p>
                <p class="hero-sub">
                    {{ $employee->designation?->name ?: '—' }}
                    · {{ $employee->department?->name ?: '—' }}
                </p>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="ep-pill {{ $roleClass }}">{{ $employee->role ?: 'user' }}</span>
                    <span class="ep-pill {{ $active ? 'st-on' : 'st-off' }}">{{ $active ? 'Active' : 'Inactive' }}</span>
                    @if($employee->emp_id)
                        <span class="ep-pill" style="background:var(--soft);color:var(--ink);">ID {{ $employee->emp_id }}</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('employees.photo', $employee) }}" enctype="multipart/form-data"
                      id="empPhotoForm" class="d-flex flex-wrap gap-2 align-items-center mt-3">
                    @csrf
                    <input type="file" name="image" id="empPhotoHidden" class="d-none" accept="image/*" required>
                    <button type="submit" class="btn btn-sm add-btn" id="empPhotoSubmit" disabled>
                        <i class="fa-solid fa-upload me-1"></i> Save photo
                    </button>
                    <span class="small text-muted">JPG / PNG / WEBP · max 4MB</span>
                </form>
            </div>
        </div>
        <div class="meta-grid">
            <div>
                <div class="k">Office email</div>
                <div class="v">{{ $employee->office_email ?: '—' }}</div>
            </div>
            <div>
                <div class="k">Personal email</div>
                <div class="v">{{ $employee->email ?: '—' }}</div>
            </div>
            <div>
                <div class="k">Mobile</div>
                <div class="v">{{ $employee->mobile1 ?: '—' }}</div>
            </div>
            <div>
                <div class="k">Date of joining</div>
                <div class="v">{{ optional($employee->doj)->format('d M Y') ?: '—' }}</div>
            </div>
            <div>
                <div class="k">Attendance ID</div>
                <div class="v">{{ $employee->attendance_id ?: '—' }}</div>
            </div>
            <div>
                <div class="k">Job title</div>
                <div class="v">{{ $employee->job_title ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="ep-panel">
                <div class="ep-panel-head">
                    <h5><i class="fa-solid fa-id-card me-1" style="color:var(--accent)"></i> Personal details</h5>
                </div>
                <div class="ep-panel-body">
                    <div class="info-row"><span class="k">Date of birth</span><span class="v">{{ optional($employee->dob)->format('d M Y') ?: '—' }}</span></div>
                    <div class="info-row"><span class="k">Gender</span><span class="v">{{ (int)$employee->gender === 2 ? 'Female' : ((int)$employee->gender === 1 ? 'Male' : '—') }}</span></div>
                    <div class="info-row"><span class="k">Blood group</span><span class="v">{{ $employee->bgroup ?: '—' }}</span></div>
                    <div class="info-row"><span class="k">Marital status</span><span class="v">{{ (int)$employee->marital_status === 1 ? 'Married' : ((int)$employee->marital_status === 2 ? 'Unmarried' : '—') }}</span></div>
                    <div class="info-row"><span class="k">Nationality</span><span class="v">{{ $employee->nationality ?: '—' }}</span></div>
                    <div class="info-row"><span class="k">Religion</span><span class="v">{{ $employee->religion ?: '—' }}</span></div>
                    <div class="info-row"><span class="k">Current address</span><span class="v">{{ $employee->current_address ?: '—' }}</span></div>
                    <div class="info-row"><span class="k">Permanent address</span><span class="v">{{ $employee->permanent_address ?: '—' }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ep-panel">
                <div class="ep-panel-head">
                    <h5><i class="fa-solid fa-building-columns me-1" style="color:var(--accent)"></i> Bank details</h5>
                </div>
                <div class="ep-panel-body">
                    @if($employee->bankDetail)
                        <div class="info-row"><span class="k">Bank</span><span class="v">{{ $employee->bankDetail->bank_name ?: '—' }}</span></div>
                        <div class="info-row"><span class="k">Account</span><span class="v">{{ $employee->bankDetail->account_number ?: '—' }}</span></div>
                        <div class="info-row"><span class="k">IFSC</span><span class="v">{{ $employee->bankDetail->ifsc ?: '—' }}</span></div>
                        <div class="info-row"><span class="k">Branch</span><span class="v">{{ $employee->bankDetail->branch ?: '—' }}</span></div>
                        <div class="info-row"><span class="k">Holder</span><span class="v">{{ $employee->bankDetail->account_holder_name ?: '—' }}</span></div>
                        <div class="info-row"><span class="k">PAN</span><span class="v">{{ $employee->bankDetail->pan ?: '—' }}</span></div>
                    @else
                        <div class="empty">No bank details added.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="ep-panel">
                <div class="ep-panel-head">
                    <h5><i class="fa-solid fa-people-roof me-1" style="color:var(--accent)"></i> Family</h5>
                    <span class="small text-muted">{{ $employee->familyMembers->count() }}</span>
                </div>
                <div class="ep-panel-body">
                    @forelse($employee->familyMembers as $f)
                        <div class="list-item">
                            <div>
                                <p class="t">{{ $f->name }}</p>
                                <p class="d">{{ $f->relationship?->name ?: '—' }}</p>
                            </div>
                            <div class="sub text-end">{{ $f->phone ?: '' }}</div>
                        </div>
                    @empty
                        <div class="empty">No family members.</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ep-panel">
                <div class="ep-panel-head">
                    <h5><i class="fa-solid fa-graduation-cap me-1" style="color:var(--accent)"></i> Education</h5>
                    <span class="small text-muted">{{ $employee->educations->count() }}</span>
                </div>
                <div class="ep-panel-body">
                    @forelse($employee->educations as $e)
                        <div class="list-item">
                            <div>
                                <p class="t">{{ $e->course_name ?: ($e->qualification_type ?: 'Education') }}</p>
                                <p class="d">{{ $e->college_name ?: ($e->university_name ?: '—') }}</p>
                            </div>
                            <div class="sub text-end">{{ $e->grade ?: '' }}</div>
                        </div>
                    @empty
                        <div class="empty">No education records.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const pick = document.getElementById('empPhotoPick');
    const hidden = document.getElementById('empPhotoHidden');
    const submit = document.getElementById('empPhotoSubmit');
    const preview = document.getElementById('empPhotoPreview');

    pick?.addEventListener('change', function () {
        const file = this.files?.[0];
        if (!file || !hidden) return;
        const dt = new DataTransfer();
        dt.items.add(file);
        hidden.files = dt.files;
        if (submit) submit.disabled = false;
        if (preview) preview.src = URL.createObjectURL(file);
    });
})();
</script>
@endpush
