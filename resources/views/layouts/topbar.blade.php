{{-- Modern topbar --}}
@php
    $user = auth()->user();
    $badges = $layoutBadges ?? [];
@endphp
<style>
    #global-preloader {
        position: fixed; inset: 0; background: rgba(247,248,250,.94); z-index: 9999;
        display: flex; justify-content: center; align-items: center;
    }
    .spinner {
        width: 40px; height: 40px; border: 3px solid #E4E7EC; border-top: 3px solid #2563EB;
        border-radius: 50%; animation: spin 1s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .header {
        background: #FFFFFF;
        border-bottom: 1px solid #E5E7EB;
        padding: .55rem 1.15rem; min-height: 70px;
        display: flex; align-items: center; gap: .75rem;
        position: sticky; top: 0; z-index: 1030;
        box-shadow: none;
        color: #172033;
        backdrop-filter: none;
    }
    .header .tb-toggle {
        width: 40px; height: 40px; border-radius: 8px;
        border: 1px solid #E4E7EC; background: #fff; color: #344054;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .12s ease;
    }
    .header .tb-toggle:hover {
        background: #F9FAFB; border-color: #E4E7EC; color: #172033;
        box-shadow: none;
    }
    .header .page-title-box { display: flex; align-items: center; gap: .65rem; min-width: 0; }
    .header .page-title-box .tb-logo {
        width: 32px; height: 32px; border-radius: 8px; object-fit: contain;
        background: #F9FAFB; border: 1px solid #E5E7EB; padding: 2px;
    }
    .header .page-title-box h3 {
        margin: 0; font-size: 1.05rem; color: #172033 !important; font-weight: 700; letter-spacing: -.01em;
    }
    .header .page-title-box .tb-sub {
        display: none;
    }
    .header .user-menu {
        margin-left: auto; display: flex; align-items: center; gap: .45rem;
        list-style: none; padding: 0; margin-bottom: 0;
    }
    .header .user-menu > li { list-style: none; }
    .header .tb-chip {
        display: inline-flex; align-items: center; gap: .4rem;
        padding: .4rem .75rem; border-radius: 8px;
        border: 1px solid #E4E7EC; background: #fff; color: #344054;
        text-decoration: none; font-weight: 600; font-size: .84rem;
        transition: background .12s ease;
    }
    .header .tb-chip:hover { background: #F9FAFB; border-color: #E4E7EC; color: #172033; }
    .header .tb-chip i { color: #667085; }
    .header .tb-icon-btn {
        width: 40px; height: 40px; border-radius: 8px; border: 1px solid #E4E7EC;
        background: #fff; color: #344054; display: inline-flex; align-items: center; justify-content: center;
        position: relative; text-decoration: none; cursor: pointer;
    }
    .header .tb-icon-btn.dropdown-toggle::after { display: none; }
    .header .tb-icon-btn:hover { background: #F9FAFB; color: #172033; }
    .header .tb-user {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .2rem .55rem .2rem .2rem; border-radius: 8px;
        border: 1px solid #E4E7EC; background: #fff; color: #172033;
        text-decoration: none; font-weight: 600; font-size: .86rem;
    }
    .header .theme-menu {
        min-width: 200px; padding: .4rem; border: 1px solid #E4E7EC; border-radius: 10px;
        box-shadow: 0 1px 3px rgba(16,24,40,.08);
        background: #fff !important;
    }
    .header .theme-item {
        display: flex; align-items: center; gap: .65rem; width: 100%;
        border: 0; background: transparent; color: #172033;
        padding: .45rem .55rem; border-radius: 6px; font-weight: 600; font-size: .84rem;
        text-align: left; cursor: pointer;
    }
    .header .theme-item:hover { background: #F9FAFB; }
    .header .theme-item.is-active {
        background: #EFF4FF; color: #172033;
        box-shadow: inset 0 0 0 1px #BFDBFE;
    }
    .header .theme-swatch {
        width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;
        border: 1px solid rgba(0,0,0,.12);
    }
    .header .theme-swatch.light { background: #F7F8FA; }
    .header .theme-swatch.dark { background: #243044; }
    .header .theme-swatch.dark-blue { background: #123a66; }
    .header .theme-check { margin-left: auto; color: #2563EB; opacity: 0; }
    .header .theme-item.is-active .theme-check { opacity: 1; }
    #global-preloader { background: color-mix(in srgb, var(--bg) 92%, transparent); }
    html[data-theme="dark"] #global-preloader,
    html[data-theme="dark-blue"] #global-preloader { background: rgba(8,12,20,.92); }
    .clock-container {
        font-size: .8rem; padding: .4rem .7rem; border-radius: 8px;
        background: #F9FAFB;
        box-shadow: none; color: #344054; font-weight: 650;
        font-variant-numeric: tabular-nums; letter-spacing: .01em;
        border: 1px solid #E4E7EC;
    }
    .chat-unread-badge {
        display: none; min-width: 16px; height: 16px; padding: 0 4px; border-radius: 6px;
        background: #F04438; color: #fff; font-size: 10px; font-weight: 700; line-height: 16px; text-align: center;
    }
    .chat-unread-badge.show { display: inline-block; animation: none; }
    .header .tb-chip.chat-has-unread {
        background: #EFF4FF; border-color: #BFDBFE; box-shadow: none;
        animation: none;
    }
    #chatToastStack {
        position: fixed; top: 78px; right: 16px; z-index: 1080;
        display: flex; flex-direction: column; gap: .5rem; max-width: min(360px, calc(100vw - 24px));
        pointer-events: none;
    }
    .chat-toast {
        pointer-events: auto; display: flex; gap: .65rem; align-items: flex-start;
        background: #fff; color: #172033; border-radius: 10px; padding: .7rem .8rem;
        box-shadow: 0 2px 8px rgba(16,24,40,.08); border: 1px solid #E4E7EC; border-left: 3px solid #2563EB;
        text-decoration: none; animation: chatToastIn .2s ease;
    }
    .chat-toast img {
        width: 36px; height: 36px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
        border: 1px solid #E4E7EC; background: #F9FAFB;
    }
    .chat-toast .body { min-width: 0; flex: 1; }
    .chat-toast .body .label { font-size: .66rem; color: #667085; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
    .chat-toast .body .from { font-weight: 700; font-size: .88rem; margin: .1rem 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #172033; }
    .chat-toast .body .preview { font-size: .8rem; color: #667085; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-toast .close-toast {
        border: 0; background: transparent; color: #667085; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0;
    }
    @keyframes chatToastIn {
        from { transform: translateY(-6px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .header .tb-icon-btn .badge.rounded-pill {
        position: absolute; top: -4px; right: -4px; background: #F04438; font-size: .65rem;
        min-width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px !important;
    }
    .header .user-img img {
        width: 32px; height: 32px; border-radius: 50%; object-fit: cover;
        border: 1px solid #E4E7EC;
    }
    .header .tb-user:hover { background: #F9FAFB; border-color: #E4E7EC; color: #172033; }
    .header .tb-user-name { color: #172033; }
    .noti-content { max-height: 320px; overflow-y: auto; min-width: 300px; }
    .notifications { min-width: 320px; border: 1px solid #E4E7EC; border-radius: 10px; box-shadow: 0 1px 3px rgba(16,24,40,.08); }
    .hrm-notify-item { cursor: pointer; border-radius: 8px; transition: background .12s ease; }
    .hrm-notify-item:hover { background: #f8fafc; }
    .hrm-notify-item.is-unread { background: #fff7ed; }
    .hrm-notify-item.is-unread:hover { background: #ffedd5; }
    @media (max-width: 767px) {
        .header .page-title-box .tb-sub,
        .header .tb-chip-text,
        .header .tb-user-name { display: none; }
        .clock-container { display: none; }
        .header { min-height: 60px; padding: .45rem .75rem; }
    }

    /* Dark themes: keep contrasted controls using theme tokens */
    html[data-theme="dark"] .header,
    html[data-theme="dark-blue"] .header {
        background: var(--topbar-bg) !important;
        border-bottom: 1px solid var(--border) !important;
        color: var(--topbar-text) !important;
    }
    html[data-theme="dark"] .header .tb-toggle,
    html[data-theme="dark"] .header .tb-chip,
    html[data-theme="dark"] .header .tb-icon-btn,
    html[data-theme="dark"] .header .tb-user,
    html[data-theme="dark"] .clock-container,
    html[data-theme="dark-blue"] .header .tb-toggle,
    html[data-theme="dark-blue"] .header .tb-chip,
    html[data-theme="dark-blue"] .header .tb-icon-btn,
    html[data-theme="dark-blue"] .header .tb-user,
    html[data-theme="dark-blue"] .clock-container {
        background: var(--card) !important;
        border-color: var(--border) !important;
        color: var(--text) !important;
    }
    html[data-theme="dark"] .header .page-title-box h3,
    html[data-theme="dark"] .header .tb-user-name,
    html[data-theme="dark-blue"] .header .page-title-box h3,
    html[data-theme="dark-blue"] .header .tb-user-name { color: var(--text) !important; }
    html[data-theme="dark"] .header .tb-chip i,
    html[data-theme="dark"] .header .tb-icon-btn i,
    html[data-theme="dark"] .header .tb-toggle i,
    html[data-theme="dark-blue"] .header .tb-chip i,
    html[data-theme="dark-blue"] .header .tb-icon-btn i,
    html[data-theme="dark-blue"] .header .tb-toggle i { color: var(--icon) !important; }
    html[data-theme="dark"] .header .theme-menu,
    html[data-theme="dark"] .header .notifications,
    html[data-theme="dark"] .header .dropdown-menu,
    html[data-theme="dark-blue"] .header .theme-menu,
    html[data-theme="dark-blue"] .header .notifications,
    html[data-theme="dark-blue"] .header .dropdown-menu {
        background: var(--soft) !important;
        border: 1px solid var(--border) !important;
        color: var(--text) !important;
    }
    html[data-theme="dark"] .header .theme-item,
    html[data-theme="dark-blue"] .header .theme-item { color: var(--text) !important; }
    html[data-theme="dark"] .header .theme-item:hover,
    html[data-theme="dark"] .header .theme-item.is-active,
    html[data-theme="dark-blue"] .header .theme-item:hover,
    html[data-theme="dark-blue"] .header .theme-item.is-active {
        background: color-mix(in srgb, var(--primary) 16%, transparent) !important;
        color: var(--text) !important;
    }
</style>

<div id="global-preloader"><div class="spinner"></div></div>
<div id="chatToastStack" aria-live="polite"></div>

<div class="header">
    <button class="tb-toggle" type="button" id="toggle_btn" title="Collapse / expand sidebar" aria-expanded="true">
        <i class="fa-solid fa-bars-staggered"></i>
    </button>

    <div class="page-title-box">
        <img class="tb-logo" src="{{ $appLogoUrl ?? asset('assets/img/logo2.png') }}" alt="Logo" onerror="this.style.display='none'">
        <div>
            <h3>Leadforgrow</h3>
            <span class="tb-sub">HRM workspace</span>
        </div>
    </div>

    <ul class="nav user-menu">
        <li>
            <a href="{{ route('chat.index') }}" class="tb-chip" id="chatRoomLink" title="Chat Room">
                <i class="fa-solid fa-comments"></i>
                <span class="tb-chip-text">Chat Room</span>
                <span id="chatUnreadBadge" class="chat-unread-badge {{ ($badges['chat_unread'] ?? 0) > 0 ? 'show' : '' }}">
                    @if(($badges['chat_unread'] ?? 0) > 0){{ $badges['chat_unread'] }}@endif
                </span>
            </a>
        </li>
        <li>
            <div class="clock-container"><span id="clock"></span></div>
        </li>
        <li class="nav-item dropdown">
            <button type="button" class="tb-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                    title="Theme" id="themeToggleBtn">
                <i class="fa-solid fa-moon" id="themeToggleIcon"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end theme-menu" id="themeMenu">
                <div class="px-2 pt-1 pb-2 small text-muted fw-semibold">Theme</div>
                <button type="button" class="theme-item" data-theme="light">
                    <span class="theme-swatch light"></span>
                    <span>White</span>
                    <i class="fa-solid fa-check theme-check"></i>
                </button>
                <button type="button" class="theme-item" data-theme="dark">
                    <span class="theme-swatch dark"></span>
                    <span>Dark</span>
                    <i class="fa-solid fa-check theme-check"></i>
                </button>
                <button type="button" class="theme-item" data-theme="dark-blue">
                    <span class="theme-swatch dark-blue"></span>
                    <span>Dark blue</span>
                    <i class="fa-solid fa-check theme-check"></i>
                </button>
            </div>
        </li>
        <li class="nav-item dropdown" id="hrmNotifyBell">
            <a href="#" class="tb-icon-btn dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="hrmNotifyToggle">
                <i class="fa-regular fa-bell"></i>
                <span class="badge rounded-pill" id="hrmNotifyBadge" @if(($badges['notifications'] ?? 0) <= 0) style="display:none" @endif>
                    {{ ($badges['notifications'] ?? 0) > 99 ? '99+' : ($badges['notifications'] ?? 0) }}
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-end notifications p-0">
                <div class="topnav-dropdown-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <span class="notification-title fw-semibold">Notifications</span>
                    <button type="button" class="btn btn-link btn-sm p-0" id="hrmNotifyReadAll">Mark all read</button>
                </div>
                <div class="noti-content p-2" style="max-height:360px;overflow:auto;">
                    <ul class="list-unstyled mb-0" id="hrmNotifyList">
                        @forelse($layoutNotifications ?? [] as $n)
                            <li class="px-2 py-2 border-bottom hrm-notify-item {{ $n->isUnread() ? 'is-unread' : '' }}"
                                data-id="{{ $n->id }}"
                                @if($n->link) data-link="{{ $n->link }}" @endif>
                                <div class="fw-semibold" style="font-size:.88rem;line-height:1.3;">{{ $n->title }}</div>
                                @if($n->body)
                                    <div class="small text-muted">{{ \Illuminate\Support\Str::limit($n->body, 70) }}</div>
                                @endif
                                <div class="small text-muted">{{ optional($n->created_at)->diffForHumans() }}</div>
                            </li>
                        @empty
                            <li class="px-2 py-3 text-muted small" id="hrmNotifyEmpty">No notifications</li>
                        @endforelse
                    </ul>
                </div>
                <div class="topnav-dropdown-footer px-3 py-2 border-top d-flex justify-content-between">
                    <a href="{{ route('notifications.inbox') }}">View all</a>
                    <a href="{{ route('settings.notifications') }}">Sound settings</a>
                </div>
            </div>
        </li>
        <li class="nav-item dropdown">
            <a href="#" class="tb-user dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="user-img"><img src="{{ $user?->profile_image_url }}" alt="User"></span>
                <span class="tb-user-name">{{ $user?->fname }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-end" style="border:0;border-radius:14px;box-shadow:0 16px 40px rgba(15,39,68,.14);">
                <a class="dropdown-item" href="{{ route('profile.show') }}">My Profile</a>
                @if($user?->isAdmin())
                    <a class="dropdown-item" href="{{ route('dashboard.admin') }}">Admin Dashboard</a>
                    <a class="dropdown-item" href="{{ route('dashboard.employee') }}">User Dashboard</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="dropdown-item text-danger">Logout</button>
                </form>
            </div>
        </li>
    </ul>
</div>

<script>
function updateClock() {
    const options = { timeZone: 'Asia/Kolkata', hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' };
    const el = document.getElementById('clock');
    if (el) el.textContent = new Intl.DateTimeFormat('en-US', options).format(new Date());
}
setInterval(updateClock, 1000);
updateClock();

window.addEventListener('load', function () {
    const preloader = document.getElementById('global-preloader');
    if (preloader) preloader.style.display = 'none';
});

(function () {
    const themeUrl = @json(route('theme.update'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const serverTheme = @json($user?->ui_theme ?: 'light');
    const allowed = ['light', 'dark', 'dark-blue'];

    function normalize(theme) {
        return allowed.includes(theme) ? theme : 'light';
    }

    function iconFor(theme) {
        if (theme === 'light') return 'fa-solid fa-sun';
        if (theme === 'dark-blue') return 'fa-solid fa-cloud-moon';
        return 'fa-solid fa-moon';
    }

    function applyTheme(theme, persistLocal) {
        theme = normalize(theme);
        document.documentElement.setAttribute('data-theme', theme);
        if (persistLocal) {
            try { localStorage.setItem('hrm_ui_theme', theme); } catch (e) {}
        }
        const icon = document.getElementById('themeToggleIcon');
        if (icon) icon.className = iconFor(theme);
        document.querySelectorAll('#themeMenu .theme-item').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-theme') === theme);
        });
    }

    // Server preference is source of truth; keep localStorage in sync for fast paint
    let current = normalize(serverTheme || 'light');
    try {
        const local = localStorage.getItem('hrm_ui_theme');
        if (allowed.includes(local) && !serverTheme) {
            current = local;
        }
    } catch (e) {}
    applyTheme(current, true);

    document.querySelectorAll('#themeMenu .theme-item').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const theme = normalize(this.getAttribute('data-theme'));
            applyTheme(theme, true);
            fetch(themeUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ theme: theme }),
                credentials: 'same-origin',
            }).catch(function () {});
        });
    });
})();

(function () {
    const chatUrl = @json(route('chat.index'));
    const unreadUrl = @json(route('chat.unread'));
    const defaultAv = @json(asset('assets/img/profiles/avatar-02.jpg'));
    let lastCount = {{ (int) ($badges['chat_unread'] ?? 0) }};
    let lastMessageId = 0;
    let audioCtx = null;

    function playPing() {
        try {
            const Ctx = window.AudioContext || window.webkitAudioContext;
            if (!Ctx) return;
            if (!audioCtx) audioCtx = new Ctx();
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const o = audioCtx.createOscillator();
            const g = audioCtx.createGain();
            o.type = 'sine';
            o.frequency.value = 880;
            g.gain.value = 0.0001;
            o.connect(g);
            g.connect(audioCtx.destination);
            const now = audioCtx.currentTime;
            g.gain.exponentialRampToValueAtTime(0.08, now + 0.02);
            g.gain.exponentialRampToValueAtTime(0.0001, now + 0.25);
            o.start(now);
            o.stop(now + 0.28);
        } catch (e) {}
    }

    function updateChatUnreadBadge(count) {
        const badge = document.getElementById('chatUnreadBadge');
        const link = document.getElementById('chatRoomLink');
        const n = parseInt(count, 10) || 0;
        if (badge) {
            if (n > 0) {
                badge.textContent = n > 99 ? '99+' : String(n);
                badge.classList.add('show');
            } else {
                badge.textContent = '';
                badge.classList.remove('show');
            }
        }
        if (link) {
            link.classList.toggle('chat-has-unread', n > 0);
            link.title = n > 0 ? ('Chat Room (' + n + ' new)') : 'Chat Room';
        }
        const base = document.title.replace(/^\(\d+\)\s*/, '');
        if (!window.location.pathname.includes('/chat')) {
            document.title = n > 0 ? ('(' + n + ') ' + base) : base;
        }
    }

    function showChatToast(latest) {
        if (!latest) return;
        const stack = document.getElementById('chatToastStack');
        if (!stack) return;

        const a = document.createElement('a');
        a.href = chatUrl;
        a.className = 'chat-toast';
        a.innerHTML =
            '<img src="' + (latest.image || defaultAv) + '" alt="" onerror="this.src=\'' + defaultAv + '\'">' +
            '<div class="body">' +
                '<div class="label">New chat message</div>' +
                '<div class="from"></div>' +
                '<div class="preview"></div>' +
            '</div>' +
            '<button type="button" class="close-toast" aria-label="Close">&times;</button>';
        a.querySelector('.from').textContent = latest.from || latest.chat_name || 'New message';
        a.querySelector('.preview').textContent = latest.preview || 'Open chat';
        a.querySelector('.close-toast').addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            a.remove();
        });
        stack.prepend(a);
        setTimeout(function () { a.remove(); }, 7000);
        while (stack.children.length > 3) stack.lastElementChild.remove();
    }

    function fetchChatUnreadCount() {
        fetch(unreadUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const n = parseInt(data.count ?? data.unread_count ?? 0, 10) || 0;
                const latest = data.latest || null;
                const msgId = latest ? parseInt(latest.message_id, 10) || 0 : 0;
                const isNew = (n > lastCount) || (msgId > 0 && msgId > lastMessageId && n > 0);

                updateChatUnreadBadge(n);

                if (isNew && n > 0) {
                    showChatToast(latest);
                    playPing();
                }

                lastCount = n;
                if (msgId > lastMessageId) lastMessageId = msgId;
            })
            .catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateChatUnreadBadge(lastCount);
        fetchChatUnreadCount();
        setInterval(fetchChatUnreadCount, 4000);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') fetchChatUnreadCount();
        });
    });

    window.updateChatUnreadBadge = updateChatUnreadBadge;
    window.fetchChatUnreadCount = fetchChatUnreadCount;
})();
</script>
