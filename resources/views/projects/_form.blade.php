@php
    $p = $project;
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Project Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $p?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Client Name</label>
        <input type="text" name="client_name" class="form-control @error('client_name') is-invalid @enderror"
               value="{{ old('client_name', $p?->client_name) }}">
        @error('client_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" rows="4" class="form-control js-rich-editor @error('description') is-invalid @enderror"
                  placeholder="Click to write project description…">{{ old('description', $p?->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Project Manager <span class="text-danger">*</span></label>
        <x-employee-select
            name="project_manager_id"
            :employees="$employees"
            :selected="old('project_manager_id', $p?->project_manager_id)"
            :required="true"
            class="form-select @error('project_manager_id') is-invalid @enderror"
            placeholder="Search manager by name, department or designation…"
        />
        @error('project_manager_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Start Date <span class="text-danger">*</span></label>
        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
               value="{{ old('start_date', optional($p?->start_date)->format('Y-m-d')) }}" required>
        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">End Date / Deadline <span class="text-danger">*</span></label>
        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror"
               value="{{ old('end_date', optional($p?->end_date)->format('Y-m-d')) }}" required>
        @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Priority <span class="text-danger">*</span></label>
        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
            @foreach($priorities as $pr)
                <option value="{{ $pr }}" @selected(old('priority', $p?->priority ?? 'medium') === $pr)>{{ ucfirst($pr) }}</option>
            @endforeach
        </select>
        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach($statuses as $st)
                <option value="{{ $st }}" @selected(old('status', $p?->status ?? 'planning') === $st)>{{ ucwords(str_replace('_',' ',$st)) }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">Progress (0–100)</label>
        <input type="number" name="progress" min="0" max="100" class="form-control @error('progress') is-invalid @enderror"
               value="{{ old('progress', $p?->progress ?? 0) }}">
        @error('progress')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if(!empty($includeTeam))
        <div class="col-12">
            <label class="form-label">Assign Team (optional)</label>
            <x-employee-select
                name="team_ids[]"
                :employees="$employees"
                :selected="old('team_ids', [])"
                :multiple="true"
                placeholder="Search & select team members…"
            />
            <div class="form-text">Search by name, department or designation. Select multiple from the list.</div>
        </div>
    @endif
</div>
