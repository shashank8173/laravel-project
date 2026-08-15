@extends('layouts.app')

@section('title', 'Harassment Admin')
@section('heading', 'Harassment Complaints')

@section('content')
<div class="card-soft p-3">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>ID</th>
                <th>Complainant</th>
                <th>Contact</th>
                <th>Incident Date</th>
                <th>Location</th>
                <th>Harasser</th>
                <th>Submitted</th>
                <th>Evidence</th>
            </tr>
            </thead>
            <tbody>
            @forelse($complaints as $complaint)
                <tr>
                    <td>{{ $complaint->id }}</td>
                    <td>
                        {{ $complaint->complainant_name }}
                        <div class="small text-muted">{{ $complaint->complainant_department }} · {{ $complaint->complainant_designation }}</div>
                    </td>
                    <td>{{ $complaint->complainant_contact }}</td>
                    <td>{{ $complaint->incident_date }}</td>
                    <td>{{ $complaint->incident_location }}</td>
                    <td class="small">{{ \Illuminate\Support\Str::limit($complaint->harasser_details, 40) }}</td>
                    <td>{{ optional($complaint->submission_date)->format('d M Y H:i') ?? '—' }}</td>
                    <td>
                        @if($complaint->evidence_path)
                            <a href="{{ asset('storage/'.$complaint->evidence_path) }}" target="_blank" rel="noopener">View</a>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-muted">No complaints found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $complaints->links() }}</div>
</div>
@endsection
