{{-- Shared traditional ticket UI styles --}}
<style>
    .tk-wrap {
        --tk-ink: #172033;
        --tk-muted: #667085;
        --tk-line: #E4E7EC;
        --tk-soft: #F9FAFB;
        --tk-accent: #2563EB;
        --tk-card: #ffffff;
        --tk-panel-head: #F9FAFB;
        --tk-input: #ffffff;
        --tk-hover: #F9FAFB;
        color: var(--tk-ink);
    }
    .tk-wrap .tk-metric {
        position: relative; overflow: hidden; border-radius: 8px; background: var(--tk-card);
        border: 1px solid var(--tk-line); padding: 1rem 1.1rem;
        min-height: 96px; height: 100%;
        display: flex; flex-direction: column; justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
        box-sizing: border-box; color: var(--tk-ink);
    }
    .tk-wrap .tk-metric:hover { box-shadow: 0 2px 10px rgba(0,0,0,.1); }
    .tk-wrap .tk-metric::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
    }
    .tk-wrap .tk-metric.is-open::before { background: #36C76F; }
    .tk-wrap .tk-metric.is-progress::before { background: #F79009; }
    .tk-wrap .tk-metric.is-resolved::before { background: #667085; }
    .tk-wrap .tk-metric.is-closed::before { background: #FF3150; }
    .tk-wrap .tk-metric .tk-label {
        font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        color: var(--tk-muted); margin: 0 0 .4rem; padding-right: 48px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.2;
        min-height: 1.2em;
    }
    .tk-wrap .tk-metric .tk-value {
        font-size: 1.55rem; font-weight: 700; color: #172033;
        line-height: 1; margin: 0; min-height: 1.55rem;
    }
    .tk-wrap .tk-metric .tk-icon {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        width: 34px; height: 34px; border-radius: 6px;
        display: grid; place-items: center; font-size: .9rem; flex-shrink: 0;
    }
    .tk-wrap .tk-metric.is-open .tk-icon { background: #ecfdf3; color: #027a48; }
    .tk-wrap .tk-metric.is-progress .tk-icon { background: #fff7ed; color: #b45309; }
    .tk-wrap .tk-metric.is-resolved .tk-icon { background: #f5f7fa; color: #667085; }
    .tk-wrap .tk-metric.is-closed .tk-icon { background: #fef3f2; color: #b42318; }
    .tk-wrap .tk-metrics { align-items: stretch; }
    .tk-wrap .tk-metrics > [class*="col-"] { display: flex; }
    .tk-wrap .tk-metrics > [class*="col-"] > .tk-metric { width: 100%; }

    .tk-wrap .tk-panel {
        background: var(--tk-card); border: 1px solid var(--tk-line); border-radius: 8px; overflow: hidden;
        color: var(--tk-ink); box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .tk-wrap .tk-panel-head {
        display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
        padding: .85rem 1.1rem; border-bottom: 1px solid var(--tk-line); background: #F9FAFB; color: #172033;
    }
    .tk-wrap .tk-panel-head h5 { margin: 0; font-weight: 700; color: #172033; font-size: 1rem; }
    .tk-wrap .tk-panel-body { padding: 1rem 1.1rem; background: var(--tk-card); }

    .tk-wrap .tk-search {
        display: flex; gap: .55rem; flex-wrap: wrap; align-items: center;
        background: #fff; border: 1px solid var(--tk-line); border-radius: 8px; padding: .65rem .75rem;
        color: var(--tk-ink); box-shadow: 0 2px 8px rgba(0,0,0,.06);
    }
    .tk-wrap .tk-search .form-control, .tk-wrap .tk-search .form-select {
        border-radius: 6px; border-color: var(--tk-line); min-height: 38px;
        background: #fff; color: var(--tk-ink);
    }
    .tk-wrap .tk-search .form-control::placeholder { color: var(--tk-muted); }

    .tk-wrap .tk-pill {
        display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .5rem;
        border-radius: 4px; font-size: .72rem; font-weight: 700; letter-spacing: .02em; line-height: 1.2;
    }
    .tk-wrap .tk-pill.dot::before {
        content: ""; width: 6px; height: 6px; border-radius: 50%; background: currentColor;
    }
    .tk-wrap .tk-pill.status-open { background: #dcfce7; color: #15803d; }
    .tk-wrap .tk-pill.status-in-progress { background: #fef3c7; color: #b45309; }
    .tk-wrap .tk-pill.status-resolved { background: #e2e8f0; color: #475569; }
    .tk-wrap .tk-pill.status-closed { background: #fee2e2; color: #b91c1c; }
    .tk-wrap .tk-pill.status-reopened { background: #e0e7ff; color: #4338ca; }
    .tk-wrap .tk-pill.prio-low { background: #e0f2fe; color: #0369a1; }
    .tk-wrap .tk-pill.prio-medium { background: #ffedd5; color: #c2410c; }
    .tk-wrap .tk-pill.prio-high { background: #fee2e2; color: #b91c1c; }

    .tk-wrap .tk-row {
        display: grid; grid-template-columns: 72px 1.4fr .9fr .9fr .7fr .85fr auto;
        gap: .75rem; align-items: center; padding: .95rem 1.1rem;
        border-bottom: 1px solid var(--tk-line); transition: background .15s ease;
        background: var(--tk-card); color: var(--tk-ink);
    }
    .tk-wrap .tk-row:last-child { border-bottom: 0; }
    .tk-wrap .tk-row:hover { background: var(--tk-hover); }
    .tk-wrap .tk-row:hover .tk-title,
    .tk-wrap .tk-row:hover .tk-sub { color: inherit; }
    .tk-wrap .tk-id {
        font-weight: 800; color: var(--tk-accent); font-size: .95rem;
        font-variant-numeric: tabular-nums;
    }
    .tk-wrap .tk-title { font-weight: 650; color: var(--tk-ink); margin: 0 0 .15rem; font-size: .95rem; }
    .tk-wrap .tk-sub { color: var(--tk-muted); font-size: .78rem; margin: 0; }
    .tk-wrap .tk-actions { display: flex; gap: .4rem; justify-content: flex-end; align-items: center; flex-wrap: wrap; }
    .tk-wrap .tk-btn {
        border-radius: 10px; font-weight: 600; font-size: .8rem; padding: .4rem .75rem;
        border: 1px solid transparent; display: inline-flex; align-items: center; gap: .35rem;
    }
    .tk-wrap .tk-btn-primary { background: var(--tk-accent); color: #fff; }
    .tk-wrap .tk-btn-primary:hover { background: #ff8326; color: #fff; }
    .tk-wrap .tk-btn-ghost { background: var(--tk-card); border-color: var(--tk-line); color: var(--tk-ink); }
    .tk-wrap .tk-btn-ghost:hover { background: var(--tk-soft); color: var(--tk-ink); }
    .tk-wrap .status-select {
        border-radius: 10px; border-color: var(--tk-line); font-size: .78rem; min-width: 120px; padding: .35rem .5rem;
        background: var(--tk-input); color: var(--tk-ink);
    }

    .tk-wrap .tk-create {
        background: var(--tk-soft);
        border: 1px solid var(--tk-line); border-radius: 18px; padding: 1.15rem;
    }
    .tk-wrap .tk-create h5 { font-weight: 750; color: var(--tk-ink); margin-bottom: .15rem; }
    .tk-wrap .tk-create .tk-hint { color: var(--tk-muted); font-size: .82rem; margin-bottom: 1rem; }

    .tk-wrap .tk-hero {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        background: var(--tk-soft);
        border: 1px solid var(--tk-line); border-radius: 18px; padding: 1.25rem 1.35rem;
        margin-bottom: 1rem;
    }
    .tk-wrap .tk-hero h3 { margin:0 0 .25rem; font-weight:800; color:var(--tk-ink); font-size:1.25rem; }
    .tk-wrap .tk-hero p { margin:0; color:var(--tk-muted); font-size:.9rem; max-width:42rem; }
    .tk-wrap .tk-filter-pills { display:flex; flex-wrap:wrap; gap:.45rem; }
    .tk-wrap .tk-filter-pill {
        display:inline-flex; align-items:center; gap:.35rem; padding:.4rem .85rem; border-radius:999px;
        border:1px solid var(--tk-line); background:var(--tk-card); color:var(--tk-muted);
        font-size:.78rem; font-weight:700; text-decoration:none;
    }
    .tk-wrap .tk-filter-pill:hover { color:var(--tk-ink); border-color:var(--tk-accent); }
    .tk-wrap .tk-filter-pill.active {
        background:#fff7ed; border-color:#ffd7b0; color:#c2410c;
    }
    .tk-wrap .tk-prio-group { display:flex; flex-wrap:wrap; gap:.5rem; }
    .tk-wrap .tk-prio-option { position:relative; cursor:pointer; margin:0; }
    .tk-wrap .tk-prio-option input { position:absolute; opacity:0; pointer-events:none; }
    .tk-wrap .tk-prio-option span {
        display:inline-flex; align-items:center; padding:.45rem .9rem; border-radius:999px;
        border:1px solid var(--tk-line); background:var(--tk-card); font-size:.8rem; font-weight:700;
        color:var(--tk-muted); transition: all .15s ease;
    }
    .tk-wrap .tk-prio-option input:checked + span.prio-low { background:#e0f2fe; color:#0369a1; border-color:#bae6fd; }
    .tk-wrap .tk-prio-option input:checked + span.prio-medium { background:#ffedd5; color:#c2410c; border-color:#fed7aa; }
    .tk-wrap .tk-prio-option input:checked + span.prio-high { background:#fee2e2; color:#b91c1c; border-color:#fecaca; }
    .tk-wrap .tk-prio-option:hover span { border-color:var(--tk-accent); }
    .tk-wrap .form-label { font-size:.8rem; font-weight:600; color:var(--tk-muted); }
    .tk-wrap .form-control, .tk-wrap .form-select {
        background: var(--tk-input); color: var(--tk-ink); border-color: var(--tk-line);
    }

    .tk-wrap .tk-comment {
        border: 1px solid var(--tk-line); border-radius: 14px; padding: .85rem 1rem;
        background: var(--tk-soft); margin-bottom: .7rem; color: var(--tk-ink);
    }
    .tk-wrap .tk-comment .meta { font-size: .78rem; color: var(--tk-muted); margin-bottom: .35rem; font-weight: 600; }
    .tk-wrap .tk-avatar {
        width: 34px; height: 34px; border-radius: 10px; background: #ffe8d4; color: #c2410c;
        display: grid; place-items: center; font-weight: 800; font-size: .8rem; flex-shrink: 0;
    }
    .tk-wrap .tk-detail-grid {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem;
    }
    .tk-wrap .tk-meta-card {
        background: var(--tk-soft); border: 1px solid var(--tk-line); border-radius: 12px; padding: .75rem .9rem;
    }
    .tk-wrap .tk-meta-card .k { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: var(--tk-muted); font-weight: 700; margin-bottom: .2rem; }
    .tk-wrap .tk-meta-card .v { color: var(--tk-ink); font-weight: 650; margin: 0; font-size: .9rem; }
    .tk-wrap .tk-empty {
        text-align: center; padding: 2.5rem 1rem; color: var(--tk-muted);
    }
    .tk-wrap .tk-empty i { font-size: 1.8rem; margin-bottom: .5rem; opacity: .55; display: block; }

    .tk-wrap .table { color: var(--tk-ink); --bs-table-bg: transparent; --bs-table-color: var(--tk-ink); --bs-table-border-color: var(--tk-line); --bs-table-hover-bg: var(--tk-hover); --bs-table-hover-color: var(--tk-ink); }
    .tk-wrap .table thead th { background: var(--tk-soft) !important; color: var(--tk-muted) !important; border-color: var(--tk-line) !important; }
    .tk-wrap .table td, .tk-wrap .table th { border-color: var(--tk-line) !important; color: var(--tk-ink); background: transparent; }

    .tk-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; background: var(--card, #fff); color: var(--ink, #0f2744); }
    .tk-modal .modal-header { border-bottom: 1px solid var(--line, #e8eef5); background: var(--soft, #fafbfd); color: var(--ink, #0f2744); }
    .tk-modal .modal-body { background: var(--card, #fff); color: var(--ink, #0f2744); }
    .tk-modal .modal-footer { border-top: 1px solid var(--line, #e8eef5); background: var(--card, #fff); }
    .tk-modal .form-control, .tk-modal .form-select {
        background: var(--input-bg, #fff); color: var(--ink, #0f2744); border-color: var(--line, #e8eef5);
    }

    html[data-theme="dark"] .tk-wrap {
        --tk-ink: #e8eef8;
        --tk-muted: #a8b6cc;
        --tk-line: #243044;
        --tk-soft: #1a2232;
        --tk-card: #141b27;
        --tk-panel-head: linear-gradient(180deg, #171e2c, #141b27);
        --tk-input: #0f1520;
        --tk-hover: #1a2232;
    }
    html[data-theme="dark-blue"] .tk-wrap {
        --tk-ink: #eaf2ff;
        --tk-muted: #9db4d4;
        --tk-line: #1a3358;
        --tk-soft: #102240;
        --tk-card: #0c1a31;
        --tk-panel-head: linear-gradient(180deg, #0e1f3c, #0c1a31);
        --tk-input: #081528;
        --tk-hover: #102240;
    }
    html[data-theme="light"] .tk-wrap {
        --tk-ink: #0f2744;
        --tk-muted: #6b7c93;
        --tk-line: #e8eef5;
        --tk-soft: #f4f7fb;
        --tk-card: #ffffff;
        --tk-panel-head: linear-gradient(180deg, #fff, #fafbfd);
        --tk-input: #ffffff;
        --tk-hover: #fafbfd;
    }

    html[data-theme="dark"] .tk-wrap .tk-tab.active,
    html[data-theme="dark-blue"] .tk-wrap .tk-tab.active {
        background: var(--tk-card); color: #fdba74; border-color: rgba(255,155,68,.45);
    }
    html[data-theme="dark"] .tk-wrap .tk-filter-pill.active,
    html[data-theme="dark-blue"] .tk-wrap .tk-filter-pill.active {
        background: rgba(255,155,68,.16); border-color: rgba(255,155,68,.45); color: #fdba74;
    }

    @media (max-width: 991px) {
        .tk-wrap .tk-row {
            grid-template-columns: 1fr 1fr;
            gap: .5rem .75rem;
        }
        .tk-wrap .tk-row > :nth-child(1) { grid-column: 1; }
        .tk-wrap .tk-row > :nth-child(2) { grid-column: 2; text-align: right; }
        .tk-wrap .tk-row > :nth-child(n+3) { grid-column: 1 / -1; }
        .tk-wrap .tk-actions { justify-content: flex-start; }
        .tk-wrap .tk-detail-grid { grid-template-columns: 1fr; }
    }
</style>
