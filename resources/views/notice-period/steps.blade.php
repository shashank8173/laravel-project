@extends('layouts.app')

@section('title', 'Notice Period Steps')
@section('heading', 'Manage Notice Period Steps')

@section('page_actions')
<a href="{{ route('notice-period.index') }}" class="btn btn-outline-secondary me-2">Back</a>
<button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_step">
    <i class="la la-plus-circle"></i> Add Step
</button>
@endsection

@push('styles')
<style>
    .np-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .np-wrap .np-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; }
    .np-wrap .np-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line); background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .np-wrap .np-panel-head h5 { margin:0; font-weight:750; color:var(--ink); }
    .np-wrap .np-panel-body { padding:1.15rem; }
    .np-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .np-wrap .np-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .np-wrap .np-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .np-wrap .np-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .np-wrap .np-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .np-wrap .np-hint {
        display:flex; align-items:center; gap:.5rem; color:var(--muted); font-size:.85rem; font-weight:600; margin:0;
    }
    .np-wrap .np-step-list { display:flex; flex-direction:column; gap:.65rem; }
    .np-wrap .np-step-card {
        display:grid; grid-template-columns:40px 48px 1fr auto; gap:.85rem; align-items:center;
        border:1px solid var(--line); border-radius:16px; background:#fff; padding:.95rem 1rem;
        transition: box-shadow .15s ease, border-color .15s ease;
        cursor:grab;
    }
    .np-wrap .np-step-card:active { cursor:grabbing; }
    .np-wrap .np-step-card:hover { border-color:#ffd7b0; box-shadow:0 8px 22px rgba(15,39,68,.06); }
    .np-wrap .np-step-card.sortable-ghost {
        opacity:.55; background:#fff8f2; border-style:dashed; border-color:var(--accent);
    }
    .np-wrap .np-step-card.sortable-chosen { box-shadow:0 12px 28px rgba(15,39,68,.12); }
    .np-wrap .np-drag {
        width:32px; height:32px; border-radius:10px; display:grid; place-items:center;
        color:var(--muted); background:var(--soft); border:1px solid var(--line);
    }
    .np-wrap .np-order {
        width:40px; height:40px; border-radius:12px; display:grid; place-items:center;
        font-weight:800; color:#c2410c; background:#fff7ed; border:1px solid #ffd7b0;
    }
    .np-wrap .np-step-card .title { margin:0; font-weight:800; color:var(--ink); font-size:1rem; }
    .np-wrap .np-step-card .desc {
        margin:.2rem 0 0; color:var(--muted); font-size:.85rem; line-height:1.4;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
    }
    .np-wrap .np-step-actions { display:flex; gap:.4rem; flex-wrap:wrap; justify-content:flex-end; }
    .np-wrap .np-save-pill {
        display:none; align-items:center; gap:.4rem; padding:.35rem .75rem; border-radius:999px;
        background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-size:.75rem; font-weight:700;
    }
    .np-wrap .np-save-pill.show { display:inline-flex; }
    .np-wrap .np-empty {
        text-align:center; padding:2.5rem 1rem; color:var(--muted);
        border:1px dashed var(--line); border-radius:16px; background:#fafbfd;
    }
    @media (max-width:767px) {
        .np-wrap .np-step-card { grid-template-columns:32px 1fr; }
        .np-wrap .np-step-card .np-order { display:none; }
        .np-wrap .np-step-actions { grid-column:1 / -1; justify-content:flex-start; }
    }
</style>
@endpush

@section('content')
<div class="np-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="np-metric">
                <div class="k">Total steps</div>
                <p class="v">{{ $steps->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="np-metric">
                <div class="k">Order</div>
                <p class="v" style="font-size:1rem;padding-top:.4rem;">Drag to rearrange</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="np-metric">
                <div class="k">Quick add</div>
                <p class="v" style="font-size:1rem;padding-top:.4rem;">
                    <a href="#" class="text-decoration-none" style="color:#c2410c;font-weight:800;"
                       data-bs-toggle="modal" data-bs-target="#add_step">Open add modal</a>
                </p>
            </div>
        </div>
    </div>

    <div class="np-panel">
        <div class="np-panel-head">
            <div>
                <h5 class="mb-1">Step templates</h5>
                <p class="np-hint"><i class="fa-solid fa-up-down-left-right"></i> Drag cards to change order</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="np-save-pill" id="orderSavedPill"><i class="fa-solid fa-check"></i> Order saved</span>
                <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_step">
                    <i class="fa-solid fa-plus"></i> Add Step
                </button>
            </div>
        </div>
        <div class="np-panel-body">
            @if($steps->isEmpty())
                <div class="np-empty">
                    <div class="mb-2" style="font-size:1.8rem;opacity:.4;"><i class="fa-solid fa-list-check"></i></div>
                    No steps yet.
                    <div class="mt-3">
                        <button type="button" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_step">
                            Add your first step
                        </button>
                    </div>
                </div>
            @else
                <div class="np-step-list" id="sortableSteps">
                    @foreach($steps as $step)
                        <div class="np-step-card" data-id="{{ $step->step_id }}">
                            <div class="np-drag" title="Drag"><i class="fa-solid fa-grip-vertical"></i></div>
                            <div class="np-order step-order-badge">{{ (int) $step->step_order + 1 }}</div>
                            <div>
                                <p class="title">{{ $step->step_name }}</p>
                                <div class="desc hrm-rich-content">{!! $step->description ?: '<em>No description</em>' !!}</div>
                            </div>
                            <div class="np-step-actions">
                                <button type="button"
                                        class="btn btn-sm btn-outline-primary edit-step-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#edit_step"
                                        data-url="{{ route('notice-period-steps.update', $step) }}"
                                        data-name="{{ $step->step_name }}"
                                        data-description="{{ e($step->description) }}">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                                <form method="POST" action="{{ route('notice-period-steps.destroy', $step) }}"
                                      onsubmit="return confirm('Delete this step? Employee progress for this step will also be removed.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger"><i class="fa-regular fa-trash-can"></i></button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="add_step" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" action="{{ route('notice-period-steps.store') }}">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Step</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Step name <span class="text-danger">*</span></label>
                        <input type="text" name="step_name" class="form-control" required value="{{ old('step_name') }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control js-rich-editor" rows="3"
                                  placeholder="Click to write step description…">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Step</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_step" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <form method="POST" id="edit_step_form" action="#">
                @csrf @method('PUT')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Step</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Step name <span class="text-danger">*</span></label>
                        <input type="text" name="step_name" id="edit_step_name" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_step_description" class="form-control js-rich-editor" rows="3"
                                  placeholder="Click to edit step description…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update Step</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-step-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_step_form').action = this.dataset.url;
            document.getElementById('edit_step_name').value = this.dataset.name || '';
            document.getElementById('edit_step_description').value = this.dataset.description || '';
        });
    });

    const list = document.getElementById('sortableSteps');
    if (!list || typeof Sortable === 'undefined') return;

    const csrf = @json(csrf_token());
    const reorderUrl = @json(route('notice-period-steps.reorder'));
    const pill = document.getElementById('orderSavedPill');

    function renumber() {
        list.querySelectorAll('.step-order-badge').forEach(function (el, i) {
            el.textContent = String(i + 1);
        });
    }

    function persistOrder() {
        const order = Array.from(list.querySelectorAll('.np-step-card')).map(function (el) {
            return parseInt(el.dataset.id, 10);
        });

        fetch(reorderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ order: order }),
        }).then(function (r) {
            if (!r.ok) throw new Error('fail');
            return r.json();
        }).then(function () {
            if (pill) {
                pill.classList.add('show');
                clearTimeout(persistOrder._t);
                persistOrder._t = setTimeout(function () { pill.classList.remove('show'); }, 1800);
            }
        }).catch(function () {
            alert('Failed to save order. Please refresh and try again.');
        });
    }

    Sortable.create(list, {
        animation: 160,
        handle: '.np-drag',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function () {
            renumber();
            persistOrder();
        },
    });
});
</script>
@endpush
