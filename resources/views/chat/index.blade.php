@extends('layouts.app')

@section('title', 'Chat')
@section('heading', 'Chat')
@section('page_header')
@endsection

@push('styles')
<style>
    .page-wrapper .content.container-fluid { padding-top: .75rem; padding-bottom: .75rem; }
    .wa-shell {
        --ink:#0f2744; --muted:#667781; --line:#e9edef; --soft:#f0f2f5;
        --accent:#ff9b44; --wa-out:#d9fdd3; --wa-in:#fff; --wa-header:#0f2744; --wa-teal:#1faa59;
        --panel-h: calc(100vh - 88px);
        display:grid; grid-template-columns:380px 1fr; height:var(--panel-h); min-height:520px;
        border-radius:18px; overflow:hidden; border:1px solid #dce3ec;
        box-shadow:0 12px 40px rgba(15,39,68,.08); background:#fff;
        font-family:"Segoe UI","Helvetica Neue",Helvetica,Arial,sans-serif;
        position:relative;
    }
    .wa-side, .wa-main { min-height:0; height:100%; }
    .wa-side { display:flex; flex-direction:column; background:#fff; border-right:1px solid var(--line); min-width:0; }
    .wa-side-head {
        background:linear-gradient(135deg,#0f2744 0%,#1a3a5c 100%); color:#fff;
        padding:.85rem 1rem; display:flex; align-items:center; gap:.75rem;
    }
    .wa-side-head .me-av { width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,155,68,.55); }
    .wa-side-head .meta { flex:1; min-width:0; }
    .wa-side-head .meta strong { display:block; font-size:.98rem; }
    .wa-side-head .meta span { font-size:.78rem; opacity:.75; }
    .wa-side-actions { display:flex; gap:.25rem; align-items:center; }
    .wa-side-actions .btn-ico {
        width:36px;height:36px;border:0;border-radius:50%;background:transparent;color:#fff;
        display:inline-flex;align-items:center;justify-content:center;cursor:pointer;
    }
    .wa-side-actions .btn-ico:hover { background:rgba(255,255,255,.12); }
    .badge-unread {
        background:var(--accent); color:#fff; border-radius:999px; min-width:22px; height:22px;
        font-size:.72rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; padding:0 .4rem;
    }
    .wa-search { padding:.65rem .85rem; background:var(--soft); border-bottom:1px solid var(--line); }
    .wa-search-box {
        display:flex; align-items:center; gap:.55rem; background:#fff; border-radius:999px;
        padding:.45rem .85rem; border:1px solid #e4e9f0;
    }
    .wa-search-box i { color:var(--muted); font-size:.85rem; }
    .wa-search-box input { border:0; outline:0; width:100%; background:transparent; font-size:.9rem; color:var(--ink); }
    .wa-tabs { display:flex; gap:.35rem; padding:.55rem .75rem; background:#fff; border-bottom:1px solid var(--line); flex-wrap:wrap; }
    .wa-tab {
        border:0; background:transparent; color:var(--muted); font-size:.8rem; font-weight:600;
        padding:.35rem .75rem; border-radius:999px; cursor:pointer;
    }
    .wa-tab.active { background:rgba(255,155,68,.15); color:#c45f12; }
    .wa-list { flex:1; overflow-y:auto; background:#fff; }
    .wa-sec { padding:.55rem 1rem .25rem; font-size:.72rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; color:#8a97a8; }
    .wa-item {
        display:flex; gap:.75rem; align-items:center; padding:.7rem 1rem; cursor:pointer;
        border-bottom:1px solid #f2f4f7; transition:background .15s ease; position:relative;
    }
    .wa-item:hover { background:#f7f9fc; }
    .wa-item.active { background:#eef5ff; }
    .wa-av-wrap { position:relative; width:48px; height:48px; flex-shrink:0; }
    .wa-av { width:48px; height:48px; border-radius:50%; object-fit:cover; display:block; background:#dfe6ee; }
    .wa-av.group {
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(145deg,#1b466f,#0f2744); color:#fff; font-size:1.1rem;
    }
    .wa-online-dot {
        position:absolute; right:1px; bottom:1px; width:12px; height:12px; border-radius:50%;
        background:#25d366; border:2px solid #fff; display:none;
    }
    .wa-online-dot.on { display:block; }
    .wa-item-body { flex:1; min-width:0; }
    .wa-item-top { display:flex; justify-content:space-between; gap:.5rem; align-items:baseline; }
    .wa-item-top .name { font-weight:600; color:var(--ink); font-size:.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wa-item-top .time { font-size:.72rem; color:var(--muted); flex-shrink:0; }
    .wa-item-bot { display:flex; justify-content:space-between; gap:.5rem; align-items:center; margin-top:.15rem; }
    .wa-item-bot .preview {
        font-size:.82rem; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
        background:transparent !important; border:0 !important; box-shadow:none !important;
        padding:0 !important; margin:0; display:block; max-width:100%;
    }
    .wa-item-bot .dot {
        background:var(--wa-teal); color:#fff; border-radius:999px; min-width:20px; height:20px;
        font-size:.7rem; font-weight:700; display:inline-flex; align-items:center; justify-content:center; padding:0 .35rem;
    }
    .wa-name-row { display:flex; align-items:center; gap:.4rem; min-width:0; }
    .wa-name-row .name { font-weight:600; color:var(--ink); font-size:.95rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wa-oncall-ico {
        color:#16a34a; font-size:.78rem; flex-shrink:0;
        animation: waCallBounce 1s ease-in-out infinite;
    }
    .wa-oncall-ico.video { color:#2563eb; }
    @keyframes waCallBounce {
        0%, 100% { transform: translateY(0) scale(1); }
        40% { transform: translateY(-3px) scale(1.12); }
        60% { transform: translateY(-1px) scale(1.05); }
    }
    .wa-live-calls {
        margin:0 .75rem .55rem; padding:.55rem .7rem; border-radius:10px;
        background:rgba(37,99,235,.08); border:1px solid rgba(37,99,235,.2);
        color:var(--ink); font-size:.78rem;
    }
    .wa-live-calls .live-title {
        font-weight:700; display:flex; align-items:center; gap:.4rem; margin-bottom:.25rem;
    }
    .wa-live-calls .live-title i { color:#16a34a; animation: waCallBounce 1s ease-in-out infinite; }
    .wa-live-calls .live-row { color:var(--muted); line-height:1.35; }
    .wa-item-menu { flex-shrink:0; }
    .wa-item-menu .btn-link { color:var(--muted); text-decoration:none; padding:.15rem .35rem; }
    .wa-empty-side { padding:2.5rem 1.25rem; text-align:center; color:var(--muted); }

    .wa-main {
        display:flex; flex-direction:column; min-width:0; min-height:0; height:100%;
        background:#efeae2; position:relative; overflow:hidden;
    }
    .wa-main::before {
        content:""; position:absolute; inset:0; pointer-events:none; opacity:.06; z-index:0;
        background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%230f2744' fill-opacity='1'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .wa-main > * { position:relative; z-index:1; }
    .wa-chat-head {
        display:none; align-items:center; gap:.75rem; padding:.7rem 1rem; flex-shrink:0;
        background:var(--wa-header); color:#fff; border-bottom:1px solid rgba(255,255,255,.06);
    }
    .wa-chat-head.is-open { display:flex; }
    .wa-back { display:none; border:0; background:transparent; color:#fff; font-size:1.1rem; padding:.25rem .4rem; cursor:pointer; }
    .wa-chat-head .av-wrap { position:relative; width:40px; height:40px; flex-shrink:0; }
    .wa-chat-head .av { width:40px; height:40px; border-radius:50%; object-fit:cover; background:#16375f; display:block; }
    .wa-chat-head .av.group {
        display:flex; align-items:center; justify-content:center; background:rgba(255,155,68,.25); color:#ffd2a8;
    }
    .wa-chat-head .av-wrap .wa-online-dot { width:11px; height:11px; right:0; bottom:0; border-color:#0f2744; }
    .wa-chat-head .info { flex:1; min-width:0; cursor:pointer; }
    .wa-chat-head .info strong { display:block; font-size:.98rem; }
    .wa-chat-head .info span { font-size:.75rem; opacity:.72; }
    .wa-chat-head .head-actions { display:flex; gap:.15rem; }
    .wa-chat-head .head-actions button {
        border:0; background:transparent; color:#fff; width:36px; height:36px; border-radius:50%;
        display:inline-flex; align-items:center; justify-content:center; cursor:pointer;
    }
    .wa-chat-head .head-actions button:hover { background:rgba(255,255,255,.12); }

    .wa-chat-body { flex:1; min-height:0; display:flex; overflow:hidden; }
    .wa-msgs { flex:1; overflow-y:auto; padding:1rem 1.25rem 1.25rem; display:flex; flex-direction:column; gap:.35rem; }
    .wa-members {
        width:280px; background:#fff; border-left:1px solid var(--line); display:none; flex-direction:column;
    }
    .wa-members.open { display:flex; }
    .wa-members-head {
        padding:.85rem 1rem; font-weight:700; color:var(--ink); border-bottom:1px solid var(--line);
        display:flex; justify-content:space-between; align-items:center;
    }
    .wa-members-list { flex:1; overflow-y:auto; padding:.5rem 0; }
    .wa-member-row {
        display:flex; align-items:center; justify-content:space-between; gap:.5rem;
        padding:.55rem 1rem; border-bottom:1px solid #f2f4f7;
    }
    .wa-member-row .left { display:flex; align-items:center; gap:.55rem; min-width:0; }
    .wa-member-row img { width:34px; height:34px; border-radius:50%; object-fit:cover; }
    .wa-member-row .nm { font-size:.88rem; font-weight:600; color:var(--ink); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .wa-member-row .rm { border:0; background:transparent; color:#c0392b; cursor:pointer; }

    .wa-day {
        align-self:center; background:rgba(255,255,255,.92); color:var(--muted);
        font-size:.72rem; font-weight:600; padding:.28rem .7rem; border-radius:8px;
        box-shadow:0 1px 2px rgba(0,0,0,.06); margin:.55rem 0; text-transform:uppercase; letter-spacing:.03em;
    }
    .wa-bubble-row {
        display:flex; width:100%;
        align-items:flex-end;
    }
    .wa-bubble-row.me { justify-content:flex-end; }
    .wa-bubble-row.them { justify-content:flex-start; }
    .wa-msg {
        position:relative;
        display:inline-block;
        max-width:min(65%, 420px);
        vertical-align:bottom;
    }
    .wa-msg.me { margin-left:auto; }
    .wa-bubble {
        display:block !important;
        width:fit-content !important;
        max-width:100% !important;
        min-width:72px;
        min-height:0 !important;
        height:auto !important;
        padding:6px 72px 8px 9px; /* reserve right space for time — keeps short msgs compact */
        border-radius:8px;
        box-shadow:0 1px 0.5px rgba(0,0,0,.13);
        position:relative;
        word-break:break-word;
        white-space:normal;
        font-size:.9375rem;
        line-height:1.35;
        color:#111b21;
        text-align:left;
        box-sizing:border-box;
        vertical-align:bottom;
        flex:0 0 auto !important;
    }
    .wa-bubble.has-edited { padding: 6px 78px 26px 9px; }
    .wa-bubble.has-multiline { white-space:pre-wrap; }
    .wa-bubble.me { background:var(--wa-out); border-top-right-radius:2px; }
    .wa-bubble.them { background:var(--wa-in); border-top-left-radius:2px; }
    .wa-bubble .wa-text {
        display:inline;
        margin:0;
        padding:0;
        white-space:inherit;
        background:transparent !important;
        border:0 !important;
        box-shadow:none !important;
        min-height:0 !important;
        height:auto !important;
    }
    .wa-bubble .who { display:block; font-size:.72rem; font-weight:700; color:#c45f12; margin-bottom:.15rem; }
    .wa-bubble .reply-box {
        display:block;
        background:rgba(0,0,0,.06); border-left:3px solid var(--accent); border-radius:6px;
        padding:.3rem .45rem; margin-bottom:.35rem; font-size:.78rem; color:#54656f; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .wa-bubble img.media,
    .wa-bubble video.media {
        max-width:min(260px, 70vw);
        width:auto;
        height:auto;
        border-radius:8px;
        display:block;
        margin:0;
        vertical-align:bottom;
    }
    .wa-bubble audio.media {
        display:block;
        width:min(240px, 70vw);
        max-width:100%;
        margin:0;
        height:36px;
    }
    .wa-bubble.is-media {
        padding:0 !important;
        min-width:0 !important;
        overflow:hidden;
        line-height:0;
        background:transparent !important;
        box-shadow:none !important;
    }
    .wa-bubble.is-media.has-caption {
        line-height:1.35;
        padding:0 0 4px !important;
        background:var(--wa-in) !important;
        box-shadow:0 1px 0.5px rgba(0,0,0,.13) !important;
    }
    .wa-bubble.is-media.me.has-caption { background:var(--wa-out) !important; }
    .wa-bubble.is-media > a {
        display:block;
        line-height:0;
        width:max-content;
        max-width:100%;
    }
    .wa-bubble.is-media img.media,
    .wa-bubble.is-media video.media {
        max-width:min(260px, 70vw);
        border-radius:8px;
    }
    .wa-bubble.is-media .wa-media-caption {
        display:block;
        padding:4px 62px 2px 6px;
        font-size:.82rem;
        line-height:1.3;
        word-break:break-word;
    }
    .wa-bubble a.file-link {
        display:inline-flex; align-items:center; gap:.4rem; color:#0b5cab; text-decoration:none; font-weight:600; font-size:.85rem;
    }
    .wa-bubble.deleted { font-style:italic; color:#8696a0; }
    /* edited above time; no meta background */
    .wa-meta {
        position:absolute !important;
        right:8px;
        bottom:5px;
        display:inline-flex !important;
        flex-direction:column !important;
        flex-wrap:nowrap !important;
        align-items:flex-end !important;
        gap:1px;
        float:none !important;
        margin:0 !important;
        padding:0 !important;
        user-select:none;
        line-height:1;
        height:auto !important;
        min-height:0 !important;
        width:auto !important;
        max-width:none !important;
        white-space:nowrap !important;
        background:transparent !important;
        border:0 !important;
        box-shadow:none !important;
    }
    .wa-meta .edited {
        display:block;
        font-size:.65rem;
        font-style:italic;
        color:#8696a0;
        margin:0 0 2px !important;
        white-space:nowrap;
        background:transparent !important;
        line-height:1;
    }
    .wa-meta .wa-meta-time {
        display:inline-flex;
        align-items:center;
        gap:.2rem;
        background:transparent !important;
    }
    .wa-meta time {
        font-size:.6875rem;
        color:#667781;
        white-space:nowrap;
        background:transparent !important;
    }
    .wa-bubble.is-media .wa-meta {
        right:7px;
        bottom:6px;
        background:transparent !important;
        text-shadow:0 1px 2px rgba(0,0,0,.55);
    }
    .wa-bubble.is-media .wa-meta time,
    .wa-bubble.is-media .wa-meta .edited { color:#fff; background:transparent !important; }
    .wa-bubble.is-media .wa-ticks { color:#fff; }
    .wa-bubble.is-media.has-caption .wa-meta { text-shadow:none; }
    .wa-bubble.is-media.has-caption .wa-meta time,
    .wa-bubble.is-media.has-caption .wa-meta .edited { color:#667781; }
    .wa-bubble.is-media.has-caption .wa-ticks { color:#53bdeb; }
    .wa-bubble.is-media.has-caption .wa-ticks.sent { color:#8696a0; }
    .wa-ticks { font-size:.7rem; color:#53bdeb; line-height:1; }
    .wa-ticks.sent { color:#8696a0; }
    .msg-actions {
        position:absolute; top:-2px; right:2px; opacity:0; transition:opacity .15s; z-index:3;
        line-height:1; width:auto; height:auto;
    }
    .wa-msg:hover .msg-actions { opacity:1; }
    .msg-actions .btn-link,
    .wa-shell .msg-actions .btn-link,
    .wa-shell .msg-actions a {
        color:#667781 !important; font-size:.7rem; text-decoration:none;
        padding:2px 4px !important; margin:0 !important;
        min-height:0 !important; min-width:0 !important; height:auto !important;
        width:auto !important; display:inline-flex !important;
        background:rgba(255,255,255,.85) !important; border:0 !important; box-shadow:none !important;
        border-radius:4px; line-height:1;
    }
    /* Dropdown must never take layout space */
    .wa-msg .dropdown-menu,
    .wa-shell .wa-msg .dropdown-menu {
        display:none !important;
        position:absolute !important;
        inset:auto 0 auto auto !important;
        transform:none !important;
        min-width:9rem;
        z-index:30;
        margin:0 !important;
        height:auto;
    }
    .wa-msg .dropdown-menu.show,
    .wa-shell .wa-msg .dropdown-menu.show {
        display:block !important;
    }
    .wa-shell .btn,
    .wa-shell .btn-sm,
    .wa-bubble .btn,
    .wa-bubble .btn-link,
    .wa-msg .btn,
    .wa-msg .btn-link {
        min-height:0 !important;
    }

    .wa-welcome {
        margin:auto; text-align:center; padding:2rem; background:rgba(255,255,255,.88);
        border-radius:16px; box-shadow:0 8px 24px rgba(15,39,68,.08); max-width:360px;
    }
    .wa-welcome .ico {
        width:64px; height:64px; margin:0 auto .85rem; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        background:linear-gradient(145deg,#0f2744,#1b466f); color:var(--accent); font-size:1.6rem;
    }
    .wa-welcome h3 { font-size:1.15rem; color:var(--ink); margin:0 0 .35rem; }
    .wa-welcome p { margin:0; color:var(--muted); font-size:.9rem; }

    .wa-composer-wrap {
        display:none; background:var(--soft); padding:.55rem .75rem .7rem;
        border-top:1px solid var(--line); position:relative; flex-shrink:0; z-index:3;
    }
    .wa-composer-wrap.is-open { display:block; }
    .wa-reply-bar {
        display:none; align-items:center; justify-content:space-between; gap:.75rem;
        background:#fff; border-left:4px solid var(--accent); border-radius:10px;
        padding:.45rem .7rem; margin-bottom:.45rem; font-size:.82rem;
    }
    .wa-reply-bar.show { display:flex; }
    .wa-reply-bar .txt { color:var(--ink); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .wa-reply-bar button { border:0; background:transparent; color:#c0392b; cursor:pointer; }
    .wa-file-chip {
        display:none; align-items:center; gap:.5rem; background:#fff; border:1px solid var(--line);
        border-radius:10px; padding:.4rem .65rem; margin-bottom:.45rem; font-size:.82rem; color:var(--ink);
    }
    .wa-file-chip.show { display:inline-flex; }
    .wa-file-chip button { border:0; background:transparent; color:#c0392b; cursor:pointer; }
    .wa-photo-menu {
        display:none; position:absolute; left:3.4rem; bottom:calc(100% - .15rem);
        background:#fff; border:1px solid var(--line); border-radius:12px;
        box-shadow:0 10px 28px rgba(15,39,68,.14); z-index:9; overflow:hidden; min-width:180px;
    }
    .wa-photo-menu.show { display:block; }
    .wa-photo-menu button {
        display:flex; align-items:center; gap:.55rem; width:100%; border:0; background:#fff;
        padding:.7rem .9rem; font-size:.88rem; color:var(--ink); cursor:pointer; text-align:left;
    }
    .wa-photo-menu button:hover { background:#f4f7fb; }
    .wa-photo-menu button i { width:1.1rem; color:#54656f; }
    .wa-attach-wrap { position:relative; flex-shrink:0; }
    .wa-attach-wrap #attachMenuBtn.is-open {
        background:rgba(255,155,68,.16); color:#c45f12;
        transform:rotate(45deg);
    }
    .wa-attach-rail {
        display:none; position:absolute; left:0; bottom:calc(100% + .35rem);
        flex-direction:column; gap:.25rem; padding:.35rem;
        background:#fff; border:1px solid var(--line); border-radius:14px;
        box-shadow:0 12px 28px rgba(15,39,68,.16); z-index:8; min-width:48px;
    }
    .wa-attach-rail.show { display:flex; }
    .wa-attach-rail .wa-icon-btn {
        width:42px; height:42px; border-radius:12px; background:#f4f7fb; color:#54656f;
    }
    .wa-attach-rail .wa-icon-btn:hover { background:rgba(255,155,68,.16); color:#c45f12; }
    .wa-attach-rail .wa-icon-btn span.lbl {
        display:none;
    }
    .wa-emoji-panel {
        display:none; position:absolute; left:.75rem; right:.75rem; bottom:calc(100% + .35rem);
        max-width:320px; background:#fff; border:1px solid var(--line); border-radius:14px;
        box-shadow:0 12px 28px rgba(15,39,68,.16); z-index:9; padding:.55rem;
    }
    .wa-emoji-panel.show { display:block; }
    .wa-emoji-grid {
        display:grid; grid-template-columns:repeat(8, 1fr); gap:.2rem; max-height:180px; overflow-y:auto;
    }
    .wa-emoji-grid button {
        border:0; background:transparent; font-size:1.25rem; line-height:1.4;
        border-radius:8px; cursor:pointer; padding:.15rem;
    }
    .wa-emoji-grid button:hover { background:#f4f7fb; }
    .wa-rec-bar {
        display:none; align-items:center; gap:.65rem; background:#fff; border:1px solid #ffd7bf;
        border-radius:12px; padding:.45rem .7rem; margin-bottom:.45rem; font-size:.84rem; color:var(--ink);
    }
    .wa-rec-bar.show { display:flex; }
    .wa-rec-bar .rec-dot {
        width:10px; height:10px; border-radius:50%; background:#e74c3c;
        box-shadow:0 0 0 0 rgba(231,76,60,.45); animation:waRecPulse 1.2s infinite;
    }
    @keyframes waRecPulse {
        0% { box-shadow:0 0 0 0 rgba(231,76,60,.45); }
        70% { box-shadow:0 0 0 8px rgba(231,76,60,0); }
        100% { box-shadow:0 0 0 0 rgba(231,76,60,0); }
    }
    .wa-rec-bar .rec-timer { font-variant-numeric:tabular-nums; font-weight:700; min-width:42px; }
    .wa-rec-bar .rec-label { flex:1; color:var(--muted); }
    .wa-rec-bar .btn-stop {
        border:0; border-radius:999px; background:#e74c3c; color:#fff;
        padding:.28rem .7rem; font-size:.78rem; font-weight:600; cursor:pointer;
    }
    .wa-rec-bar .btn-cancel {
        border:0; background:transparent; color:#c0392b; font-size:1.1rem; cursor:pointer; line-height:1;
    }
    .wa-icon-btn.is-recording { color:#e74c3c; background:rgba(231,76,60,.1); }
    .wa-composer { display:flex; align-items:flex-end; gap:.2rem; }
    .wa-icon-btn {
        width:40px; height:42px; border-radius:50%; border:0; background:transparent; color:#54656f;
        font-size:1.1rem; display:inline-flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0;
    }
    .wa-icon-btn:hover { color:var(--ink); background:rgba(15,39,68,.06); }
    .wa-icon-btn:disabled { opacity:.4; cursor:not-allowed; }
    .wa-media-overlay {
        display:none; position:fixed; inset:0; z-index:2500; background:rgba(8,14,24,.88);
        align-items:center; justify-content:center; padding:1rem;
    }
    .wa-media-overlay.is-open { display:flex; }
    .wa-media-sheet {
        width:min(420px,100%); background:#111827; color:#fff; border-radius:18px; overflow:hidden;
        box-shadow:0 24px 60px rgba(0,0,0,.45);
    }
    .wa-media-sheet .head {
        display:flex; align-items:center; justify-content:space-between; gap:.75rem;
        padding:.85rem 1rem; border-bottom:1px solid rgba(255,255,255,.08);
    }
    .wa-media-sheet .head strong { font-size:.95rem; }
    .wa-media-sheet .head button {
        border:0; background:transparent; color:#fff; font-size:1.35rem; line-height:1; cursor:pointer; opacity:.8;
    }
    .wa-media-preview {
        position:relative; width:100%; background:#000; aspect-ratio:3/4; max-height:62vh;
        overflow:hidden; line-height:0;
    }
    .wa-media-preview video,
    .wa-media-preview canvas,
    .wa-media-preview img {
        position:absolute; inset:0;
        width:100% !important; height:100% !important; max-width:none !important;
        object-fit:cover; object-position:center; display:block; margin:0; border:0;
        background:#000;
    }
    #cameraPhotoVideo { transform: scaleX(-1); } /* mirror selfie preview */
    .wa-media-preview .countdown {
        position:absolute; top:.75rem; left:50%; transform:translateX(-50%);
        background:rgba(0,0,0,.55); color:#fff; border-radius:999px; padding:.25rem .7rem;
        font-size:.82rem; font-weight:700; font-variant-numeric:tabular-nums;
    }
    .wa-media-actions {
        display:flex; align-items:center; justify-content:center; gap:1rem; padding:1rem;
    }
    .wa-media-actions .snap, .wa-media-actions .rec {
        width:64px; height:64px; border-radius:50%; border:3px solid #fff; background:#e74c3c;
        cursor:pointer; display:inline-flex; align-items:center; justify-content:center;
    }
    .wa-media-actions .snap { background:#fff; }
    .wa-media-actions .snap i { color:#111; font-size:1.2rem; }
    .wa-media-actions .rec.is-on { background:#fff; }
    .wa-media-actions .rec.is-on .inner {
        width:18px; height:18px; border-radius:4px; background:#e74c3c;
    }
    .wa-media-actions .rec .inner {
        width:22px; height:22px; border-radius:50%; background:#fff;
    }
    .wa-media-actions .use-btn, .wa-media-actions .retake-btn {
        border:0; border-radius:999px; padding:.55rem 1rem; font-weight:600; cursor:pointer; font-size:.88rem;
    }
    .wa-media-actions .use-btn { background:#ff9b44; color:#fff; }
    .wa-media-actions .retake-btn { background:rgba(255,255,255,.12); color:#fff; }
    .wa-input-pill {
        flex:1; background:#fff; border-radius:24px; border:1px solid #e4e9f0; padding:.55rem 1rem;
        display:flex; flex-direction:column; gap:.2rem; min-width:0;
    }
    .wa-input-pill textarea {
        width:100%; border:0; outline:0; resize:none; max-height:180px; min-height:22px;
        font-size:.95rem; line-height:1.35; background:transparent; color:var(--ink); font-family:inherit;
    }
    .wa-line-hint { font-size:.68rem; color:var(--muted); text-align:right; display:none; }
    .wa-line-hint.warn { color:#c0392b; display:block; }
    .wa-send {
        width:46px; height:46px; border-radius:50%; border:0;
        background:linear-gradient(145deg,#ff9b44,#f07a1a); color:#fff; font-size:1.05rem;
        display:inline-flex; align-items:center; justify-content:center; cursor:pointer; flex-shrink:0;
        box-shadow:0 4px 12px rgba(255,155,68,.35);
    }
    .wa-send:disabled { opacity:.45; cursor:not-allowed; box-shadow:none; }
    .mention-suggestions {
        position:absolute; left:.75rem; right:.75rem; bottom:100%;
        background:#fff; border:1px solid var(--line); border-radius:10px;
        box-shadow:0 8px 24px rgba(15,39,68,.12); max-height:180px; overflow-y:auto; z-index:5;
    }
    .mention-suggestions .item {
        padding:.55rem .85rem; cursor:pointer; font-size:.88rem; border-bottom:1px solid #f2f4f7;
    }
    .mention-suggestions .item:hover { background:#f4f7fb; }
    .chat-modal .form-check { margin-bottom:.35rem; }
    .chat-modal .members-scroll { max-height:280px; overflow-y:auto; border:1px solid #eef2f7; border-radius:10px; padding:.65rem .85rem; }

    /* ---- Calling UI (theme-aware) ---- */
    .hrm-call-overlay {
        display:none; position:fixed; inset:0; z-index:2400;
        background:rgba(15,23,42,.55); align-items:center; justify-content:center; padding:1rem;
    }
    .hrm-call-overlay.is-open { display:flex; }
    .hrm-call-overlay.hrm-call-active.is-minimized {
        inset:auto; right:1rem; bottom:1rem; width:min(360px,92vw); height:220px;
        padding:0; border-radius:16px; overflow:hidden;
        box-shadow:0 16px 40px rgba(0,0,0,.45); align-items:stretch;
    }
    .hrm-call-overlay.hrm-call-active.is-pip {
        inset:auto; right:1rem; bottom:1rem; width:min(280px,90vw); height:170px;
        padding:0; border-radius:14px; overflow:hidden;
        box-shadow:0 14px 36px rgba(0,0,0,.5); align-items:stretch; z-index:2450;
    }
    .hrm-call-overlay.hrm-call-active.is-minimized .hrm-call-stage,
    .hrm-call-overlay.hrm-call-active.is-pip .hrm-call-stage {
        min-height:100%; height:100%;
    }
    .hrm-call-overlay.hrm-call-active.is-minimized .hrm-call-local,
    .hrm-call-overlay.hrm-call-active.is-pip .hrm-call-local {
        width:72px; height:96px; right:.5rem; bottom:3.6rem;
    }
    .hrm-call-overlay.hrm-call-active.is-minimized .hrm-call-controls,
    .hrm-call-overlay.hrm-call-active.is-pip .hrm-call-controls {
        gap:.45rem; padding:.55rem; 
    }
    .hrm-call-overlay.hrm-call-active.is-minimized .hrm-call-btn,
    .hrm-call-overlay.hrm-call-active.is-pip .hrm-call-btn {
        width:40px; height:40px; font-size:.9rem;
    }
    .hrm-call-overlay.hrm-call-active.is-minimized .hrm-call-top .hrm-call-people,
    .hrm-call-overlay.hrm-call-active.is-pip .hrm-call-top .hrm-call-people { display:none; }
    .hrm-call-win-btns {
        position:absolute; top:.75rem; right:.85rem; z-index:6;
        display:flex; gap:.35rem;
    }
    .hrm-call-win-btns button {
        width:34px; height:34px; border-radius:8px; border:0; cursor:pointer;
        background:rgba(255,255,255,.14); color:#fff; display:inline-flex;
        align-items:center; justify-content:center; font-size:.85rem;
    }
    .hrm-call-win-btns button:hover { background:rgba(255,255,255,.28); }
    /* Add-people modal must sit above the fullscreen call overlay */
    #callInviteModal { z-index: 2600 !important; }
    body.hrm-call-invite-open .modal-backdrop { z-index: 2590 !important; }
    .hrm-call-card {
        width:min(360px,100%); background:var(--hrm-white, #fff); color:var(--hrm-navy, #0f2744);
        border:1px solid var(--hrm-border, #e5eaf1); border-radius:16px; padding:1.5rem 1.25rem 1.35rem;
        text-align:center; box-shadow:0 20px 50px rgba(0,0,0,.25);
    }
    .hrm-call-label { font-size:.8rem; font-weight:600; color:var(--hrm-muted, #667781); margin-bottom:.85rem; }
    .hrm-call-name { font-size:1.15rem; font-weight:700; margin-top:.75rem; }
    .hrm-call-avatar {
        width:88px; height:88px; border-radius:50%; object-fit:cover; background:#dfe6ee;
        border:3px solid var(--hrm-border, #e5eaf1);
    }
    .hrm-call-avatar.lg { width:120px; height:120px; }
    .hrm-call-actions { display:flex; justify-content:center; gap:1.5rem; margin-top:1.35rem; }
    .hrm-call-btn {
        width:56px; height:56px; border-radius:50%; border:0; cursor:pointer;
        display:inline-flex; align-items:center; justify-content:center; font-size:1.15rem; color:#fff;
    }
    .hrm-call-btn.accept { background:#16a34a; }
    .hrm-call-btn.reject, .hrm-call-btn.end { background:#dc2626; }
    .hrm-call-btn.ctrl {
        background:rgba(255,255,255,.14); color:#fff; width:52px; height:52px;
        border:1px solid rgba(255,255,255,.18);
    }
    .hrm-call-btn.ctrl.is-off, .hrm-call-btn.ctrl.is-on { background:rgba(255,255,255,.32); }
    .hrm-call-active { background:#0b1220; padding:0; }
    .hrm-call-stage {
        position:relative; width:100%; height:100%; min-height:100vh;
        display:flex; flex-direction:column; background:#0b1220; color:#fff;
    }
    .hrm-call-top {
        position:absolute; top:0; left:0; right:0; z-index:3;
        padding:1rem 1.25rem; background:linear-gradient(180deg,rgba(0,0,0,.55),transparent);
    }
    .hrm-call-status { font-size:.82rem; opacity:.85; margin-top:.15rem; }
    .hrm-call-remote {
        flex:1; width:100%; height:100%; object-fit:cover; background:#111827;
    }
    .hrm-call-remote-grid {
        flex:1; width:100%; min-height:0; display:grid; gap:.5rem; padding:.75rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        align-content:center; background:#0b1220;
    }
    .hrm-call-tile {
        position:relative; background:#111827; border-radius:12px; overflow:hidden; min-height:160px;
        border:1px solid rgba(255,255,255,.12);
    }
    .hrm-call-tile video { width:100%; height:100%; object-fit:cover; min-height:160px; background:#000; }
    .hrm-call-tile-name {
        position:absolute; left:8px; bottom:8px; font-size:.75rem; font-weight:600;
        background:rgba(0,0,0,.55); padding:.2rem .45rem; border-radius:6px;
    }
    .hrm-call-people { display:flex; flex-wrap:wrap; gap:.35rem; margin-top:.45rem; }
    .hrm-call-chip {
        font-size:.68rem; padding:.15rem .45rem; border-radius:999px;
        background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.18);
    }
    .hrm-call-chip.invited, .hrm-call-chip.ringing { opacity:.7; }
    .hrm-call-local {
        position:absolute; right:1rem; bottom:6.5rem; width:150px; height:200px; object-fit:cover;
        border-radius:12px; border:2px solid rgba(255,255,255,.35); background:#1f2937; z-index:4;
        cursor:grab; touch-action:none; user-select:none;
    }
    .hrm-call-local.is-dragging { cursor:grabbing; z-index:8; }
    .hrm-call-local-hint {
        position:absolute; left:50%; bottom:6px; transform:translateX(-50%);
        font-size:.62rem; color:#fff; background:rgba(0,0,0,.45); padding:1px 6px; border-radius:999px;
        pointer-events:none; opacity:.85; white-space:nowrap;
    }
    .hrm-call-audio-hero {
        flex:1; display:flex; align-items:center; justify-content:center;
    }
    .hrm-call-controls {
        position:absolute; left:0; right:0; bottom:0; z-index:5;
        display:flex; justify-content:center; gap:.85rem; padding:1.25rem;
        background:linear-gradient(0deg,rgba(0,0,0,.65),transparent);
    }
    .wa-bubble-row.call { justify-content:center; }
    .wa-call-msg {
        align-self:center; background:rgba(255,255,255,.92); color:var(--muted);
        font-size:.8rem; font-weight:600; padding:.45rem .85rem; border-radius:10px;
        box-shadow:0 1px 2px rgba(0,0,0,.06); margin:.35rem 0; text-align:center;
        display:inline-flex; align-items:center; gap:.4rem; max-width:90%;
    }
    html[data-theme="dark"] .wa-call-msg,
    html[data-theme="dark-blue"] .wa-call-msg {
        background:var(--hrm-white); color:var(--hrm-muted);
    }
    html[data-theme="dark"] .hrm-call-card,
    html[data-theme="dark-blue"] .hrm-call-card {
        background:var(--hrm-white); color:var(--hrm-navy);
    }
    @media (max-width: 767px) {
        .hrm-call-local { width:110px; height:150px; right:.75rem; bottom:5.75rem; }
    }

    @media (max-width: 991px) {
        .wa-shell { grid-template-columns:1fr; height:calc(100vh - 72px); border-radius:12px; }
        .wa-shell.show-chat .wa-side { display:none; }
        .wa-shell:not(.show-chat) .wa-main { display:none; }
        .wa-back { display:inline-flex; }
        .wa-members {
            position:absolute; right:0; top:0; bottom:0; width:min(300px,85vw); z-index:6;
            box-shadow:-4px 0 16px rgba(0,0,0,.12);
        }
    }
</style>
@endpush

@section('content')
<div class="wa-shell" id="waShell">
    <aside class="wa-side">
        <div class="wa-side-head">
            <img class="me-av" src="{{ $meImage }}" alt="">
            <div class="meta">
                <strong>{{ $meName }}</strong>
                <span>Messages</span>
            </div>
            <span class="badge-unread d-none" id="unreadBadge">0</span>
            <div class="wa-side-actions">
                <button type="button" class="btn-ico" data-bs-toggle="modal" data-bs-target="#groupModal" title="New group">
                    <i class="fa-solid fa-users"></i>
                </button>
            </div>
        </div>
        <div class="wa-search">
            <div class="wa-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="chatSearch" placeholder="Search or start a chat" autocomplete="off">
            </div>
        </div>
        <div class="wa-tabs">
            <button type="button" class="wa-tab active" data-filter="all">All</button>
            <button type="button" class="wa-tab" data-filter="online">Online</button>
            <button type="button" class="wa-tab" data-filter="unread">Unread</button>
            <button type="button" class="wa-tab" data-filter="groups">Groups</button>
            <button type="button" class="wa-tab" data-filter="calls">Call History</button>
        </div>
        <div class="wa-live-calls d-none" id="liveCallsBanner"></div>
        <div class="wa-list" id="contactList">
            <div class="wa-empty-side">Loading chats…</div>
        </div>
    </aside>

    <section class="wa-main" id="waMain">
        <div class="wa-chat-head" id="chatHead">
            <button type="button" class="wa-back" id="backBtn" title="Back"><i class="fa-solid fa-arrow-left"></i></button>
            <div id="chatHeadAv"></div>
            <div class="info" id="chatTitleWrap">
                <strong id="chatTitle">Select a chat</strong>
                <span id="chatSub">Click for info</span>
            </div>
            <div class="head-actions">
                <button type="button" class="d-none" id="callAudioBtn" title="Audio call"><i class="fa-solid fa-phone"></i></button>
                <button type="button" class="d-none" id="callVideoBtn" title="Video call"><i class="fa-solid fa-video"></i></button>
                <button type="button" id="clearChatBtn" title="Clear chat"><i class="fa-solid fa-trash-can"></i></button>
            </div>
        </div>

        <div class="wa-chat-body">
            <div class="wa-msgs" id="chatMessages">
                <div class="wa-welcome" id="waWelcome">
                    <div class="ico"><i class="fa-solid fa-comments"></i></div>
                    <h3>HRM Chat</h3>
                    <p>Select a colleague or group to start messaging.</p>
                </div>
            </div>
            <aside class="wa-members" id="groupMembersSidebar">
                <div class="wa-members-head">
                    <span>Group info</span>
                    <button type="button" class="btn btn-sm btn-link" id="closeMembersBtn">&times;</button>
                </div>
                <div class="wa-members-list" id="groupMembersList"></div>
                <div class="p-2 border-top d-none" id="addMembersAction">
                    <button type="button" class="btn btn-sm w-100" style="background:#ff9b44;color:#fff;" id="openAddMembersBtn">
                        <i class="fa-solid fa-user-plus"></i> Add members
                    </button>
                </div>
            </aside>
        </div>

        <div class="wa-composer-wrap" id="composerWrap">
            <div id="mentionSuggestions" class="mention-suggestions d-none"></div>
            <div class="wa-photo-menu" id="photoMenu">
                <button type="button" data-photo="camera"><i class="fa-solid fa-camera"></i> Camera</button>
                <button type="button" data-photo="gallery"><i class="fa-solid fa-images"></i> Local storage</button>
            </div>
            <div class="wa-emoji-panel" id="emojiPanel">
                <div class="wa-emoji-grid" id="emojiGrid"></div>
            </div>
            <div class="wa-reply-bar" id="replyBar">
                <div class="txt" id="replyPreview"></div>
                <button type="button" id="cancelReply" title="Cancel">&times;</button>
            </div>
            <div class="wa-file-chip" id="fileChip">
                <i class="fa-solid fa-paperclip"></i>
                <span id="selectedFileName"></span>
                <button type="button" id="clearFile" title="Remove">&times;</button>
            </div>
            <div class="wa-rec-bar" id="recBar">
                <span class="rec-dot"></span>
                <span class="rec-timer" id="recTimer">00:00</span>
                <span class="rec-label" id="recLabel">Recording audio…</span>
                <button type="button" class="btn-stop" id="stopRecBtn">Stop</button>
                <button type="button" class="btn-cancel" id="cancelRecBtn" title="Cancel">&times;</button>
            </div>
            <form class="wa-composer" id="chatForm" enctype="multipart/form-data">
                <input type="file" id="imageInput" class="d-none" accept=".jpg,.jpeg,.png,.gif,.webp,image/*">
                <input type="file" id="fileInput" class="d-none" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar,.mp3,.wav,.ogg,.webm,.m4a,.mp4,.mov,.avi">
                <div class="wa-attach-wrap">
                    <button type="button" class="wa-icon-btn" id="attachMenuBtn" title="Attach" disabled>
                        <i class="fa-solid fa-plus"></i>
                    </button>
                    <div class="wa-attach-rail" id="attachRail">
                        <button type="button" class="wa-icon-btn" id="imageBtn" title="Photo" disabled>
                            <i class="fa-solid fa-image"></i>
                        </button>
                        <button type="button" class="wa-icon-btn" id="attachBtn" title="Send file" disabled>
                            <i class="fa-solid fa-paperclip"></i>
                        </button>
                        <button type="button" class="wa-icon-btn" id="audioRecBtn" title="Record audio" disabled>
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                        <button type="button" class="wa-icon-btn" id="videoRecBtn" title="Record video (max 1 min)" disabled>
                            <i class="fa-solid fa-video"></i>
                        </button>
                        <button type="button" class="wa-icon-btn" id="emojiBtn" title="Emoji" disabled>
                            <i class="fa-regular fa-face-smile"></i>
                        </button>
                    </div>
                </div>
                <div class="wa-input-pill">
                    <textarea id="chatInput" rows="1" placeholder="Type a message" disabled maxlength="10000"></textarea>
                    <div class="wa-line-hint" id="lineHint">0 / 100 lines</div>
                </div>
                <button type="submit" class="wa-send" id="sendBtn" disabled title="Send">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </section>
</div>

{{-- Camera photo / video record --}}
<div class="wa-media-overlay" id="cameraPhotoOverlay" aria-hidden="true">
    <div class="wa-media-sheet">
        <div class="head">
            <strong>Take photo</strong>
            <button type="button" id="closeCameraPhoto" aria-label="Close">&times;</button>
        </div>
        <div class="wa-media-preview">
            <video id="cameraPhotoVideo" playsinline autoplay muted></video>
            <canvas id="cameraPhotoCanvas" class="d-none"></canvas>
            <img id="cameraPhotoPreview" class="d-none" alt="Captured">
        </div>
        <div class="wa-media-actions" id="cameraPhotoLiveActions">
            <button type="button" class="snap" id="snapPhotoBtn" title="Capture"><i class="fa-solid fa-camera"></i></button>
        </div>
        <div class="wa-media-actions d-none" id="cameraPhotoReviewActions">
            <button type="button" class="retake-btn" id="retakePhotoBtn">Retake</button>
            <button type="button" class="use-btn" id="usePhotoBtn">Use photo</button>
        </div>
    </div>
</div>

<div class="wa-media-overlay" id="videoRecOverlay" aria-hidden="true">
    <div class="wa-media-sheet">
        <div class="head">
            <strong>Record video</strong>
            <button type="button" id="closeVideoRec" aria-label="Close">&times;</button>
        </div>
        <div class="wa-media-preview">
            <video id="videoRecPreview" playsinline autoplay muted></video>
            <div class="countdown" id="videoRecTimer">00:00 / 01:00</div>
        </div>
        <div class="wa-media-actions">
            <button type="button" class="rec" id="videoRecToggle" title="Record">
                <span class="inner"></span>
            </button>
        </div>
        <div class="text-center pb-3" style="color:rgba(255,255,255,.65);font-size:.78rem">
            Max 1 minute per clip. Stop to attach, then send.
        </div>
    </div>
</div>

{{-- Incoming call --}}
<div class="hrm-call-overlay" id="callIncomingOverlay" aria-hidden="true">
    <div class="hrm-call-card">
        <div class="hrm-call-label" id="callIncomingType">Incoming call</div>
        <img id="callIncomingAvatar" class="hrm-call-avatar" src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="">
        <div class="hrm-call-name" id="callIncomingName">—</div>
        <div class="hrm-call-actions">
            <button type="button" class="hrm-call-btn reject" id="callRejectBtn" title="Reject"><i class="fa-solid fa-phone-slash"></i></button>
            <button type="button" class="hrm-call-btn accept" id="callAcceptBtn" title="Accept"><i class="fa-solid fa-phone"></i></button>
        </div>
    </div>
</div>

{{-- Active call --}}
<div class="hrm-call-overlay hrm-call-active" id="callActiveOverlay" aria-hidden="true">
    <div class="hrm-call-stage" id="callStage">
        <div class="hrm-call-top">
            <div>
                <strong id="callActiveName">Call</strong>
                <div class="hrm-call-status"><span id="callStatusText">Calling...</span> · <span id="callTimer">00:00</span></div>
                <div class="hrm-call-people" id="callPeopleChips"></div>
            </div>
        </div>
        <div class="hrm-call-win-btns">
            <button type="button" id="callMinBtn" title="Minimize"><i class="fa-solid fa-window-minimize"></i></button>
            <button type="button" id="callMaxBtn" title="Maximize"><i class="fa-solid fa-window-maximize"></i></button>
            <button type="button" id="callPipBtn" title="Picture in picture"><i class="fa-solid fa-images"></i></button>
        </div>
        <div class="hrm-call-remote-grid" id="callRemoteGrid"></div>
        <video id="callRemoteVideo" class="hrm-call-remote d-none" autoplay playsinline></video>
        <div class="hrm-call-audio-hero d-none" id="callAudioHero">
            <img id="callActiveAvatar" class="hrm-call-avatar lg" src="{{ asset('assets/img/profiles/avatar-02.jpg') }}" alt="">
        </div>
        <video id="callLocalVideo" class="hrm-call-local" autoplay playsinline muted title="Drag to move"></video>
        <div class="hrm-call-controls">
            <button type="button" class="hrm-call-btn ctrl" id="callMuteBtn" title="Mute"><i class="fa-solid fa-microphone"></i></button>
            <button type="button" class="hrm-call-btn ctrl d-none" id="callCamBtn" title="Camera off"><i class="fa-solid fa-video"></i></button>
            <button type="button" class="hrm-call-btn ctrl d-none" id="callShareBtn" title="Share screen"><i class="fa-solid fa-desktop"></i></button>
            <button type="button" class="hrm-call-btn ctrl" id="callAddBtn" title="Add people"><i class="fa-solid fa-user-plus"></i></button>
            <button type="button" class="hrm-call-btn end" id="callEndBtn" title="End call"><i class="fa-solid fa-phone-slash"></i></button>
        </div>
    </div>
</div>

{{-- Invite / start group call members --}}
<div class="modal fade chat-modal" id="callInviteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="callInviteModalTitle">Add people to call</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="callInviteList" class="members-scroll"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn d-none" style="background:#ff9b44;color:#fff;" id="callStartConfirmBtn">Start call</button>
                <button type="button" class="btn" style="background:#ff9b44;color:#fff;" id="callInviteConfirmBtn">Add to call</button>
            </div>
        </div>
    </div>
</div>

{{-- Create Group --}}
<div class="modal fade chat-modal" id="groupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="groupName" class="form-control mb-3" placeholder="Group name" autocomplete="off">
                <h6>Select Members</h6>
                <div class="members-scroll" id="groupMembersChecks">
                    @foreach($employees as $e)
                        <div class="form-check">
                            <input class="form-check-input create-member" type="checkbox" value="{{ $e->id }}" id="cm{{ $e->id }}">
                            <label class="form-check-label" for="cm{{ $e->id }}">{{ trim($e->fname.' '.$e->lname) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn" style="background:#ff9b44;color:#fff;" id="createGroupBtn">Create</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Group --}}
<div class="modal fade chat-modal" id="editGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Group Name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="editGroupName" class="form-control" placeholder="New group name">
                <input type="hidden" id="editGroupId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn" style="background:#ff9b44;color:#fff;" id="saveGroupNameBtn">Save</button>
            </div>
        </div>
    </div>
</div>

{{-- Add Members --}}
<div class="modal fade chat-modal" id="addMembersModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Members</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="members-scroll" id="addGroupMembersChecks">
                    @foreach($employees as $e)
                        <div class="form-check">
                            <input class="form-check-input add-member" type="checkbox" value="{{ $e->id }}" id="am{{ $e->id }}">
                            <label class="form-check-label" for="am{{ $e->id }}">{{ trim($e->fname.' '.$e->lname) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn" style="background:#ff9b44;color:#fff;" id="addMembersBtn">Add Members</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Message --}}
<div class="modal fade chat-modal" id="editMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="editMessageText" class="form-control">
                <input type="hidden" id="editMessageId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn" style="background:#ff9b44;color:#fff;" id="saveMessageBtn">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const meId = {{ (int) auth()->id() }};
const baseUrl = @json(url('/'));
const defaultAv = @json(asset('assets/img/profiles/avatar-02.jpg'));
const routes = {
    contacts: @json(route('chat.contacts')),
    messages: @json(route('chat.messages')),
    send: @json(route('chat.send')),
    unread: @json(route('chat.unread')),
    heartbeat: @json(route('chat.heartbeat')),
    groupMembers: @json(route('chat.group-members')),
    createGroup: @json(route('chat.groups.create')),
    addMembers: @json(route('chat.groups.add-members')),
    removeMember: @json(route('chat.groups.remove-member')),
    exitGroup: @json(route('chat.groups.exit')),
    deleteGroup: @json(route('chat.groups.delete')),
    editGroup: @json(route('chat.groups.edit')),
    clearChat: @json(route('chat.clear')),
    deleteMessage: @json(route('chat.messages.delete')),
    editMessage: @json(route('chat.messages.edit')),
    callConfig: @json(route('chat.call.config')),
    callPoll: @json(route('chat.call.poll')),
    callStart: @json(route('chat.call.start')),
    callAccept: @json(route('chat.call.accept')),
    callReject: @json(route('chat.call.reject')),
    callEnd: @json(route('chat.call.end')),
    callSignal: @json(route('chat.call.signal')),
    callInvite: @json(route('chat.call.invite')),
    groupMembers: @json(route('chat.group-members')),
    contacts: @json(route('chat.contacts')),
};
const csrf = $('meta[name="csrf-token"]').attr('content');

let current = null;
let pollTimer = null;
let selectedFile = null;
let contactsCache = { employees: [], groups: [], call_history: [] };
window.contactsCache = contactsCache;
let listFilter = 'all';
let searchQ = '';
let lastMsgSignature = '';
let lastMaxMsgIdByChat = {};
let lastUnreadContactsTotal = null;
let chatSoundCooldownUntil = 0;
let replyingTo = null;
let groupMembers = [];
let membersOpen = false;

function esc(s) { return $('<div>').text(s ?? '').html(); }
function chatSoundEnabled() {
    return !(window.HrmNotifyPrefs && window.HrmNotifyPrefs.chatSound === false);
}
function playChatMessageSound() {
    if (!chatSoundEnabled()) return;
    const now = Date.now();
    if (now < chatSoundCooldownUntil) return;
    chatSoundCooldownUntil = now + 1600;
    if (window.HrmNotifySounds) window.HrmNotifySounds.playMessageTone();
}
function isMineMessage(m) {
    return Number(m.sender_id) === Number(meId) || m.is_me === true || m.is_me === 1 || m.is_me === '1';
}
function fileUrl(path) {
    if (!path) return '';
    return baseUrl.replace(/\/$/, '') + '/' + String(path).replace(/^\//, '');
}
function formatListTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const now = new Date();
    if (d.toDateString() === now.toDateString()) return d.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    const y = new Date(now); y.setDate(now.getDate() - 1);
    if (d.toDateString() === y.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { day:'2-digit', month:'short' });
}
function formatBubbleTime(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
}
function dayLabel(iso) {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) return '';
    const now = new Date();
    if (d.toDateString() === now.toDateString()) return 'Today';
    const y = new Date(now); y.setDate(now.getDate() - 1);
    if (d.toDateString() === y.toDateString()) return 'Yesterday';
    return d.toLocaleDateString([], { weekday:'short', day:'numeric', month:'short', year:'numeric' });
}
function autoGrow(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}
function postForm(url, data) {
    const fd = data instanceof FormData ? data : (() => {
        const f = new FormData();
        Object.entries(data || {}).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach(item => f.append(k + '[]', item));
            else if (v !== undefined && v !== null) f.append(k, v);
        });
        return f;
    })();
    if (!fd.has('_token')) fd.append('_token', csrf);
    return $.ajax({ url, method: 'POST', data: fd, processData: false, contentType: false });
}

function syncChatOnlineStatus() {
    if (!current || current.type !== 'user') return;
    const row = (contactsCache.employees || []).find(e => Number(e.id) === Number(current.id));
    const online = !!(row && row.online);
    const onCall = !!(row && row.on_call);
    current.online = online;
    current.on_call = onCall;
    if (onCall) {
        $('#chatSub').html('<span class="wa-oncall-ico"><i class="fa-solid fa-phone"></i></span> On a call');
    } else {
        $('#chatSub').text(online ? 'Online' : (current.sub || 'Colleague'));
    }
    $('#chatHeadOnline').toggleClass('on', online);
}

function groupMenuHtml(g) {
    const id = g.id;
    const name = esc(g.name);
    let items = `<li><a class="dropdown-item" href="#" data-act="edit-group" data-id="${id}" data-name="${name}">Edit name</a></li>`;
    items += `<li><a class="dropdown-item" href="#" data-act="exit-group" data-id="${id}">Exit group</a></li>`;
    if (g.is_creator) {
        items += `<li><a class="dropdown-item" href="#" data-act="add-members" data-id="${id}">Add members</a></li>`;
        items += `<li><hr class="dropdown-divider"></li>`;
        items += `<li><a class="dropdown-item text-danger" href="#" data-act="delete-group" data-id="${id}">Delete group</a></li>`;
    }
    return `<div class="wa-item-menu dropdown" onclick="event.stopPropagation()">
        <button class="btn-link" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical"></i></button>
        <ul class="dropdown-menu dropdown-menu-end">${items}</ul>
    </div>`;
}

function renderLiveCallsBanner(liveCalls) {
    const box = $('#liveCallsBanner');
    if (!box.length) return;
    const calls = liveCalls || contactsCache.live_calls || [];
    if (!calls.length) {
        box.addClass('d-none').empty();
        return;
    }
    let html = `<div class="live-title"><i class="fa-solid fa-phone"></i> Live call in progress</div>`;
    calls.forEach(c => {
        const names = (c.participant_names || []).join(', ') || 'Participants';
        const kind = c.call_type === 'video' ? 'Video' : 'Audio';
        html += `<div class="live-row"><i class="fa-solid ${c.call_type === 'video' ? 'fa-video' : 'fa-phone'} me-1"></i>${esc(kind)} · ${esc(names)}</div>`;
    });
    box.html(html).removeClass('d-none');
}

function renderContactList() {
    const q = searchQ.trim().toLowerCase();
    let groups = contactsCache.groups || [];
    let employees = contactsCache.employees || [];
    let callHistory = contactsCache.call_history || [];

    if (listFilter === 'calls') {
        let rows = callHistory.slice();
        if (q) {
            rows = rows.filter((r) => String(r.name || '').toLowerCase().includes(q)
                || String(r.preview || '').toLowerCase().includes(q));
        }
        let html = '';
        if (rows.length) {
            html += `<div class="wa-sec">Call History · ${rows.length}</div>`;
            rows.forEach((r) => {
                const isGroup = r.type === 'group';
                const name = r.name || 'Unknown';
                const active = current && current.type === r.type && Number(current.id) === Number(r.id);
                const img = r.image || defaultAv;
                const online = !!r.online;
                const onCall = !!r.on_call;
                const ico = r.call_type === 'video' ? 'fa-video' : 'fa-phone';
                if (isGroup) {
                    html += `
                    <div class="wa-item ${active ? 'active' : ''}" data-type="group" data-id="${r.id}" data-name="${esc(name)}" data-image="" data-online="0" data-creator="${r.is_creator ? 1 : 0}">
                        <div class="wa-av-wrap"><div class="wa-av group"><i class="fa-solid fa-users"></i></div></div>
                        <div class="wa-item-body">
                            <div class="wa-item-top"><div class="name">${esc(name)}</div><div class="time">${esc(formatListTime(r.last_at))}</div></div>
                            <div class="wa-item-bot">
                                <div class="preview"><i class="fa-solid ${ico}" style="margin-right:.3rem;opacity:.75"></i>${esc(r.preview || 'Call')}</div>
                            </div>
                        </div>
                    </div>`;
                } else {
                    html += `
                    <div class="wa-item ${active ? 'active' : ''}" data-type="user" data-id="${r.id}" data-name="${esc(name)}" data-image="${esc(img)}" data-sub="${esc(r.job_title || 'Colleague')}" data-online="${online ? 1 : 0}" data-oncall="${onCall ? 1 : 0}" data-creator="0">
                        <div class="wa-av-wrap">
                            <img class="wa-av" src="${esc(img)}" alt="" onerror="this.src='${defaultAv}'">
                            <span class="wa-online-dot ${online ? 'on' : ''}"></span>
                        </div>
                        <div class="wa-item-body">
                            <div class="wa-item-top">
                                <div class="wa-name-row"><div class="name">${esc(name)}</div></div>
                                <div class="time">${onCall ? '<span style="color:#16a34a;font-weight:700">on call</span>' : esc(formatListTime(r.last_at))}</div>
                            </div>
                            <div class="wa-item-bot">
                                <div class="preview"><i class="fa-solid ${ico}" style="margin-right:.3rem;opacity:.75"></i>${esc(r.preview || 'Call')}</div>
                            </div>
                        </div>
                    </div>`;
                }
            });
        } else {
            html = `<div class="wa-empty-side">No call history yet</div>`;
        }
        $('#contactList').html(html);
        renderLiveCallsBanner(contactsCache.live_calls);
        syncChatOnlineStatus();
        return;
    }

    if (listFilter === 'unread') {
        groups = groups.filter(g => Number(g.unread) > 0);
        employees = employees.filter(e => Number(e.unread) > 0);
    } else if (listFilter === 'online') {
        groups = [];
        employees = employees.filter(e => !!e.online);
    } else if (listFilter === 'groups') {
        employees = [];
    }

    if (q) {
        groups = groups.filter(g => String(g.name || '').toLowerCase().includes(q));
        employees = employees.filter(e => String(e.name || '').toLowerCase().includes(q)
            || String(e.job_title || '').toLowerCase().includes(q));
    }

    let html = '';
    const onlineCount = (contactsCache.employees || []).filter(e => e.online).length;
    const onCallCount = (contactsCache.employees || []).filter(e => e.on_call).length;

    if (groups.length) {
        html += '<div class="wa-sec">Groups</div>';
        groups.forEach(g => {
            const active = current && current.type === 'group' && Number(current.id) === Number(g.id);
            const unread = Number(g.unread || 0);
            html += `
            <div class="wa-item ${active ? 'active' : ''}" data-type="group" data-id="${g.id}" data-name="${esc(g.name)}" data-image="" data-online="0" data-creator="${g.is_creator ? 1 : 0}">
                <div class="wa-av-wrap"><div class="wa-av group"><i class="fa-solid fa-users"></i></div></div>
                <div class="wa-item-body">
                    <div class="wa-item-top"><div class="name">${esc(g.name)}</div><div class="time">${esc(formatListTime(g.last_at))}</div></div>
                    <div class="wa-item-bot">
                        <div class="preview">${esc(g.last_message || 'No messages yet')}</div>
                        ${unread ? `<span class="dot">${unread > 99 ? '99+' : unread}</span>` : ''}
                    </div>
                </div>
                ${groupMenuHtml(g)}
            </div>`;
        });
    }

    if (employees.length) {
        const label = listFilter === 'online'
            ? `Online · ${employees.length}`
            : `Employees · ${employees.length}${onlineCount ? ` · ${onlineCount} online` : ''}${onCallCount ? ` · ${onCallCount} on call` : ''}`;
        html += `<div class="wa-sec">${esc(label)}</div>`;
        employees.forEach(e => {
            const name = e.name || `${e.fname || ''} ${e.lname || ''}`.trim();
            const active = current && current.type === 'user' && Number(current.id) === Number(e.id);
            const unread = Number(e.unread || 0);
            const online = !!e.online;
            const onCall = !!e.on_call;
            const img = e.image || defaultAv;
            const callIco = onCall
                ? `<span class="wa-oncall-ico" title="On a call"><i class="fa-solid fa-phone"></i></span>`
                : '';
            html += `
            <div class="wa-item ${active ? 'active' : ''}" data-type="user" data-id="${e.id}" data-name="${esc(name)}" data-image="${esc(img)}" data-sub="${esc(e.job_title || 'Colleague')}" data-online="${online ? 1 : 0}" data-oncall="${onCall ? 1 : 0}" data-creator="0">
                <div class="wa-av-wrap">
                    <img class="wa-av" src="${esc(img)}" alt="" onerror="this.src='${defaultAv}'">
                    <span class="wa-online-dot ${online ? 'on' : ''}"></span>
                </div>
                <div class="wa-item-body">
                    <div class="wa-item-top">
                        <div class="wa-name-row"><div class="name">${esc(name)}</div>${callIco}</div>
                        <div class="time">${onCall ? '<span style="color:#16a34a;font-weight:700">on call</span>' : (online ? '<span style="color:#25d366;font-weight:700">online</span>' : esc(formatListTime(e.last_at)))}</div>
                    </div>
                    <div class="wa-item-bot">
                        <div class="preview">${esc(onCall ? 'On a call right now' : (e.last_message || e.job_title || 'Tap to chat'))}</div>
                        ${unread ? `<span class="dot">${unread > 99 ? '99+' : unread}</span>` : ''}
                    </div>
                </div>
            </div>`;
        });
    }

    if (!html) {
        html = `<div class="wa-empty-side">${listFilter === 'online' ? 'No one is online right now' : 'No employees found'}</div>`;
    }
    $('#contactList').html(html);
    renderLiveCallsBanner(contactsCache.live_calls);
    syncChatOnlineStatus();
}

function loadContacts(keepScroll) {
    const list = document.getElementById('contactList');
    const top = keepScroll ? list.scrollTop : 0;
    $.getJSON(routes.contacts, function (data) {
        contactsCache = {
            employees: data.employees || [],
            groups: data.groups || [],
            call_history: data.call_history || [],
            live_calls: data.live_calls || [],
            on_call_ids: data.on_call_ids || [],
        };
        window.contactsCache = contactsCache;
        const totalUnread = (contactsCache.employees || []).reduce((s, e) => s + Number(e.unread || 0), 0)
            + (contactsCache.groups || []).reduce((s, g) => s + Number(g.unread || 0), 0);
        if (lastUnreadContactsTotal !== null && totalUnread > lastUnreadContactsTotal) {
            playChatMessageSound();
        }
        lastUnreadContactsTotal = totalUnread;
        renderContactList();
        if (keepScroll) list.scrollTop = top;
    });
}

function highlightMentions(text) {
    return esc(text).replace(/@([A-Za-z][A-Za-z0-9_.\-]*(?:\s+[A-Za-z][A-Za-z0-9_.\-]*)?)/g, '<span style="color:#0b5cab;font-weight:600">@$1</span>');
}

function normalizeChatText(text) {
    return String(text || '')
        .replace(/\r\n/g, '\n')
        .replace(/[ \t]+\n/g, '\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

function isAutoMediaCaption(raw) {
    const t = String(raw || '').trim();
    if (!t) return true;
    return /^(photo|voice-note|video-clip|image|audio|video)(\.[a-z0-9]+)?$/i.test(t)
        || /\.(jpe?g|png|gif|webp|webm|mp4|mov|avi|ogg|mp3|wav|m4a|aac)$/i.test(t);
}

function renderBody(m) {
    if (Number(m.is_deleted) === 1) {
        return { html: '<span class="deleted">This message was deleted</span>', isMedia: false, hasCaption: false };
    }
    if (m.message_type === 'call') {
        let info = {};
        try { info = JSON.parse(m.message || '{}'); } catch (e) { info = {}; }
        const icon = info.call_type === 'video' ? 'fa-video' : 'fa-phone';
        const label = info.label || (info.call_type === 'video' ? 'Video call' : 'Audio call');
        const detail = info.detail || info.status || '';
        return {
            html: `<div class="wa-call-msg"><i class="fa-solid ${icon}"></i> ${esc(label)}${detail ? ' · ' + esc(detail) : ''}</div>`,
            isMedia: false,
            hasCaption: false,
        };
    }
    const raw = normalizeChatText(m.message || '');
    if (m.file_path) {
        const url = fileUrl(m.file_path);
        const showCaption = raw && !isAutoMediaCaption(raw);
        const caption = showCaption ? `<div class="wa-media-caption">${highlightMentions(raw)}</div>` : '';
        if (m.message_type === 'image') {
            return {
                html: `<a href="${url}" target="_blank" rel="noopener"><img class="media" src="${url}" alt="image"></a>${caption}`,
                isMedia: true,
                hasCaption: showCaption,
            };
        }
        if (m.message_type === 'audio') {
            return {
                html: `<audio class="media" controls src="${url}"></audio>${caption}`,
                isMedia: true,
                hasCaption: showCaption,
            };
        }
        if (m.message_type === 'video') {
            return {
                html: `<video class="media" controls preload="metadata" src="${url}"></video>${caption}`,
                isMedia: true,
                hasCaption: showCaption,
            };
        }
        return {
            html: `<a class="file-link" href="${url}" target="_blank" rel="noopener"><i class="fa-solid fa-file"></i> ${esc(raw || 'Attachment')}</a>`,
            isMedia: false,
            hasCaption: false,
        };
    }
    return {
        html: `<span class="wa-text">${highlightMentions(raw)}</span>`,
        isMedia: false,
        hasCaption: false,
    };
}

const MAX_FILE_BYTES = 50 * 1024 * 1024; // 50 MB (video clips)
const MAX_LINES = 100;
const MAX_VIDEO_REC_SEC = 60;
const MAX_AUDIO_REC_SEC = 300;

let mediaStream = null;
let mediaRecorder = null;
let mediaChunks = [];
let mediaTimer = null;
let mediaElapsed = 0;
let mediaMode = null; // audio | video | photo
let capturedPhotoBlob = null;

function fmtMmSs(sec) {
    const s = Math.max(0, Math.floor(sec));
    const m = Math.floor(s / 60);
    const r = s % 60;
    return String(m).padStart(2, '0') + ':' + String(r).padStart(2, '0');
}

function stopMediaTracks() {
    if (mediaStream) {
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
    }
}

function clearMediaTimer() {
    if (mediaTimer) {
        clearInterval(mediaTimer);
        mediaTimer = null;
    }
    mediaElapsed = 0;
}

function pickMime(kinds) {
    if (!window.MediaRecorder) return '';
    for (let i = 0; i < kinds.length; i++) {
        if (MediaRecorder.isTypeSupported(kinds[i])) return kinds[i];
    }
    return '';
}

function blobToFile(blob, name) {
    return new File([blob], name, { type: blob.type || 'application/octet-stream', lastModified: Date.now() });
}

function hidePhotoMenu() {
    $('#photoMenu').removeClass('show');
}
function hideAttachRail() {
    $('#attachRail').removeClass('show');
    $('#attachMenuBtn').removeClass('is-open');
}
function hideEmojiPanel() {
    $('#emojiPanel').removeClass('show');
}
function hideComposerMenus() {
    hidePhotoMenu();
    hideAttachRail();
    hideEmojiPanel();
}
function insertEmojiAtCursor(emoji) {
    const el = document.getElementById('chatInput');
    if (!el || el.disabled) return;
    const start = el.selectionStart ?? el.value.length;
    const end = el.selectionEnd ?? el.value.length;
    const val = el.value || '';
    el.value = val.slice(0, start) + emoji + val.slice(end);
    const pos = start + emoji.length;
    el.focus();
    try { el.setSelectionRange(pos, pos); } catch (e) {}
    if (typeof autoGrow === 'function') autoGrow(el);
    if (typeof updateLineHint === 'function') updateLineHint();
}
function buildEmojiPanel() {
    const emojis = ['😀','😁','😂','🤣','😊','😍','😘','😎','🤩','🤗','😇','🙂','😉','🙌','👍','👎','👏','🙏','🔥','✨','🎉','❤️','💙','💚','💛','🧡','💜','🖤','💯','✅','❌','⚡','🌟','💫','🎵','📸','🎥','📞','💬','📌','🥳','😢','😭','😤','😴','🤔','👀','💪'];
    const grid = $('#emojiGrid').empty();
    emojis.forEach((em) => {
        grid.append(`<button type="button" data-emoji="${em}">${em}</button>`);
    });
}
buildEmojiPanel();

function showRecBar(label) {
    $('#recLabel').text(label || 'Recording…');
    $('#recTimer').text('00:00');
    $('#recBar').addClass('show');
}

function hideRecBar() {
    $('#recBar').removeClass('show');
    $('#audioRecBtn').removeClass('is-recording');
}

async function startAudioRecording() {
    if (!current) return;
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        stopAudioRecording(true);
        return;
    }
    hidePhotoMenu();
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (err) {
        alert('Microphone permission is required to record audio.');
        return;
    }
    mediaChunks = [];
    mediaMode = 'audio';
    const mime = pickMime(['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']);
    try {
        mediaRecorder = mime
            ? new MediaRecorder(mediaStream, { mimeType: mime, audioBitsPerSecond: 64000 })
            : new MediaRecorder(mediaStream);
    } catch (err) {
        stopMediaTracks();
        alert('Audio recording is not supported in this browser.');
        return;
    }
    mediaRecorder.ondataavailable = (e) => { if (e.data && e.data.size) mediaChunks.push(e.data); };
    mediaRecorder.onstop = () => {
        const type = (mediaRecorder && mediaRecorder.mimeType) || mime || 'audio/webm';
        const blob = new Blob(mediaChunks, { type });
        stopMediaTracks();
        hideRecBar();
        mediaRecorder = null;
        if (!blob.size) return;
        const ext = type.includes('ogg') ? 'ogg' : (type.includes('mp4') ? 'm4a' : 'webm');
        pickFile(blobToFile(blob, 'voice-note.' + ext));
    };
    mediaRecorder.start(250);
    $('#audioRecBtn').addClass('is-recording');
    showRecBar('Recording audio…');
    clearMediaTimer();
    mediaTimer = setInterval(() => {
        mediaElapsed += 1;
        $('#recTimer').text(fmtMmSs(mediaElapsed));
        if (mediaElapsed >= MAX_AUDIO_REC_SEC) stopAudioRecording(true);
    }, 1000);
}

function stopAudioRecording(keep) {
    clearMediaTimer();
    if (!mediaRecorder) {
        stopMediaTracks();
        hideRecBar();
        return;
    }
    const rec = mediaRecorder;
    if (!keep) {
        rec.ondataavailable = null;
        rec.onstop = () => {
            stopMediaTracks();
            hideRecBar();
            mediaRecorder = null;
        };
    }
    if (rec.state !== 'inactive') rec.stop();
    else {
        stopMediaTracks();
        hideRecBar();
        mediaRecorder = null;
    }
}

async function openCameraPhoto() {
    hidePhotoMenu();
    capturedPhotoBlob = null;
    $('#cameraPhotoPreview, #cameraPhotoCanvas').addClass('d-none');
    $('#cameraPhotoVideo').removeClass('d-none');
    $('#cameraPhotoLiveActions').removeClass('d-none');
    $('#cameraPhotoReviewActions').addClass('d-none');
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' } },
            audio: false,
        });
    } catch (err) {
        alert('Camera permission is required to take a photo.');
        return;
    }
    const video = document.getElementById('cameraPhotoVideo');
    video.srcObject = mediaStream;
    await video.play().catch(() => {});
    $('#cameraPhotoOverlay').addClass('is-open').attr('aria-hidden', 'false');
}

function closeCameraPhoto() {
    const video = document.getElementById('cameraPhotoVideo');
    if (video) video.srcObject = null;
    stopMediaTracks();
    capturedPhotoBlob = null;
    $('#cameraPhotoOverlay').removeClass('is-open').attr('aria-hidden', 'true');
}

function snapCameraPhoto() {
    const video = document.getElementById('cameraPhotoVideo');
    const canvas = document.getElementById('cameraPhotoCanvas');
    if (!video || !canvas || !video.videoWidth) return;
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    canvas.toBlob((blob) => {
        if (!blob) return;
        capturedPhotoBlob = blob;
        const url = URL.createObjectURL(blob);
        $('#cameraPhotoPreview').attr('src', url).removeClass('d-none');
        $('#cameraPhotoVideo').addClass('d-none');
        $('#cameraPhotoLiveActions').addClass('d-none');
        $('#cameraPhotoReviewActions').removeClass('d-none');
        stopMediaTracks();
        if (video) video.srcObject = null;
    }, 'image/jpeg', 0.92);
}

async function openVideoRecorder() {
    hidePhotoMenu();
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        alert('Stop the current recording first.');
        return;
    }
    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'user' }, width: { ideal: 640 }, height: { ideal: 480 } },
            audio: true,
        });
    } catch (err) {
        alert('Camera/microphone permission is required to record video.');
        return;
    }
    mediaMode = 'video';
    mediaChunks = [];
    mediaElapsed = 0;
    $('#videoRecTimer').text('00:00 / 01:00');
    $('#videoRecToggle').removeClass('is-on');
    const video = document.getElementById('videoRecPreview');
    video.srcObject = mediaStream;
    video.muted = true;
    await video.play().catch(() => {});
    $('#videoRecOverlay').addClass('is-open').attr('aria-hidden', 'false');
}

function closeVideoRecorder(discard) {
    clearMediaTimer();
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        const rec = mediaRecorder;
        if (discard) {
            rec.ondataavailable = null;
            rec.onstop = () => { mediaRecorder = null; };
        }
        try { rec.stop(); } catch (e) {}
    }
    mediaRecorder = null;
    const video = document.getElementById('videoRecPreview');
    if (video) video.srcObject = null;
    stopMediaTracks();
    $('#videoRecToggle').removeClass('is-on');
    $('#videoRecOverlay').removeClass('is-open').attr('aria-hidden', 'true');
}

function startVideoRecording() {
    if (!mediaStream) return;
    mediaChunks = [];
    const mime = pickMime([
        'video/webm;codecs=vp8,opus',
        'video/webm;codecs=vp9,opus',
        'video/webm',
        'video/mp4',
    ]);
    try {
        mediaRecorder = mime
            ? new MediaRecorder(mediaStream, { mimeType: mime, videoBitsPerSecond: 700000, audioBitsPerSecond: 64000 })
            : new MediaRecorder(mediaStream);
    } catch (err) {
        alert('Video recording is not supported in this browser.');
        return;
    }
    mediaRecorder.ondataavailable = (e) => { if (e.data && e.data.size) mediaChunks.push(e.data); };
    mediaRecorder.onstop = () => {
        const type = (mediaRecorder && mediaRecorder.mimeType) || mime || 'video/webm';
        const blob = new Blob(mediaChunks, { type });
        mediaRecorder = null;
        $('#videoRecToggle').removeClass('is-on');
        clearMediaTimer();
        const video = document.getElementById('videoRecPreview');
        if (video) video.srcObject = null;
        stopMediaTracks();
        $('#videoRecOverlay').removeClass('is-open').attr('aria-hidden', 'true');
        if (!blob.size) return;
        const ext = type.includes('mp4') ? 'mp4' : 'webm';
        pickFile(blobToFile(blob, 'video-clip.' + ext));
    };
    mediaRecorder.start(250);
    $('#videoRecToggle').addClass('is-on');
    mediaElapsed = 0;
    $('#videoRecTimer').text('00:00 / 01:00');
    clearMediaTimer();
    mediaTimer = setInterval(() => {
        mediaElapsed += 1;
        $('#videoRecTimer').text(fmtMmSs(mediaElapsed) + ' / 01:00');
        if (mediaElapsed >= MAX_VIDEO_REC_SEC) {
            // Auto-stop at 1 minute; user can record next clip after send/clear
            if (mediaRecorder && mediaRecorder.state === 'recording') mediaRecorder.stop();
        }
    }, 1000);
}

function toggleVideoRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        return;
    }
    startVideoRecording();
}

function countLines(text) {
    if (!text) return 0;
    return String(text).split(/\r\n|\r|\n/).length;
}

function updateLineHint() {
    const lines = countLines($('#chatInput').val());
    const hint = $('#lineHint');
    if (lines > 40 || lines > MAX_LINES) {
        hint.text(lines + ' / ' + MAX_LINES + ' lines').addClass('warn').show();
    } else {
        hint.removeClass('warn').hide();
    }
    return lines;
}

function pickFile(file) {
    if (!file) {
        clearAttachment();
        return;
    }
    if (file.size > MAX_FILE_BYTES) {
        alert('File must be 50 MB or smaller.');
        $('#fileInput').val('');
        $('#imageInput').val('');
        clearAttachment();
        return;
    }
    selectedFile = file;
    const kb = Math.round(file.size / 1024);
    $('#selectedFileName').text(file.name + ' (' + (kb >= 1024 ? (file.size / (1024 * 1024)).toFixed(2) + ' MB' : kb + ' KB') + ')');
    $('#fileChip').addClass('show');
}

function openChat(type, id, name, image, sub, online, isCreator) {
    current = {
        type, id: Number(id), name, image: image || '', sub: sub || '',
        online: !!Number(online), is_creator: !!Number(isCreator),
    };
    replyingTo = null;
    updateReplyBar();
    membersOpen = false;
    $('#groupMembersSidebar').removeClass('open');
    $('#waWelcome').remove();
    $('#chatHead, #composerWrap').addClass('is-open');
    $('#chatTitle').text(name);

    if (type === 'group') {
        $('#chatSub').text('Click for group info');
        $('#chatHeadAv').html('<div class="av-wrap"><div class="av group"><i class="fa-solid fa-users"></i></div></div>');
        $('#addMembersAction').toggleClass('d-none', !current.is_creator);
        loadGroupMembers();
    } else {
        $('#chatSub').text(current.online ? 'Online' : (sub || 'Colleague'));
        $('#chatHeadAv').html(`
            <div class="av-wrap">
                <img class="av" src="${esc(image || defaultAv)}" alt="" onerror="this.src='${defaultAv}'">
                <span class="wa-online-dot ${current.online ? 'on' : ''}" id="chatHeadOnline"></span>
            </div>`);
        $('#addMembersAction').addClass('d-none');
        groupMembers = [];
    }

    $('#chatInput, #sendBtn, #attachBtn, #imageBtn, #audioRecBtn, #videoRecBtn, #attachMenuBtn, #emojiBtn').prop('disabled', false);
    $('#waShell').addClass('show-chat');
    lastMsgSignature = '';
    renderContactList();
    loadMessages(true);
    clearInterval(pollTimer);
    pollTimer = setInterval(() => loadMessages(false), 3000);
    setTimeout(() => $('#chatInput').trigger('focus'), 50);
    if (window.HrmChatCall) window.HrmChatCall.updateHeaderButtons();
}

function loadMessages(forceScroll) {
    if (!current) return;
    $.getJSON(routes.messages, { type: current.type, id: current.id }, function (res) {
        const messages = res.messages || [];
        const sig = messages.map(m => `${m.message_id}:${m.is_deleted}:${m.is_edited}:${m.message}`).join('|');
        if (!forceScroll && sig === lastMsgSignature) {
            if (res.unread_count !== undefined) setUnreadBadge(res.unread_count);
            return;
        }

        const chatKey = current.type + ':' + current.id;
        const prevMax = Number(lastMaxMsgIdByChat[chatKey] || 0);
        let maxId = 0;
        messages.forEach((m) => {
            const id = Number(m.message_id || 0);
            if (id > maxId) maxId = id;
        });

        if (!forceScroll && prevMax > 0 && maxId > prevMax) {
            const hasNewFromOther = messages.some((m) => {
                const id = Number(m.message_id || 0);
                return id > prevMax && !isMineMessage(m) && m.message_type !== 'call' && Number(m.is_deleted) !== 1;
            });
            if (hasNewFromOther) playChatMessageSound();
        }
        lastMaxMsgIdByChat[chatKey] = Math.max(prevMax, maxId);
        lastMsgSignature = sig;

        const box = $('#chatMessages');
        const el = box[0];
        const atBottom = forceScroll || (el.scrollHeight - el.scrollTop - el.clientHeight < 100);

        let html = '';
        let lastDay = '';
        messages.forEach(m => {
            const day = dayLabel(m.timestamp);
            if (day && day !== lastDay) {
                html += `<div class="wa-day">${esc(day)}</div>`;
                lastDay = day;
            }
            if (m.message_type === 'call') {
                html += `<div class="wa-bubble-row call">${renderBody(m).html}</div>`;
                return;
            }
            const mine = isMineMessage(m);
            const who = mine ? '' : `${m.fname || m.sender?.fname || ''} ${m.lname || m.sender?.lname || ''}`.trim();
            const deleted = Number(m.is_deleted) === 1;
            const edited = Number(m.is_edited) === 1 && !deleted;
            const ticks = mine
                ? `<span class="wa-ticks ${Number(m.seen) === 1 || m.status === 'read' ? '' : 'sent'}"><i class="fa-solid fa-check-double"></i></span>`
                : '';

            let replyHtml = '';
            if (m.reply_to && m.reply_message) {
                const rn = `${m.reply_fname || ''} ${m.reply_lname || ''}`.trim() || 'Message';
                replyHtml = `<div class="reply-box"><strong>${esc(rn)}</strong>: ${esc(m.reply_message)}</div>`;
            }

            let actions = '';
            if (!deleted) {
                const ownActions = mine
                    ? `<li><a class="dropdown-item" href="#" data-act="edit-msg" data-id="${m.message_id}" data-text="${esc(m.message || '')}">Edit</a></li>
                       <li><a class="dropdown-item text-danger" href="#" data-act="del-msg" data-id="${m.message_id}" data-own="1">Delete</a></li>`
                    : '';
                actions = `<div class="msg-actions dropdown">
                    <a href="#" class="btn-link" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-solid fa-chevron-down"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" data-act="reply" data-id="${m.message_id}" data-text="${esc(m.message || '')}">Reply</a></li>
                        ${ownActions}
                    </ul>
                </div>`;
            }

            const body = renderBody(m);
            const bubbleCls = [
                'wa-bubble',
                mine ? 'me' : 'them',
                deleted ? 'deleted' : '',
                body.isMedia ? 'is-media' : '',
                body.hasCaption ? 'has-caption' : '',
                edited ? 'has-edited' : '',
                /\n/.test(normalizeChatText(m.message || '')) ? 'has-multiline' : '',
            ].filter(Boolean).join(' ');

            html += `
            <div class="wa-bubble-row ${mine ? 'me' : 'them'}">
                <div class="wa-msg ${mine ? 'me' : 'them'}">
                    ${actions}
                    <div class="${bubbleCls}">
                        ${(!mine && current.type === 'group' && who) ? `<div class="who">${esc(who)}</div>` : ''}
                        ${replyHtml}
                        ${body.html}
                        <span class="wa-meta">
                            ${edited ? '<span class="edited">edited</span>' : ''}
                            <span class="wa-meta-time"><time>${esc(formatBubbleTime(m.timestamp))}</time>${ticks}</span>
                        </span>
                    </div>
                </div>
            </div>`;
        });

        if (!html) {
            html = `<div class="wa-welcome"><div class="ico"><i class="fa-regular fa-comment-dots"></i></div>
                <h3>No messages yet</h3><p>Say hello to start the conversation.</p></div>`;
        }
        box.html(html);
        if (atBottom) el.scrollTop = el.scrollHeight;
        if (res.unread_count !== undefined) setUnreadBadge(res.unread_count);
        else refreshUnread();
        loadContacts(true);
    });
}

function setUnreadBadge(n) {
    n = Number(n || 0);
    const badge = $('#unreadBadge');
    if (n > 0) badge.text(n > 99 ? '99+' : n).removeClass('d-none');
    else badge.addClass('d-none');
    const base = document.title.replace(/^\(\d+\)\s*/, '');
    document.title = n > 0 ? `(${n}) ${base}` : base;
    if (typeof window.updateChatUnreadBadge === 'function') {
        window.updateChatUnreadBadge(n);
    }
}

function refreshUnread() {
    $.getJSON(routes.unread, res => setUnreadBadge(res.count ?? res.unread_count ?? 0));
}

function clearAttachment() {
    selectedFile = null;
    $('#fileInput').val('');
    $('#imageInput').val('');
    $('#fileChip').removeClass('show');
    $('#selectedFileName').text('');
}

function updateReplyBar() {
    if (replyingTo) {
        $('#replyPreview').html(`Replying to: <strong>${esc(replyingTo.text || 'message')}</strong>`);
        $('#replyBar').addClass('show');
    } else {
        $('#replyBar').removeClass('show');
        $('#replyPreview').text('');
    }
}

function loadGroupMembers() {
    if (!current || current.type !== 'group') return;
    $.getJSON(routes.groupMembers, { group_id: current.id }, function (members) {
        groupMembers = members || [];
        let html = '';
        groupMembers.forEach(m => {
            const name = m.name || `${m.fname || ''} ${m.lname || ''}`.trim();
            const canRemove = current.is_creator && Number(m.id) !== meId;
            html += `<div class="wa-member-row">
                <div class="left">
                    <img src="${esc(m.image || defaultAv)}" onerror="this.src='${defaultAv}'" alt="">
                    <div class="nm">${esc(name)}${Number(m.id) === meId ? ' (You)' : ''}</div>
                </div>
                ${canRemove ? `<button type="button" class="rm" data-act="remove-member" data-uid="${m.id}" title="Remove"><i class="fa-solid fa-xmark"></i></button>` : ''}
            </div>`;
        });
        $('#groupMembersList').html(html || '<div class="p-3 text-muted">No members</div>');
    });
}

function handleMentionInput() {
    if (!current || current.type !== 'group') {
        $('#mentionSuggestions').addClass('d-none').empty();
        return;
    }
    const val = $('#chatInput').val();
    const m = val.match(/@([A-Za-z0-9_.\-]*)$/);
    if (!m) {
        $('#mentionSuggestions').addClass('d-none').empty();
        return;
    }
    const q = m[1].toLowerCase();
    const matches = groupMembers.filter(mem => {
        const name = (mem.name || `${mem.fname || ''} ${mem.lname || ''}`).trim().toLowerCase();
        return name.includes(q);
    }).slice(0, 8);
    if (!matches.length) {
        $('#mentionSuggestions').addClass('d-none').empty();
        return;
    }
    const html = matches.map(mem => {
        const name = mem.name || `${mem.fname || ''} ${mem.lname || ''}`.trim();
        return `<div class="item" data-name="${esc(name)}">@${esc(name)}</div>`;
    }).join('');
    $('#mentionSuggestions').html(html).removeClass('d-none');
}

$(document).on('click', '.wa-item', function (e) {
    if ($(e.target).closest('.wa-item-menu, .dropdown-menu, .dropdown-item').length) return;
    openChat(
        $(this).data('type'),
        $(this).data('id'),
        $(this).data('name'),
        $(this).data('image'),
        $(this).data('sub'),
        $(this).data('online'),
        $(this).data('creator')
    );
});

$(document).on('click', '.wa-tab', function () {
    $('.wa-tab').removeClass('active');
    $(this).addClass('active');
    listFilter = $(this).data('filter');
    renderContactList();
});

$('#chatSearch').on('input', function () {
    searchQ = this.value || '';
    renderContactList();
});

$('#backBtn').on('click', () => $('#waShell').removeClass('show-chat'));
$('#closeMembersBtn').on('click', () => {
    membersOpen = false;
    $('#groupMembersSidebar').removeClass('open');
});

$('#chatTitleWrap').on('click', function () {
    if (!current || current.type !== 'group') return;
    membersOpen = !membersOpen;
    $('#groupMembersSidebar').toggleClass('open', membersOpen);
    if (membersOpen) loadGroupMembers();
});

$('#attachMenuBtn').on('click', function (e) {
    e.stopPropagation();
    if ($(this).prop('disabled')) return;
    hidePhotoMenu();
    hideEmojiPanel();
    const open = !$('#attachRail').hasClass('show');
    $('#attachRail').toggleClass('show', open);
    $(this).toggleClass('is-open', open);
});
$('#attachBtn').on('click', () => {
    hideComposerMenus();
    $('#fileInput').click();
});
$('#imageBtn').on('click', function (e) {
    e.stopPropagation();
    if ($(this).prop('disabled')) return;
    hideEmojiPanel();
    $('#photoMenu').toggleClass('show');
});
$('#emojiBtn').on('click', function (e) {
    e.stopPropagation();
    if ($(this).prop('disabled')) return;
    hidePhotoMenu();
    $('#emojiPanel').toggleClass('show');
});
$('#emojiGrid').on('click', 'button[data-emoji]', function (e) {
    e.stopPropagation();
    insertEmojiAtCursor($(this).data('emoji'));
});
$(document).on('click', function (e) {
    if (!$(e.target).closest('#photoMenu, #imageBtn, #attachRail, #attachMenuBtn, #emojiPanel, #emojiBtn').length) {
        hideComposerMenus();
    }
});
$('#photoMenu').on('click', 'button[data-photo]', function () {
    const mode = $(this).data('photo');
    hideComposerMenus();
    if (mode === 'gallery') $('#imageInput').click();
    else if (mode === 'camera') openCameraPhoto();
});
$('#fileInput, #imageInput').on('change', function () {
    const other = this.id === 'fileInput' ? '#imageInput' : '#fileInput';
    $(other).val('');
    pickFile(this.files[0] || null);
});
$('#clearFile').on('click', clearAttachment);
$('#cancelReply').on('click', () => { replyingTo = null; updateReplyBar(); });

$('#audioRecBtn').on('click', function () {
    if ($(this).prop('disabled')) return;
    hideComposerMenus();
    startAudioRecording();
});
$('#stopRecBtn').on('click', () => stopAudioRecording(true));
$('#cancelRecBtn').on('click', () => stopAudioRecording(false));

$('#videoRecBtn').on('click', function () {
    if ($(this).prop('disabled')) return;
    hideComposerMenus();
    openVideoRecorder();
});
$('#videoRecToggle').on('click', toggleVideoRecording);
$('#closeVideoRec').on('click', () => closeVideoRecorder(true));

$('#closeCameraPhoto').on('click', closeCameraPhoto);
$('#snapPhotoBtn').on('click', snapCameraPhoto);
$('#retakePhotoBtn').on('click', function () {
    capturedPhotoBlob = null;
    openCameraPhoto();
});
$('#usePhotoBtn').on('click', function () {
    if (!capturedPhotoBlob) return;
    pickFile(blobToFile(capturedPhotoBlob, 'photo.jpg'));
    closeCameraPhoto();
});

$('#chatInput').on('input', function () {
    let val = this.value;
    let lines = countLines(val);
    if (lines > MAX_LINES) {
        const parts = val.split(/\r\n|\r|\n/).slice(0, MAX_LINES);
        val = parts.join('\n');
        this.value = val;
        lines = MAX_LINES;
        alert('Maximum 100 lines allowed per message.');
    }
    autoGrow(this);
    updateLineHint();
    handleMentionInput();
});
$('#chatInput').on('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        $('#chatForm').trigger('submit');
        return;
    }
    if (e.key === 'Enter' && e.shiftKey) {
        if (countLines(this.value) >= MAX_LINES) {
            e.preventDefault();
            alert('Maximum 100 lines allowed per message.');
        }
    }
});

$(document).on('click', '#mentionSuggestions .item', function () {
    const name = $(this).data('name');
    const val = $('#chatInput').val().replace(/@([A-Za-z0-9_.\-]*)$/, '@' + name + ' ');
    $('#chatInput').val(val).focus();
    $('#mentionSuggestions').addClass('d-none').empty();
});

$(document).on('click', '[data-act]', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const act = $(this).data('act');
    const id = $(this).data('id');

    if (act === 'reply') {
        replyingTo = { id, text: $(this).data('text') || '' };
        updateReplyBar();
        $('#chatInput').focus();
    } else if (act === 'edit-msg') {
        $('#editMessageId').val(id);
        $('#editMessageText').val($(this).data('text') || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editMessageModal')).show();
    } else if (act === 'del-msg') {
        // Only the sender may delete their own message
        if (String($(this).data('own')) !== '1') {
            alert('You can only delete your own messages.');
            return;
        }
        if (!confirm('Delete this message?')) return;
        postForm(routes.deleteMessage, { message_id: id }).done(() => loadMessages(true))
            .fail(xhr => alert(xhr.responseJSON?.message || 'You can only delete your own messages.'));
    } else if (act === 'edit-group') {
        $('#editGroupId').val(id);
        $('#editGroupName').val($(this).data('name') || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editGroupModal')).show();
    } else if (act === 'add-members') {
        current = current || { type: 'group', id, is_creator: true };
        if (current.type !== 'group') current = { type: 'group', id: Number(id), is_creator: true, name: '' };
        current.id = Number(id);
        $('.add-member').prop('checked', false);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('addMembersModal')).show();
    } else if (act === 'exit-group') {
        if (!confirm('Exit this group?')) return;
        postForm(routes.exitGroup, { group_id: id }).done(() => {
            if (current && current.type === 'group' && Number(current.id) === Number(id)) {
                current = null;
                $('#chatHead, #composerWrap').removeClass('is-open');
                $('#chatMessages').html(`<div class="wa-welcome" id="waWelcome"><div class="ico"><i class="fa-solid fa-comments"></i></div><h3>HRM Chat</h3><p>Select a colleague or group to start messaging.</p></div>`);
                $('#waShell').removeClass('show-chat');
            }
            loadContacts(false);
        }).fail(xhr => alert(xhr.responseJSON?.message || 'Exit failed'));
    } else if (act === 'delete-group') {
        if (!confirm('Delete this group permanently?')) return;
        postForm(routes.deleteGroup, { group_id: id }).done(() => {
            if (current && current.type === 'group' && Number(current.id) === Number(id)) {
                current = null;
                $('#chatHead, #composerWrap').removeClass('is-open');
                $('#chatMessages').html(`<div class="wa-welcome" id="waWelcome"><div class="ico"><i class="fa-solid fa-comments"></i></div><h3>HRM Chat</h3><p>Select a colleague or group to start messaging.</p></div>`);
                $('#waShell').removeClass('show-chat');
            }
            loadContacts(false);
        }).fail(xhr => alert(xhr.responseJSON?.message || 'Delete failed'));
    } else if (act === 'remove-member') {
        const uid = $(this).data('uid');
        if (!current || !confirm('Remove this member?')) return;
        postForm(routes.removeMember, { group_id: current.id, user_id: uid })
            .done(() => loadGroupMembers())
            .fail(xhr => alert(xhr.responseJSON?.message || 'Remove failed'));
    }
});

$('#clearChatBtn').on('click', function () {
    if (!current || !confirm('Delete only your messages in this chat? Other people\'s messages will stay.')) return;
    postForm(routes.clearChat, { type: current.type, id: current.id })
        .done(() => loadMessages(true))
        .fail(xhr => alert(xhr.responseJSON?.message || 'Clear failed'));
});

$('#openAddMembersBtn').on('click', function () {
    if (!current || current.type !== 'group') return;
    $('.add-member').prop('checked', false);
    bootstrap.Modal.getOrCreateInstance(document.getElementById('addMembersModal')).show();
});

$('#createGroupBtn').on('click', function () {
    const name = $('#groupName').val().trim();
    if (!name) return alert('Group name is required');
    const members = $('.create-member:checked').map(function () { return this.value; }).get();
    postForm(routes.createGroup, { group_name: name, members })
        .done(res => {
            bootstrap.Modal.getInstance(document.getElementById('groupModal'))?.hide();
            $('#groupName').val('');
            $('.create-member').prop('checked', false);
            loadContacts(false);
            if (res.group_id) openChat('group', res.group_id, name, '', 'Group chat', 0, 1);
        })
        .fail(xhr => alert(xhr.responseJSON?.message || 'Create failed'));
});

$('#saveGroupNameBtn').on('click', function () {
    const id = $('#editGroupId').val();
    const new_name = $('#editGroupName').val().trim();
    if (!new_name) return alert('New group name is required');
    postForm(routes.editGroup, { group_id: id, new_name })
        .done(() => {
            bootstrap.Modal.getInstance(document.getElementById('editGroupModal'))?.hide();
            if (current && current.type === 'group' && Number(current.id) === Number(id)) {
                current.name = new_name;
                $('#chatTitle').text(new_name);
            }
            loadContacts(false);
        })
        .fail(xhr => alert(xhr.responseJSON?.message || 'Rename failed'));
});

$('#addMembersBtn').on('click', function () {
    if (!current || current.type !== 'group') return;
    const members = $('.add-member:checked').map(function () { return this.value; }).get();
    if (!members.length) return alert('Select members to add');
    postForm(routes.addMembers, { group_id: current.id, members })
        .done(() => {
            bootstrap.Modal.getInstance(document.getElementById('addMembersModal'))?.hide();
            loadGroupMembers();
        })
        .fail(xhr => alert(xhr.responseJSON?.message || 'Add failed'));
});

$('#saveMessageBtn').on('click', function () {
    const message_id = $('#editMessageId').val();
    const new_message = $('#editMessageText').val().trim();
    if (!new_message) return alert('Message cannot be empty');
    postForm(routes.editMessage, { message_id, new_message })
        .done(() => {
            bootstrap.Modal.getInstance(document.getElementById('editMessageModal'))?.hide();
            loadMessages(true);
        })
        .fail(xhr => alert(xhr.responseJSON?.message || 'Edit failed'));
});

$('#chatForm').on('submit', function (e) {
    e.preventDefault();
    if (!current) return;
    const message = normalizeChatText($('#chatInput').val());
    if (!message && !selectedFile) return;

    if (countLines(message) > MAX_LINES) {
        alert('Maximum 100 lines allowed per message.');
        return;
    }
    if (selectedFile && selectedFile.size > MAX_FILE_BYTES) {
        alert('File must be 50 MB or smaller.');
        clearAttachment();
        return;
    }

    const fd = new FormData();
    fd.append('_token', csrf);
    fd.append('type', current.type);
    fd.append('id', current.id);
    fd.append('message', message);
    if (replyingTo?.id) fd.append('reply_to', replyingTo.id);
    if (selectedFile) fd.append('file', selectedFile);

    $('#sendBtn').prop('disabled', true);
    $.ajax({
        url: routes.send,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function () {
            $('#chatInput').val('');
            autoGrow(document.getElementById('chatInput'));
            updateLineHint();
            clearAttachment();
            replyingTo = null;
            updateReplyBar();
            $('#mentionSuggestions').addClass('d-none').empty();
            loadMessages(true);
        },
        error: function (xhr) {
            alert(xhr.responseJSON?.message || xhr.responseJSON?.error || 'Send failed');
        },
        complete: function () {
            $('#sendBtn').prop('disabled', !current);
        }
    });
});

loadContacts(false);
refreshUnread();
setInterval(() => loadContacts(true), 8000);
setInterval(refreshUnread, 10000);
</script>
<script src="{{ asset('js/chat-call.js') }}?v=20260814p"></script>
<script>
if (window.HrmChatCall) {
    window.HrmChatCall.init({
        meId: meId,
        csrf: csrf,
        defaultAv: defaultAv,
        getCurrent: function () { return current; },
        routes: {
            config: routes.callConfig,
            poll: routes.callPoll,
            start: routes.callStart,
            accept: routes.callAccept,
            reject: routes.callReject,
            end: routes.callEnd,
            signal: routes.callSignal,
            invite: routes.callInvite,
            groupMembers: routes.groupMembers,
            contacts: routes.contacts,
        },
    });
}
</script>
@endpush
