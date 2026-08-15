@extends('layouts.app')

@section('title', 'Greeting Cards')
@section('heading', 'Birthday, Anniversary & Holiday Cards')

@section('page_actions')
<button type="button" class="btn btn-notice" data-bs-toggle="modal" data-bs-target="#greetingNoticeModal">
    <i class="fa-solid fa-circle-info me-1"></i> Notice
</button>
@endsection

@push('styles')
<style>
    .gr-wrap { --ink:#0f2744; --muted:#6b7c93; --line:#e8eef5; --soft:#f4f7fb; --accent:#ff9b44; }
    .gr-wrap .gr-panel { background:#fff; border:1px solid var(--line); border-radius:18px; overflow:hidden; margin-bottom:1rem; }
    .gr-wrap .gr-panel-head {
        display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;
        padding:1rem 1.15rem; border-bottom:1px solid var(--line);
        background:linear-gradient(180deg,#fff,#fafbfd);
    }
    .gr-wrap .gr-panel-head h5 { margin:0; font-weight:750; color:var(--ink); font-size:1rem; }
    .gr-wrap .gr-panel-head .sub { font-size:.78rem; color:var(--muted); display:block; margin-top:.15rem; }
    .gr-wrap .gr-panel-body { padding:1.15rem; }
    .gr-wrap .gr-panel-foot {
        padding:.85rem 1.15rem; border-top:1px solid var(--line); background:#fafbfd;
        display:flex; justify-content:flex-end;
    }
    .gr-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--muted); }
    .gr-wrap .help { font-size:.75rem; color:var(--muted); margin-top:.25rem; }
    .gr-wrap .btn-save {
        background:var(--accent); border-color:var(--accent); color:#fff; font-weight:700; border-radius:10px; padding:.55rem 1.1rem;
    }
    .gr-wrap .btn-save:hover { filter:brightness(.96); color:#fff; }
    .gr-wrap .img-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:.85rem; }
    .gr-wrap .img-card {
        border:1px solid var(--line); border-radius:14px; overflow:hidden; background:#fff; position:relative;
    }
    .gr-wrap .img-card img { width:100%; height:130px; object-fit:cover; display:block; background:var(--soft); }
    .gr-wrap .img-card .meta { padding:.65rem .75rem; }
    .gr-wrap .img-card .meta .t { font-size:.78rem; font-weight:700; color:var(--ink); margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .gr-wrap .img-card .actions { display:flex; gap:.35rem; margin-top:.45rem; }
    .gr-wrap .img-card .actions .btn { font-size:.72rem; padding:.25rem .5rem; }
    .gr-wrap .badge-off { background:#fee2e2; color:#b91c1c; }
    .gr-wrap .badge-on { background:#dcfce7; color:#15803d; }
    .gr-wrap .form-check-input:checked { background-color:var(--accent); border-color:var(--accent); }
    .btn-notice {
        border:1px solid #f59e0b; color:#92400e; background:#fffbeb; font-weight:700; border-radius:999px;
        padding:.35rem .9rem; font-size:.8rem;
    }
    .btn-notice:hover { background:#fef3c7; color:#78350f; border-color:#d97706; }
    .gr-notice-body p { margin:0 0 .75rem; color:#0f2744; font-size:.92rem; }
    .gr-notice-body ul { margin:0 0 .75rem; padding-left:1.1rem; color:#4b5c73; font-size:.9rem; }
    .gr-notice-body li { margin-bottom:.4rem; }
    .gr-notice-body code { background:#f4f7fb; padding:.1rem .35rem; border-radius:6px; color:#0f2744; }
</style>
@endpush

@section('content')
<div class="gr-wrap">
    <form method="POST" action="{{ route('settings.greetings.update') }}" class="gr-panel">
        @csrf
        @method('PUT')
        <div class="gr-panel-head">
            <div>
                <h5><i class="fa-solid fa-cake-candles me-1" style="color:var(--accent)"></i> Birthday greeting text</h5>
                <span class="sub">Personal card to the employee + team reminder to everyone</span>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" id="birthday_enabled" name="birthday_enabled" value="1" @checked($settings->birthday_enabled)>
                <label class="form-check-label" for="birthday_enabled">Enabled</label>
            </div>
        </div>
        <div class="gr-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Personal subject</label>
                    <input type="text" name="birthday_subject" class="form-control" value="{{ old('birthday_subject', $settings->birthday_subject) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Personal heading</label>
                    <input type="text" name="birthday_heading" class="form-control" value="{{ old('birthday_heading', $settings->birthday_heading) }}" required>
                    <div class="help">Example: Happy Birthday, {name}!</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Personal message</label>
                    <textarea name="birthday_message" class="form-control" rows="3">{{ old('birthday_message', $settings->birthday_message) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Footer</label>
                    <input type="text" name="birthday_footer" class="form-control" value="{{ old('birthday_footer', $settings->birthday_footer) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team alert subject</label>
                    <input type="text" name="birthday_alert_subject" class="form-control" value="{{ old('birthday_alert_subject', $settings->birthday_alert_subject) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team alert heading</label>
                    <input type="text" name="birthday_alert_heading" class="form-control" value="{{ old('birthday_alert_heading', $settings->birthday_alert_heading) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team alert message</label>
                    <textarea name="birthday_alert_message" class="form-control" rows="3">{{ old('birthday_alert_message', $settings->birthday_alert_message) }}</textarea>
                    <div class="help">Use {name} and {email} — filled automatically.</div>
                </div>
            </div>
        </div>

        <div class="gr-panel-head" style="border-top:1px solid var(--line);">
            <div>
                <h5><i class="fa-solid fa-award me-1" style="color:#16a34a"></i> Work anniversary greeting text</h5>
                <span class="sub">Personal card to the employee + team reminder to everyone</span>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" id="anniversary_enabled" name="anniversary_enabled" value="1" @checked($settings->anniversary_enabled)>
                <label class="form-check-label" for="anniversary_enabled">Enabled</label>
            </div>
        </div>
        <div class="gr-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Personal subject</label>
                    <input type="text" name="anniversary_subject" class="form-control" value="{{ old('anniversary_subject', $settings->anniversary_subject) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Personal heading</label>
                    <input type="text" name="anniversary_heading" class="form-control" value="{{ old('anniversary_heading', $settings->anniversary_heading) }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Personal message</label>
                    <textarea name="anniversary_message" class="form-control" rows="3">{{ old('anniversary_message', $settings->anniversary_message) }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Footer</label>
                    <input type="text" name="anniversary_footer" class="form-control" value="{{ old('anniversary_footer', $settings->anniversary_footer) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team alert subject</label>
                    <input type="text" name="anniversary_alert_subject" class="form-control" value="{{ old('anniversary_alert_subject', $settings->anniversary_alert_subject) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team alert heading</label>
                    <input type="text" name="anniversary_alert_heading" class="form-control" value="{{ old('anniversary_alert_heading', $settings->anniversary_alert_heading) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Team alert message</label>
                    <textarea name="anniversary_alert_message" class="form-control" rows="3">{{ old('anniversary_alert_message', $settings->anniversary_alert_message) }}</textarea>
                </div>
            </div>
        </div>

        <div class="gr-panel-head" style="border-top:1px solid var(--line);">
            <div>
                <h5><i class="fa-solid fa-snowflake me-1" style="color:#0ea5e9"></i> Holiday greeting text</h5>
                <span class="sub">Auto email to all official mails on holiday date (from Holidays page) · use {holiday}</span>
            </div>
            <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" role="switch" id="holiday_enabled" name="holiday_enabled" value="1" @checked($settings->holiday_enabled ?? true)>
                <label class="form-check-label" for="holiday_enabled">Enabled</label>
            </div>
        </div>
        <div class="gr-panel-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Subject</label>
                    <input type="text" name="holiday_subject" class="form-control" value="{{ old('holiday_subject', $settings->holiday_subject ?? 'Happy {holiday}!') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Heading</label>
                    <input type="text" name="holiday_heading" class="form-control" value="{{ old('holiday_heading', $settings->holiday_heading ?? 'Happy {holiday}!') }}" required>
                    <div class="help">Example: Happy {holiday}!</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Message</label>
                    <textarea name="holiday_message" class="form-control" rows="3">{{ old('holiday_message', $settings->holiday_message ?? 'Wishing you and your loved ones a wonderful celebration!') }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Footer</label>
                    <input type="text" name="holiday_footer" class="form-control" value="{{ old('holiday_footer', $settings->holiday_footer ?? 'Best wishes from the HR Team!') }}">
                </div>
            </div>
        </div>
        <div class="gr-panel-foot">
            <button type="submit" class="btn btn-save"><i class="fa-solid fa-floppy-disk me-1"></i> Save greeting text</button>
        </div>
    </form>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="gr-panel">
                <div class="gr-panel-head">
                    <div>
                        <h5>Birthday card images</h5>
                        <span class="sub">One random active image is used per email</span>
                    </div>
                </div>
                <div class="gr-panel-body">
                    <form method="POST" action="{{ route('settings.greetings.images.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                        @csrf
                        <input type="hidden" name="type" value="birthday">
                        <div class="col-12">
                            <label class="form-label">Title (optional)</label>
                            <input type="text" name="title" class="form-control" placeholder="Cake card">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-save w-100">Upload</button>
                        </div>
                    </form>
                    <div class="img-grid">
                        @forelse($birthdayImages as $img)
                            <div class="img-card">
                                <img src="{{ $img->url() }}" alt="{{ $img->title ?: 'Birthday card' }}">
                                <div class="meta">
                                    <p class="t">{{ $img->title ?: 'Untitled' }}</p>
                                    <span class="badge {{ $img->is_active ? 'badge-on' : 'badge-off' }}">{{ $img->is_active ? 'Active' : 'Off' }}</span>
                                    <div class="actions">
                                        <form method="POST" action="{{ route('settings.greetings.images.toggle', $img) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">{{ $img->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('settings.greetings.images.destroy', $img) }}" onsubmit="return confirm('Delete this image?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">No birthday images yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="gr-panel">
                <div class="gr-panel-head">
                    <div>
                        <h5>Anniversary card images</h5>
                        <span class="sub">One random active image is used per email</span>
                    </div>
                </div>
                <div class="gr-panel-body">
                    <form method="POST" action="{{ route('settings.greetings.images.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                        @csrf
                        <input type="hidden" name="type" value="anniversary">
                        <div class="col-12">
                            <label class="form-label">Title (optional)</label>
                            <input type="text" name="title" class="form-control" placeholder="Trophy card">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-save w-100">Upload</button>
                        </div>
                    </form>
                    <div class="img-grid">
                        @forelse($anniversaryImages as $img)
                            <div class="img-card">
                                <img src="{{ $img->url() }}" alt="{{ $img->title ?: 'Anniversary card' }}">
                                <div class="meta">
                                    <p class="t">{{ $img->title ?: 'Untitled' }}</p>
                                    <span class="badge {{ $img->is_active ? 'badge-on' : 'badge-off' }}">{{ $img->is_active ? 'Active' : 'Off' }}</span>
                                    <div class="actions">
                                        <form method="POST" action="{{ route('settings.greetings.images.toggle', $img) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">{{ $img->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('settings.greetings.images.destroy', $img) }}" onsubmit="return confirm('Delete this image?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">No anniversary images yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="gr-panel">
                <div class="gr-panel-head">
                    <div>
                        <h5>Holiday card images</h5>
                        <span class="sub">Used for automatic holiday emails</span>
                    </div>
                </div>
                <div class="gr-panel-body">
                    <form method="POST" action="{{ route('settings.greetings.images.store') }}" enctype="multipart/form-data" class="row g-2 align-items-end mb-3">
                        @csrf
                        <input type="hidden" name="type" value="holiday">
                        <div class="col-12">
                            <label class="form-label">Title (optional)</label>
                            <input type="text" name="title" class="form-control" placeholder="Festival card">
                        </div>
                        <div class="col-8">
                            <label class="form-label">Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-save w-100">Upload</button>
                        </div>
                    </form>
                    <div class="img-grid">
                        @forelse($holidayImages as $img)
                            <div class="img-card">
                                <img src="{{ $img->url() }}" alt="{{ $img->title ?: 'Holiday card' }}">
                                <div class="meta">
                                    <p class="t">{{ $img->title ?: 'Untitled' }}</p>
                                    <span class="badge {{ $img->is_active ? 'badge-on' : 'badge-off' }}">{{ $img->is_active ? 'Active' : 'Off' }}</span>
                                    <div class="actions">
                                        <form method="POST" action="{{ route('settings.greetings.images.toggle', $img) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-outline-secondary btn-sm" type="submit">{{ $img->is_active ? 'Disable' : 'Enable' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('settings.greetings.images.destroy', $img) }}" onsubmit="return confirm('Delete this image?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger btn-sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">No holiday images yet. Upload at least one.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="greetingNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:0;border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold mb-0">
                    <i class="fa-solid fa-circle-info me-1" style="color:#f59e0b;"></i> Notice — Greeting Cards
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body gr-notice-body">
                <p><strong>What this page controls</strong></p>
                <p>Configure the email cards sent automatically for birthdays, work anniversaries, and holidays. Text templates and card images are managed here.</p>

                <p><strong>Auto-fill placeholders</strong></p>
                <ul>
                    <li><code>{name}</code> — employee full name from HRM (do not type real names)</li>
                    <li><code>{email}</code> — employee official email (<code>office_email</code>)</li>
                    <li><code>{holiday}</code> — holiday name from the Holidays page</li>
                </ul>

                <p><strong>How emails are sent</strong></p>
                <ul>
                    <li><strong>Birthday / anniversary:</strong> personal card to the employee, plus a team reminder to all official emails</li>
                    <li><strong>Holiday:</strong> greeting card to all employees’ official emails on that holiday date</li>
                    <li>Card images: one random <em>active</em> image of that type is attached in the email body</li>
                </ul>

                <p><strong>Related pages</strong></p>
                <ul>
                    <li>Holidays list: <a href="{{ route('holidays.index') }}">Holidays</a></li>
                    <li>Manual test / cron URLs: <a href="{{ route('developer.cron') }}">Developer → Cron Jobs</a></li>
                </ul>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
