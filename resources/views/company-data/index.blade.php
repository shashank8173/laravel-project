@extends('layouts.app')

@section('title', 'Company Data')
@section('heading', 'Company Data')

@section('page_actions')
@if($isAdmin)
<a href="#" class="btn add-btn" data-bs-toggle="modal" data-bs-target="#add_document">
    <i class="la la-plus-circle"></i> Add Document
</a>
@endif
@endsection

@push('styles')
<style>
    .cdata-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .cdata-wrap .cdata-panel {
        background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden;
    }
    .cdata-wrap .cdata-panel-head {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .cdata-wrap .cdata-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1.05rem; }
    .cdata-wrap .cdata-panel-body { padding:1rem 1.15rem; }
    .cdata-wrap .cdata-metric {
        border:1px solid var(--line); border-radius:14px; background:#fff; padding:1rem 1.1rem; height:100%;
        position:relative; overflow:hidden;
    }
    .cdata-wrap .cdata-metric::before {
        content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--accent);
    }
    .cdata-wrap .cdata-metric .k {
        font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:var(--muted); font-weight:700;
    }
    .cdata-wrap .cdata-metric .v { font-size:1.55rem; font-weight:800; color:var(--ink); margin:0; line-height:1.1; }
    .cdata-wrap .cdata-card {
        border:1px solid var(--line); border-radius:16px; background:#fff; height:100%;
        overflow:hidden; display:flex; flex-direction:column;
        transition:transform .15s ease, box-shadow .15s ease;
    }
    .cdata-wrap .cdata-card:hover { transform:translateY(-2px); box-shadow:0 10px 24px rgba(15,39,68,.07); }
    .cdata-wrap .cdata-card-head {
        display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        padding:.9rem 1rem; background:linear-gradient(135deg,#0f2744,#1b3b61); color:#fff;
    }
    .cdata-wrap .cdata-icon {
        width:42px; height:42px; border-radius:50%; background:#fff; color:#0f2744;
        display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.85rem;
    }
    .cdata-wrap .cdata-type { margin:0; font-size:.9rem; font-weight:700; }
    .cdata-wrap .cdata-card-body { padding:1rem; flex:1; }
    .cdata-wrap .cdata-card-body .name { font-weight:750; color:var(--ink); margin-bottom:.55rem; }
    .cdata-wrap .cdata-card-body .meta { color:var(--muted); font-size:.85rem; margin:0; }
    .cdata-wrap .cdata-card-actions {
        display:flex; flex-wrap:wrap; gap:.4rem; padding:0 1rem 1rem;
    }
    .cdata-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .cdata-wrap .cdata-chip {
        display:inline-flex; padding:.28rem .65rem; border-radius:999px;
        background:#fff7ed; color:#c2410c; border:1px solid #ffd7b0; font-size:.75rem; font-weight:650;
    }
</style>
@endpush

@section('content')
@php $total = method_exists($documents, 'total') ? $documents->total() : $documents->count(); @endphp
<div class="cdata-wrap">
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="cdata-metric">
                <div class="k">Total documents</div>
                <p class="v">{{ $total }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cdata-metric">
                <div class="k">Document types</div>
                <p class="v">{{ $types->count() }}</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="cdata-metric">
                <div class="k">Access</div>
                <p class="v" style="font-size:1.05rem;padding-top:.35rem;">{{ $isAdmin ? 'Admin · full CRUD' : 'View & download' }}</p>
            </div>
        </div>
    </div>

    <div class="cdata-panel mb-3">
        <div class="cdata-panel-head">
            <h5><i class="fa-solid fa-magnifying-glass me-1" style="color:var(--accent)"></i> Search company data</h5>
        </div>
        <div class="cdata-panel-body">
            <form method="GET" action="{{ route('company-data.index') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Name / type / file</label>
                    <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search documents...">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn add-btn flex-fill">Search</button>
                    @if($q !== '')
                        <a href="{{ route('company-data.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="cdata-panel">
        <div class="cdata-panel-head">
            <h5>Company documents</h5>
            <span class="small text-muted">{{ $total }} record(s)</span>
        </div>
        <div class="cdata-panel-body">
            <div class="row g-3">
                @forelse($documents as $doc)
                    <div class="col-lg-4 col-md-6">
                        <div class="cdata-card">
                            <div class="cdata-card-head">
                                <div class="cdata-icon">{{ $doc->typeInitials() }}</div>
                                <p class="cdata-type">{{ $doc->document_type ?: 'Document' }}</p>
                            </div>
                            <div class="cdata-card-body">
                                <div class="name">{{ $doc->document_name }}</div>
                                <p class="meta">
                                    Updated: {{ $doc->updated_on?->format('d M Y') ?? '—' }}
                                    @if($doc->hasFile())
                                        <br>File: {{ \Illuminate\Support\Str::limit($doc->fileName(), 32) }}
                                    @endif
                                </p>
                            </div>
                            <div class="cdata-card-actions">
                                @if($doc->hasFile())
                                    <a href="{{ route('company-data.download', $doc) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-download"></i> Download
                                    </a>
                                    <a href="{{ route('company-data.download', $doc) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-eye"></i> View
                                    </a>
                                @endif
                                @if($isAdmin)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary edit-doc-btn"
                                            data-bs-toggle="modal"
                                            data-bs-target="#edit_document"
                                            data-name="{{ $doc->document_name }}"
                                            data-type="{{ $doc->document_type }}"
                                            data-updated="{{ optional($doc->updated_on)->format('Y-m-d') }}"
                                            data-file="{{ $doc->fileName() }}"
                                            data-url="{{ route('company-data.update', $doc) }}">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <form method="POST" action="{{ route('company-data.destroy', $doc) }}" class="d-inline"
                                          onsubmit="return confirm('Delete this document?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="text-center text-muted py-5">No company documents found.</div>
                    </div>
                @endforelse
            </div>
            <div class="mt-3">{{ $documents->links() }}</div>
        </div>
    </div>
</div>

@if($isAdmin)
<div class="modal fade" id="add_document" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('company-data.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Document name <span class="text-danger">*</span></label>
                            <input type="text" name="document_name" class="form-control" required value="{{ old('document_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Document type <span class="text-danger">*</span></label>
                            <input type="text" name="document_type" class="form-control" list="doc_types" required value="{{ old('document_type') }}" placeholder="e.g. GST, MSME">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Updated on</label>
                            <input type="date" name="updated_on" class="form-control" value="{{ old('updated_on', now()->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" required>
                            <div class="form-text">PDF / images / docs — max 12MB</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Save Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="edit_document" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="edit_document_form" action="#" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Document name <span class="text-danger">*</span></label>
                            <input type="text" name="document_name" id="edit_doc_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Document type <span class="text-danger">*</span></label>
                            <input type="text" name="document_type" id="edit_doc_type" class="form-control" list="doc_types" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Updated on</label>
                            <input type="date" name="updated_on" id="edit_doc_updated" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Replace file (optional)</label>
                            <input type="file" name="file" class="form-control">
                            <div class="form-text">Current: <span id="edit_doc_file">—</span></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn add-btn">Update Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<datalist id="doc_types">
    @foreach($types as $type)
        <option value="{{ $type }}"></option>
    @endforeach
</datalist>
@endif
@endsection

@if($isAdmin)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.edit-doc-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('edit_document_form').action = this.dataset.url;
            document.getElementById('edit_doc_name').value = this.dataset.name || '';
            document.getElementById('edit_doc_type').value = this.dataset.type || '';
            document.getElementById('edit_doc_updated').value = this.dataset.updated || '';
            document.getElementById('edit_doc_file').textContent = this.dataset.file || '—';
        });
    });
});
</script>
@endpush
@endif
