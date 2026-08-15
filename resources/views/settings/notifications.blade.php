@extends('layouts.app')

@section('title', 'Notifications')
@section('heading', 'Notifications')

@section('content')
<div class="card-soft p-4" style="max-width:640px">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <p class="text-muted mb-4" style="font-size:.9rem">
        Control alert sounds for chat, calls, and in-app notifications (leave, project, asset, expense).
    </p>

    <form method="POST" action="{{ route('settings.notifications.update') }}">
        @csrf
        @method('PUT')

        <div class="border rounded-3 p-3 mb-3" style="border-color:var(--hrm-border,#e5eaf1)!important;background:var(--hrm-white,#fff)">
            <div class="form-check form-switch d-flex align-items-start gap-2">
                <input type="hidden" name="notify_app_sound" value="0">
                <input class="form-check-input mt-1" type="checkbox" role="switch"
                       id="notify_app_sound" name="notify_app_sound" value="1"
                       {{ $notifyAppSound ? 'checked' : '' }}>
                <div>
                    <label class="form-check-label fw-semibold" for="notify_app_sound">App notification tone</label>
                    <div class="text-muted" style="font-size:.82rem;margin-top:.2rem">
                        Play “tn tn” when leave / project / asset / expense alerts arrive in the bell menu.
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="previewAppSound">
                <i class="fa-solid fa-volume-high me-1"></i> Preview
            </button>
        </div>

        <div class="border rounded-3 p-3 mb-3" style="border-color:var(--hrm-border,#e5eaf1)!important;background:var(--hrm-white,#fff)">
            <div class="form-check form-switch d-flex align-items-start gap-2">
                <input type="hidden" name="notify_chat_sound" value="0">
                <input class="form-check-input mt-1" type="checkbox" role="switch"
                       id="notify_chat_sound" name="notify_chat_sound" value="1"
                       {{ $notifyChatSound ? 'checked' : '' }}>
                <div>
                    <label class="form-check-label fw-semibold" for="notify_chat_sound">Chat message sound</label>
                    <div class="text-muted" style="font-size:.82rem;margin-top:.2rem">
                        Play a short “tn tn” tone when a new chat message arrives.
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="previewChatSound">
                <i class="fa-solid fa-volume-high me-1"></i> Preview
            </button>
        </div>

        <div class="border rounded-3 p-3 mb-4" style="border-color:var(--hrm-border,#e5eaf1)!important;background:var(--hrm-white,#fff)">
            <div class="form-check form-switch d-flex align-items-start gap-2">
                <input type="hidden" name="notify_call_ringtone" value="0">
                <input class="form-check-input mt-1" type="checkbox" role="switch"
                       id="notify_call_ringtone" name="notify_call_ringtone" value="1"
                       {{ $notifyCallRingtone ? 'checked' : '' }}>
                <div>
                    <label class="form-check-label fw-semibold" for="notify_call_ringtone">Call ringtone</label>
                    <div class="text-muted" style="font-size:.82rem;margin-top:.2rem">
                        When on, play a ringtone for incoming calls until you accept or reject.
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="previewCallRingtone">
                <i class="fa-solid fa-phone me-1"></i> Preview ringtone
            </button>
        </div>

        <button type="submit" class="btn add-btn">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save preferences
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function unlock() {
        if (window.HrmNotifySounds) window.HrmNotifySounds.unlock();
    }
    document.addEventListener('click', unlock, { once: true });
    $('#previewAppSound').on('click', function () {
        unlock();
        window.HrmNotifySounds && window.HrmNotifySounds.playMessageTone();
    });
    $('#previewChatSound').on('click', function () {
        unlock();
        window.HrmNotifySounds && window.HrmNotifySounds.playMessageTone();
    });
    $('#previewCallRingtone').on('click', function () {
        unlock();
        if (!window.HrmNotifySounds) return;
        window.HrmNotifySounds.startCallRingtone();
        setTimeout(function () { window.HrmNotifySounds.stopCallRingtone(); }, 3500);
    });
})();
</script>
@endpush
