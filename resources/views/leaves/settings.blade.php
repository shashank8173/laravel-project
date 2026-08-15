@extends('layouts.app')

@section('title', 'Leave Settings')
@section('heading', 'Leave Settings')

@section('page_actions')
<a href="{{ route('leaves.admin') }}" class="btn btn-outline-secondary me-1">
    <i class="fa-solid fa-arrow-left me-1"></i> Back to Leaves
</a>
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#addLeaveTypeModal">
    <i class="fa-solid fa-plus"></i> Add leave type
</button>
@endsection

@push('styles')
<style>
    .ls-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ls-wrap .ls-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .ls-wrap .ls-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ls-wrap .ls-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .ls-wrap .ls-panel-body { padding:1.15rem; }
    .ls-wrap .ls-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .ls-wrap .ls-metric::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent); }
    .ls-wrap .ls-metric.is-days::before { background:#2563eb; }
    .ls-wrap .ls-metric.is-used::before { background:#16a34a; }
    .ls-wrap .ls-metric .k { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700; }
    .ls-wrap .ls-metric .v { font-size:1.45rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .ls-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ls-wrap .table > :not(caption) > * > * { vertical-align:middle; }
    .ls-wrap .name-input {
        font-weight:650; color:var(--ink); border-color:var(--line);
    }
    .ls-wrap .days-pill {
        display:inline-flex; align-items:center; justify-content:center; min-width:2.4rem;
        padding:.2rem .55rem; border-radius:999px; background:#fff7ed; color:#9a3412;
        font-size:.75rem; font-weight:700;
    }
    .ls-wrap .used-muted { font-size:.78rem; color:var(--muted); }
    .ls-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700;
    }
    .ls-wrap .btn-save:hover { filter:brightness(.96); color:#fff; }
    .ls-wrap .empty { text-align:center; color:var(--muted); padding:2rem 1rem; }
</style>
@endpush

@section('content')
<div class="ls-wrap">
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-4">
            <div class="ls-metric">
                <div class="k">Leave types</div>
                <p class="v">{{ $stats['types'] }}</p>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="ls-metric is-days">
                <div class="k">Total days configured</div>
                <p class="v">{{ $stats['days'] }}</p>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="ls-metric is-used">
                <div class="k">Applications linked</div>
                <p class="v">{{ $stats['used'] }}</p>
            </div>
        </div>
    </div>

    <div class="ls-panel">
        <div class="ls-panel-head">
            <h5><i class="fa-solid fa-calendar-days me-1" style="color:var(--accent)"></i> Leave types</h5>
            <span class="small text-muted">Edit name &amp; days, then save</span>
        </div>
        <div class="table-responsive p-2">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th style="width:42%;">Leave type</th>
                    <th style="width:18%;">Days / year</th>
                    <th style="width:18%;">Applications</th>
                    <th class="text-end" style="width:22%;">Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($types as $type)
                    <tr>
                        <td>
                            <form id="upd-{{ $type->id }}" method="POST" action="{{ route('leave-settings.update', $type) }}">
                                @csrf @method('PUT')
                                <input name="name" value="{{ $type->name }}" class="form-control name-input" required>
                            </form>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <input form="upd-{{ $type->id }}" type="number" name="number_of_leave"
                                       value="{{ $type->number_of_leave }}" class="form-control" min="0" required style="max-width:110px;">
                                <span class="days-pill">days</span>
                            </div>
                        </td>
                        <td>
                            <span class="used-muted">{{ $type->leaves_count }} linked</span>
                        </td>
                        <td class="text-end text-nowrap">
                            <button form="upd-{{ $type->id }}" class="btn btn-sm btn-save me-1">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save
                            </button>
                            <form method="POST" action="{{ route('leave-settings.destroy', $type) }}" class="d-inline"
                                  onsubmit="return confirm('Delete leave type “{{ $type->name }}”?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" @disabled($type->leaves_count > 0)
                                        title="{{ $type->leaves_count > 0 ? 'Cannot delete — applications exist' : 'Delete' }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty">No leave types yet. Add one to get started.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Add leave type --}}
<div class="modal fade" id="addLeaveTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('leave-settings.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add leave type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body ls-wrap">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Casual Leave" required>
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Number of leaves (days / year)</label>
                        <input type="number" name="number_of_leave" class="form-control" min="0" value="0" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Add leave type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
