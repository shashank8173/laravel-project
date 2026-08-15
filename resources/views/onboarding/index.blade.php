@extends('layouts.app')

@section('title', 'Onboarding')
@section('heading', 'Employee Onboarding')

@section('page_actions')
@if(($tab ?? 'progress') === 'steps')
<button type="button" class="btn add-btn me-2" data-bs-toggle="modal" data-bs-target="#addStepModal">
    <i class="fa-solid fa-plus"></i> Add Step
</button>
<a href="{{ route('onboarding.index', ['tab' => 'progress']) }}" class="btn btn-outline-secondary">
    <i class="fa-solid fa-user-check"></i> Progress
</a>
@else
<a href="{{ route('onboarding.index', ['tab' => 'steps']) }}" class="btn btn-outline-secondary">
    <i class="fa-solid fa-list-check"></i> Manage Steps
</a>
@endif
@endsection

@push('styles')
<style>
    .ob-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .ob-wrap .ob-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .ob-wrap .ob-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .ob-wrap .ob-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .ob-wrap .ob-panel-body { padding:1.15rem; }
    .ob-wrap .ob-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .ob-wrap .ob-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .ob-wrap .ob-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .ob-wrap .ob-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .ob-wrap .ob-tabs {
        display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem;
        padding:.35rem; background:var(--soft); border:1px solid var(--line); border-radius:14px;
        width:fit-content; max-width:100%;
    }
    .ob-wrap .ob-tab {
        border:1px solid transparent; background:transparent; color:var(--muted);
        font-weight:700; padding:.55rem 1rem; border-radius:10px; text-decoration:none;
        display:inline-flex; align-items:center; gap:.4rem;
    }
    .ob-wrap .ob-tab.active {
        background:#fff; color:#c2410c; border-color:#ffd7b0;
        box-shadow:0 2px 8px rgba(15,39,68,.06);
    }
    .ob-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .ob-wrap .ob-progress {
        height:14px; border-radius:999px; background:#eef2f7; overflow:hidden;
    }
    .ob-wrap .ob-progress > span {
        display:block; height:100%; border-radius:999px;
        background:linear-gradient(90deg,#ff9b44,#55ce63);
        transition:width .35s ease;
    }
    .ob-wrap .ob-accordion .accordion-item {
        border:1px solid var(--line); border-radius:14px !important; overflow:hidden; margin-bottom:.65rem;
        background:#fff;
    }
    .ob-wrap .ob-accordion .accordion-button {
        background:#fff; color:var(--ink); font-weight:700; box-shadow:none !important;
        padding:1rem 1.1rem;
    }
    .ob-wrap .ob-accordion .accordion-button:not(.collapsed) { background:#fff8f2; }
    .ob-wrap .ob-accordion .accordion-button.completed {
        background:#ecfdf5; color:#047857;
    }
    .ob-wrap .ob-accordion .accordion-button.submitted:not(.completed) {
        background:#fff7ed; color:#c2410c;
    }
    .ob-wrap .ob-accordion .accordion-button::after { margin-left:.75rem; }
    .ob-wrap .ob-meta {
        font-size:.75rem; color:var(--muted); font-weight:600; margin-left:auto; padding-right:.5rem;
        white-space:nowrap;
    }
    .ob-wrap .ob-file {
        display:flex; align-items:center; justify-content:space-between; gap:.75rem; flex-wrap:wrap;
        border:1px solid var(--line); border-radius:12px; padding:.65rem .85rem; background:var(--soft);
        margin-bottom:.5rem;
    }
    .ob-wrap .ob-file .name { font-weight:650; color:var(--ink); margin:0; }
    .ob-wrap .ob-desc { color:var(--ink); white-space:pre-wrap; margin-bottom:1rem; }
    .ob-wrap .badge-done {
        display:inline-flex; padding:.22rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700;
        background:#dcfce7; color:#15803d;
    }
    .ob-wrap .badge-pending {
        display:inline-flex; padding:.22rem .55rem; border-radius:999px; font-size:.68rem; font-weight:700;
        background:#fff7ed; color:#c2410c;
    }
    .ob-wrap .ob-step-list { display:flex; flex-direction:column; gap:.65rem; }
    .ob-wrap .ob-step-card {
        display:grid; grid-template-columns:40px 48px 1fr auto; gap:.85rem; align-items:center;
        border:1px solid var(--line); border-radius:16px; background:#fff; padding:.95rem 1rem;
        transition: box-shadow .15s ease, transform .15s ease, border-color .15s ease;
        cursor:grab;
    }
    .ob-wrap .ob-step-card:active { cursor:grabbing; }
    .ob-wrap .ob-step-card:hover { border-color:#ffd7b0; box-shadow:0 8px 22px rgba(15,39,68,.06); }
    .ob-wrap .ob-step-card.sortable-ghost {
        opacity:.55; background:#fff8f2; border-style:dashed; border-color:var(--accent);
    }
    .ob-wrap .ob-step-card.sortable-chosen { box-shadow:0 12px 28px rgba(15,39,68,.12); }
    .ob-wrap .ob-drag {
        width:32px; height:32px; border-radius:10px; display:grid; place-items:center;
        color:var(--muted); background:var(--soft); border:1px solid var(--line);
    }
    .ob-wrap .ob-order {
        width:40px; height:40px; border-radius:12px; display:grid; place-items:center;
        font-weight:800; color:#c2410c; background:#fff7ed; border:1px solid #ffd7b0;
    }
    .ob-wrap .ob-step-card .title { margin:0; font-weight:800; color:var(--ink); font-size:1rem; }
    .ob-wrap .ob-step-card .desc {
        margin:.2rem 0 0; color:var(--muted); font-size:.85rem; line-height:1.4;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .ob-wrap .ob-step-actions { display:flex; gap:.4rem; flex-wrap:wrap; justify-content:flex-end; }
    .ob-wrap .ob-hint {
        display:flex; align-items:center; gap:.5rem; color:var(--muted); font-size:.85rem; font-weight:600;
        margin-bottom:1rem;
    }
    .ob-wrap .ob-save-pill {
        display:none; align-items:center; gap:.4rem; padding:.35rem .75rem; border-radius:999px;
        background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:.75rem; font-weight:700;
    }
    .ob-wrap .ob-save-pill.show { display:inline-flex; }
    .ob-wrap .ob-empty-steps {
        text-align:center; padding:2.5rem 1rem; color:var(--muted);
        border:1px dashed var(--line); border-radius:16px; background:#fafbfd;
    }
    @media (max-width:767px) {
        .ob-wrap .ob-step-card { grid-template-columns:32px 1fr; }
        .ob-wrap .ob-step-card .ob-order { display:none; }
        .ob-wrap .ob-step-actions { grid-column:1 / -1; justify-content:flex-start; }
    }
</style>
@endpush

@section('content')
@php $activeTab = $tab ?? 'progress'; @endphp
<div class="ob-wrap">
    <div class="ob-tabs">
        <a class="ob-tab {{ $activeTab === 'progress' ? 'active' : '' }}"
           href="{{ route('onboarding.index', array_filter(['tab' => 'progress', 'employee_id' => $employeeId ?: null])) }}">
            <i class="fa-solid fa-user-check"></i> Employee progress
        </a>
        <a class="ob-tab {{ $activeTab === 'steps' ? 'active' : '' }}"
           href="{{ route('onboarding.index', ['tab' => 'steps']) }}">
            <i class="fa-solid fa-list-check"></i> Manage steps
        </a>
    </div>

    @if($activeTab === 'steps')
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="ob-metric">
                    <div class="k">Total steps</div>
                    <p class="v">{{ $masterSteps->count() }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ob-metric">
                    <div class="k">Order</div>
                    <p class="v" style="font-size:1rem;padding-top:.4rem;">Drag to rearrange</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ob-metric">
                    <div class="k">Quick add</div>
                    <p class="v" style="font-size:1rem;padding-top:.4rem;">
                        <a href="#" class="text-decoration-none" style="color:#c2410c;font-weight:800;"
                           data-bs-toggle="modal" data-bs-target="#addStepModal">Open add modal</a>
                    </p>
                </div>
            </div>
        </div>

        <div class="ob-panel">
            <div class="ob-panel-head">
                <div>
                    <h5 class="mb-1">Master checklist</h5>
                    <div class="ob-hint mb-0"><i class="fa-solid fa-up-down-left-right"></i> Drag cards to change step order</div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="ob-save-pill" id="orderSavedPill"><i class="fa-solid fa-check"></i> Order saved</span>
                    <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#addStepModal">
                        <i class="fa-solid fa-plus"></i> Add Step
                    </button>
                </div>
            </div>
            <div class="ob-panel-body">
                @if($masterSteps->isEmpty())
                    <div class="ob-empty-steps">
                        <div class="mb-2" style="font-size:1.8rem;opacity:.4;"><i class="fa-solid fa-list-check"></i></div>
                        No onboarding steps yet.
                        <div class="mt-3">
                            <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#addStepModal">
                                Add your first step
                            </button>
                        </div>
                    </div>
                @else
                    <div class="ob-step-list" id="sortableSteps">
                        @foreach($masterSteps as $step)
                            <div class="ob-step-card" data-id="{{ $step->step_id }}">
                                <div class="ob-drag" title="Drag"><i class="fa-solid fa-grip-vertical"></i></div>
                                <div class="ob-order step-order-badge">{{ (int) $step->step_order + 1 }}</div>
                                <div>
                                    <p class="title">{{ $step->step_name }}</p>
                                    <div class="desc hrm-rich-content">{!! $step->description ?: '<em>No description</em>' !!}</div>
                                </div>
                                <div class="ob-step-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal" data-bs-target="#editStep{{ $step->step_id }}">
                                        <i class="fa-solid fa-pen"></i> Edit
                                    </button>
                                    <form method="POST" action="{{ route('onboarding.master.destroy', $step) }}"
                                          onsubmit="return confirm('Delete this step for all employees?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Add modal --}}
        <div class="modal fade" id="addStepModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:0;border-radius:16px;">
                    <form method="POST" action="{{ route('onboarding.master') }}">
                        @csrf
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Add onboarding step</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label">Step name</label>
                                <input type="text" name="step_name" class="form-control" required
                                       value="{{ old('step_name') }}" placeholder="e.g. Collect documents">
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control js-rich-editor" rows="4"
                                          placeholder="Click to write what HR / employee should do…">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn add-btn">Save step</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach($masterSteps as $step)
        <div class="modal fade" id="editStep{{ $step->step_id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border:0;border-radius:16px;">
                    <form method="POST" action="{{ route('onboarding.master.update', $step) }}">
                        @csrf
                        @method('PUT')
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Edit step</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-2">
                                <label class="form-label">Step name</label>
                                <input type="text" name="step_name" class="form-control" value="{{ $step->step_name }}" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control js-rich-editor" rows="4"
                                          placeholder="Click to edit step description…">{{ $step->description }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn add-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @else
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="ob-metric">
                    <div class="k">Master steps</div>
                    <p class="v">{{ $masterSteps->count() }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ob-metric">
                    <div class="k">Completed</div>
                    <p class="v">{{ $selected ? $completed.' / '.$total : '—' }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ob-metric">
                    <div class="k">Progress</div>
                    <p class="v">{{ $selected ? $percent.'%' : '—' }}</p>
                </div>
            </div>
        </div>

        <div class="ob-panel mb-3">
            <div class="ob-panel-head">
                <h5><i class="fa-solid fa-user me-1" style="color:var(--accent)"></i> Select employee</h5>
            </div>
            <div class="ob-panel-body">
                <form method="GET" action="{{ route('onboarding.index') }}" class="row g-2 align-items-end">
                    <input type="hidden" name="tab" value="progress">
                    <div class="col-md-9">
                        <label class="form-label">Employee</label>
                        <x-employee-select
                            name="employee_id"
                            :employees="$employees"
                            :selected="$employeeId"
                            :required="true"
                            placeholder="Search employee…"
                        />
                    </div>
                    <div class="col-md-3">
                        <button class="btn add-btn w-100">View onboarding</button>
                    </div>
                </form>
            </div>
        </div>

        @if($selected)
            <div class="ob-panel mb-3">
                <div class="ob-panel-head">
                    <div>
                        <h5 class="mb-1">{{ $selected->full_name }}</h5>
                        <div class="small text-muted">
                            {{ $selected->emp_id ?: 'ID '.$selected->id }}
                            @if($selected->doj) · Joined {{ \Illuminate\Support\Carbon::parse($selected->doj)->format('d M Y') }} @endif
                        </div>
                    </div>
                    <span class="small text-muted">{{ $percent }}% complete</span>
                </div>
                <div class="ob-panel-body">
                    <div class="d-flex justify-content-between small mb-2">
                        <span class="text-muted">Checklist progress</span>
                        <strong style="color:var(--ink);">{{ $completed }} of {{ $total }} done</strong>
                    </div>
                    <div class="ob-progress mb-3"><span style="width:{{ $percent }}%"></span></div>

                    <div class="mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleIncomplete">
                            Show incomplete steps
                        </button>
                    </div>
                    <div id="incompleteSteps" class="mb-3" style="display:none;">
                        @php $incomplete = $progress->where('status', 0); @endphp
                        @forelse($incomplete as $row)
                            <div class="ob-file">
                                <div>
                                    <p class="name mb-0">{{ $row->step?->step_name ?? 'Step' }}</p>
                                    <div class="small text-muted hrm-rich-content">{!! \Illuminate\Support\Str::limit(strip_tags((string) ($row->step?->description ?? '')), 100) !!}</div>
                                </div>
                                <span class="badge-pending">Pending</span>
                            </div>
                        @empty
                            <div class="text-success small">All steps completed.</div>
                        @endforelse
                    </div>

                    <div class="accordion ob-accordion" id="onboardingAccordion">
                        @forelse($progress as $index => $row)
                            @php
                                $files = $row->filesForEmployee();
                                $done = (int) $row->status === 1;
                                $submitted = ! empty($row->update_date);
                                $btnClass = $done ? 'completed' : ($submitted ? 'submitted' : '');
                            @endphp
                            <div class="accordion-item">
                                <h2 class="accordion-header d-flex align-items-center flex-wrap">
                                    <button class="accordion-button collapsed flex-grow-1 {{ $btnClass }}" type="button"
                                            data-bs-toggle="collapse" data-bs-target="#obStep{{ $row->id }}">
                                        <span class="me-2">{{ $row->step?->step_name ?? 'Step' }}</span>
                                        @if($done)
                                            <span class="badge-done">Completed</span>
                                        @elseif($submitted)
                                            <span class="badge-pending">Updated</span>
                                        @endif
                                    </button>
                                    <span class="ob-meta px-3 py-2">
                                        {{ $row->update_date ? 'Updated '.$row->update_date->format('d M Y, H:i') : 'Not updated yet' }}
                                    </span>
                                </h2>
                                <div id="obStep{{ $row->id }}" class="accordion-collapse collapse" data-bs-parent="#onboardingAccordion">
                                    <div class="accordion-body">
                                        <div class="ob-desc hrm-rich-content">{!! $row->step?->description ?: '<em>No description.</em>' !!}</div>

                                        <form method="POST" action="{{ route('onboarding.step.update', $row) }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" name="status" value="1" id="done{{ $row->id }}"
                                                       @checked($done)>
                                                <label class="form-check-label fw-semibold" for="done{{ $row->id }}">Mark as completed</label>
                                            </div>
                                            <label class="form-label">Comment</label>
                                            <textarea name="comment" class="form-control mb-2" rows="3"
                                                      placeholder="Add notes…">{{ $row->comment }}</textarea>
                                            <label class="form-label">Attach files</label>
                                            <input type="file" name="files[]" class="form-control mb-3" multiple>
                                            <button class="btn add-btn">Save step</button>
                                        </form>

                                        @if($files->isNotEmpty())
                                            <div class="mt-3">
                                                <div class="form-label mb-2">Uploaded files</div>
                                                @foreach($files as $file)
                                                    <div class="ob-file">
                                                        <p class="name">{{ $file->document_name }}</p>
                                                        <div class="d-flex gap-1">
                                                            <a href="{{ $file->url() }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <a href="{{ $file->url() }}" download class="btn btn-sm btn-outline-success">
                                                                <i class="fa fa-download"></i>
                                                            </a>
                                                            <form method="POST" action="{{ route('onboarding.file.destroy', $file) }}"
                                                                  onsubmit="return confirm('Delete this file?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted text-center py-4">No onboarding steps found. Add master steps first.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
@if(($tab ?? 'progress') === 'steps')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    const list = document.getElementById('sortableSteps');
    if (!list || typeof Sortable === 'undefined') return;

    const csrf = @json(csrf_token());
    const reorderUrl = @json(route('onboarding.master.reorder'));
    const pill = document.getElementById('orderSavedPill');

    function renumber() {
        list.querySelectorAll('.step-order-badge').forEach((el, i) => {
            el.textContent = String(i + 1);
        });
    }

    async function persistOrder() {
        const order = [...list.querySelectorAll('.ob-step-card')].map((el) => Number(el.dataset.id));
        try {
            const res = await fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ order }),
            });
            if (!res.ok) throw new Error('fail');
            if (pill) {
                pill.classList.add('show');
                clearTimeout(persistOrder._t);
                persistOrder._t = setTimeout(() => pill.classList.remove('show'), 1800);
            }
        } catch (e) {
            alert('Could not save step order. Please refresh and try again.');
        }
    }

    Sortable.create(list, {
        animation: 160,
        handle: '.ob-drag',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function () {
            renumber();
            persistOrder();
        },
    });
})();
</script>
@if($errors->any() || old('step_name'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('addStepModal');
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
});
</script>
@endif
@else
<script>
document.getElementById('toggleIncomplete')?.addEventListener('click', function () {
    const box = document.getElementById('incompleteSteps');
    if (!box) return;
    const open = box.style.display !== 'none';
    box.style.display = open ? 'none' : 'block';
    this.textContent = open ? 'Show incomplete steps' : 'Hide incomplete steps';
});
</script>
@endif
@endpush
