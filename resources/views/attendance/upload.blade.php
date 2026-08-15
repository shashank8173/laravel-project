@extends('layouts.app')

@section('title', 'Upload Attendance')
@section('heading', 'Upload Attendance')

@section('page_actions')
<a href="{{ route('attendance.all') }}" class="btn btn-outline-secondary me-2">Attendance All (HRM)</a>
<a href="{{ route('attendance.admin') }}" class="btn btn-outline-secondary">Attendance (MN)</a>
@endsection

@section('content')
<div class="alert alert-warning">
    <strong>Biometric XLSX:</strong> same format as legacy machine export (.xlsx). Delete first two sheet tabs before uploading.<br>
    <strong>CSV alternative:</strong>
    <code>attendance_id,employee_name,year,month,in_out1,...,in_out31</code>
</div>

<form method="POST" action="{{ route('attendance.upload.store') }}" enctype="multipart/form-data" class="card-soft p-4">
    @csrf
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Format</label>
            <select name="format" class="form-select" required>
                <option value="xlsx">Biometric XLSX</option>
                <option value="csv">CSV</option>
            </select>
        </div>
        <div class="col-md-5">
            <label class="form-label">File</label>
            <input type="file" name="file" class="form-control" accept=".xlsx,.csv" required>
        </div>
        <div class="col-md-3">
            <button class="btn btn-success w-100">Upload</button>
        </div>
    </div>
</form>
@endsection
