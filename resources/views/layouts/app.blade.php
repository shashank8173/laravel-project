<!DOCTYPE html>
@php
    $uiTheme = auth()->user()?->ui_theme ?: 'light';
    if (! in_array($uiTheme, ['light', 'dark', 'dark-blue'], true)) {
        $uiTheme = 'light';
    }
@endphp
<html lang="en" data-theme="{{ $uiTheme }}"
      data-emp-lookup-url="{{ url('/lookups/employees') }}"
      data-emp-filters-url="{{ url('/lookups/employee-filters') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Leadforgrow HRM')</title>
    <link rel="icon" href="{{ $appIconUrl ?? asset('assets/img/logo2.png') }}">
    <link rel="apple-touch-icon" href="{{ $appIconUrl ?? asset('assets/img/logo2.png') }}">
    <script>
        (function () {
            try {
                var t = localStorage.getItem('hrm_ui_theme');
                if (t === 'light' || t === 'dark' || t === 'dark-blue') {
                    document.documentElement.setAttribute('data-theme', t);
                }
            } catch (e) {}
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="{{ asset('css/hrm-rich-editor.css') }}?v=20260815d" rel="stylesheet">
    <link href="{{ asset('css/hrm-employee-select.css') }}?v=20260815a" rel="stylesheet">
    <style>
        :root,
        html[data-theme="light"] {
            --sidebar: #FFFFFF;
            --sidebar-soft: #F9FAFB;
            --sidebar-active: #F1F3F5;
            --primary: #2563EB;
            --primary-hover: #1D4ED8;
            --primary-active: #1E40AF;
            --primary-text: #FFFFFF;
            --accent: #2563EB;
            --ink: #172033;
            --bg: #F7F8FA;
            --card: #ffffff;
            --text: #172033;
            --muted: #667085;
            --border: #E4E7EC;
            --line: #E4E7EC;
            --soft: #F9FAFB;
            --panel-head: #F9FAFB;
            --shadow: rgba(16,24,40,.05);
            --table-head: #F9FAFB;
            --input-bg: #ffffff;
            --hero-grad: #FFFFFF;
            --topbar-bg: #FFFFFF;
            --topbar-text: #172033;
            --icon: #344054;
            --radius: 10px;
            --sidebar-text: #344054;
        }

        html[data-theme="dark"] {
            --sidebar: #111827;
            --sidebar-soft: #1F2937;
            --sidebar-active: #1F2937;
            --primary: #60A5FA;
            --primary-hover: #3B82F6;
            --primary-active: #2563EB;
            --primary-text: #FFFFFF;
            --accent: #60A5FA;
            --ink: #F9FAFB;
            --bg: #0F172A;
            --card: #111827;
            --text: #F9FAFB;
            --muted: #9CA3AF;
            --border: #374151;
            --line: #374151;
            --soft: #1F2937;
            --panel-head: #1F2937;
            --shadow: rgba(0,0,0,.4);
            --table-head: #1F2937;
            --input-bg: #1F2937;
            --hero-grad: #111827;
            --topbar-bg: #111827;
            --topbar-text: #F9FAFB;
            --icon: #D1D5DB;
            --radius: 10px;
            --sidebar-text: #E5E7EB;
        }

        html[data-theme="dark-blue"] {
            --sidebar: #0C1F3D;
            --sidebar-soft: #102D52;
            --sidebar-active: #163B67;
            --primary: #60A5FA;
            --primary-hover: #3B82F6;
            --primary-active: #2563EB;
            --primary-text: #071A2E;
            --accent: #60A5FA;
            --ink: #FFFFFF;
            --bg: #0B1F3A;
            --card: #102D52;
            --text: #FFFFFF;
            --muted: #A8C1DF;
            --border: #28517D;
            --line: #28517D;
            --soft: #163B67;
            --panel-head: #163B67;
            --shadow: rgba(0,20,50,.45);
            --table-head: #163B67;
            --input-bg: #0E2748;
            --hero-grad: #102D52;
            --topbar-bg: #102D52;
            --topbar-text: #FFFFFF;
            --icon: #DBEAFE;
            --radius: 10px;
            --sidebar-text: #DBEAFE;
        }

        * { box-sizing: border-box; }
        body { background: var(--bg); font-family: Inter, Arial, sans-serif; margin: 0; color: var(--text); font-size: 15px; }
        .app-shell { min-height: 100vh; display: flex; }

        /* Themed surfaces */
        .page-header .page-title { color: var(--ink) !important; }
        .breadcrumb a { color: var(--ink) !important; }
        .breadcrumb .breadcrumb-item.active { color: var(--muted) !important; }
        .breadcrumb .breadcrumb-item + .breadcrumb-item::before { color: var(--muted) !important; }
        .stat-card, .card-soft, .card, .stats-info {
            background: var(--card) !important;
            border-color: var(--border) !important;
            color: var(--text);
        }
        .card-header-soft, .card-header {
            background: var(--soft) !important;
            border-color: var(--border) !important;
            color: var(--ink) !important;
        }
        .stats-info h6 { color: var(--muted) !important; }
        .stats-info h4, .stats-info .value, .stat-card .value { color: var(--ink) !important; }
        .table { color: var(--text); --bs-table-bg: transparent; --bs-table-color: var(--text); --bs-table-border-color: var(--line); }
        .table thead th { background: var(--table-head) !important; color: var(--muted) !important; border-color: var(--line) !important; }
        .table td, .table th { border-color: var(--line) !important; color: var(--text); }
        .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-bg-type: var(--soft);
            color: var(--text);
        }
        .form-control, .form-select {
            background: var(--input-bg) !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: #93C5FD !important;
            box-shadow: 0 0 0 .2rem rgba(37,99,235,.15) !important;
            background: var(--input-bg) !important;
            color: var(--ink) !important;
        }
        .form-control:disabled, .form-select:disabled, .form-control[readonly] {
            background: var(--soft) !important;
            color: var(--muted) !important;
        }
        .form-label, .text-muted, .small.text-muted { color: var(--muted) !important; }
        .modal-content {
            background: var(--card) !important;
            color: var(--text) !important;
            border-color: var(--border) !important;
        }
        .modal-header, .modal-footer { border-color: var(--line) !important; }
        .modal-title { color: var(--ink) !important; }
        .dropdown-menu {
            background: var(--card) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
        }
        .dropdown-item { color: var(--ink) !important; }
        .dropdown-item:hover, .dropdown-item:focus {
            background: var(--soft) !important;
            color: var(--ink) !important;
        }
        .btn-outline-secondary {
            border-color: var(--line) !important;
            color: var(--ink) !important;
            background: transparent;
        }
        .btn-outline-secondary:hover {
            background: var(--soft) !important;
            border-color: #ffd0a8 !important;
            color: var(--ink) !important;
        }
        .btn-close {
            filter: var(--bs-btn-close-filter, none);
        }
        html[data-theme="dark"] .btn-close,
        html[data-theme="dark-blue"] .btn-close {
            filter: invert(1) grayscale(100%);
        }
        .pagination .page-link {
            background: var(--card);
            border-color: var(--line);
            color: var(--ink);
        }
        .pagination .page-item.active .page-link {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }
        .pagination .page-item.disabled .page-link {
            background: var(--soft);
            color: var(--muted);
            border-color: var(--line);
        }
        .alert { border-color: var(--line); }
        a:not(.btn):not(.dropdown-item):not(.nav-link):not(.page-link) { color: #3b82f6; }
        html[data-theme="dark"] a:not(.btn):not(.dropdown-item):not(.nav-link):not(.page-link),
        html[data-theme="dark-blue"] a:not(.btn):not(.dropdown-item):not(.nav-link):not(.page-link) {
            color: #7dd3fc;
        }

        /* Common panel / hero surfaces inside page wraps */
        html[data-theme="dark"] [class*="-panel"],
        html[data-theme="dark-blue"] [class*="-panel"],
        html[data-theme="dark"] [class*="-metric"],
        html[data-theme="dark-blue"] [class*="-metric"],
        html[data-theme="dark"] [class*="-card"],
        html[data-theme="dark-blue"] [class*="-card"],
        html[data-theme="dark"] [class*="-section"],
        html[data-theme="dark-blue"] [class*="-section"] {
            background: var(--card) !important;
            border-color: var(--line) !important;
            color: var(--text);
        }
        html[data-theme="dark"] [class*="-panel-head"],
        html[data-theme="dark-blue"] [class*="-panel-head"] {
            background: var(--panel-head) !important;
            border-color: var(--line) !important;
        }
        html[data-theme="dark"] .att-hero,
        html[data-theme="dark"] .lv-hero,
        html[data-theme="dark"] .ex-hero,
        html[data-theme="dark"] .pf-hero,
        html[data-theme="dark"] .hs-hero,
        html[data-theme="dark"] .co-hero,
        html[data-theme="dark"] .br-hero,
        html[data-theme="dark"] .db-hero,
        html[data-theme="dark-blue"] .att-hero,
        html[data-theme="dark-blue"] .lv-hero,
        html[data-theme="dark-blue"] .ex-hero,
        html[data-theme="dark-blue"] .pf-hero,
        html[data-theme="dark-blue"] .hs-hero,
        html[data-theme="dark-blue"] .co-hero,
        html[data-theme="dark-blue"] .br-hero,
        html[data-theme="dark-blue"] .db-hero {
            background: var(--card) !important;
            background-image: none !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }

        /* Force wrap tokens after local hardcodes by re-declaring on html theme */
        html[data-theme="dark"] [class*="-wrap"],
        html[data-theme="dark-blue"] [class*="-wrap"] {
            --ink: #F9FAFB !important;
            --muted: #9CA3AF !important;
            --line: #374151 !important;
            --soft: #1F2937 !important;
            --card: #111827 !important;
            --panel-head: #1F2937 !important;
            --table-head: #1F2937 !important;
            --input-bg: #1F2937 !important;
            --accent: #60A5FA !important;
            --hover: #1F2937 !important;
            color: #F9FAFB;
        }
        html[data-theme="dark"] [class*="-wrap"] {
            --accent: #60A5FA !important;
            --primary: #60A5FA !important;
        }
        html[data-theme="dark-blue"] [class*="-wrap"] {
            --ink: #FFFFFF !important;
            --muted: #A8C1DF !important;
            --line: #28517D !important;
            --soft: #163B67 !important;
            --card: #102D52 !important;
            --panel-head: #163B67 !important;
            --table-head: #163B67 !important;
            --input-bg: #0E2748 !important;
            --accent: #60A5FA !important;
            --primary: #60A5FA !important;
            --hover: #163B67 !important;
            color: #FFFFFF;
        }
        html[data-theme="light"] [class*="-wrap"] {
            --ink: #172033 !important;
            --muted: #667085 !important;
            --line: #E4E7EC !important;
            --soft: #F9FAFB !important;
            --card: #ffffff !important;
            --panel-head: #F9FAFB !important;
            --table-head: #F9FAFB !important;
            --input-bg: #ffffff !important;
            --accent: #2563EB !important;
            --hover: #F9FAFB !important;
        }

        /*
         * Dark / dark-blue readability: kill hardcoded #fff surfaces so light text is visible.
         * Page CSS often sets background:#fff while --ink flips to light.
         */
        html[data-theme="dark"] [class*="-wrap"] [class*="-panel"],
        html[data-theme="dark"] [class*="-wrap"] [class*="-metric"],
        html[data-theme="dark"] [class*="-wrap"] [class*="-card"],
        html[data-theme="dark"] [class*="-wrap"] [class*="-banner"],
        html[data-theme="dark"] [class*="-wrap"] [class*="-note"],
        html[data-theme="dark"] [class*="-wrap"] [class*="-section"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-panel"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-metric"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-card"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-banner"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-note"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-section"] {
            background: var(--card) !important;
            background-color: var(--card) !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] [class*="-panel-head"],
        html[data-theme="dark"] [class*="-wrap"] [class*="-panel-body"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-panel-head"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-panel-body"] {
            background: var(--panel-head) !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] [class*="-panel-body"],
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-panel-body"] {
            background: var(--card) !important;
        }

        html[data-theme="dark"] [class*="-wrap"] .hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .hero,
        html[data-theme="dark"] [class*="-wrap"] .member-card,
        html[data-theme="dark-blue"] [class*="-wrap"] .member-card {
            background: var(--card) !important;
            background-color: var(--card) !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] .ql-item,
        html[data-theme="dark"] [class*="-wrap"] .att-card,
        html[data-theme="dark"] [class*="-wrap"] .today-tab,
        html[data-theme="dark"] [class*="-wrap"] .today-banner,
        html[data-theme="dark"] [class*="-wrap"] .emp-hero,
        html[data-theme="dark"] [class*="-wrap"] .accordion-item,
        html[data-theme="dark"] [class*="-wrap"] .accordion-button,
        html[data-theme="dark"] [class*="-wrap"] .btn-ghost,
        html[data-theme="dark"] [class*="-wrap"] .social-btn,
        html[data-theme="dark"] [class*="-wrap"] .co-item,
        html[data-theme="dark"] [class*="-wrap"] .form-section,
        html[data-theme="dark"] [class*="-wrap"] .side-card,
        html[data-theme="dark"] [class*="-wrap"] .declare-box,
        html[data-theme="dark"] [class*="-wrap"] .preview-box,
        html[data-theme="dark"] [class*="-wrap"] .date-chip,
        html[data-theme="dark"] [class*="-wrap"] .time-chip,
        html[data-theme="dark"] [class*="-wrap"] .days-chip,
        html[data-theme="dark"] [class*="-wrap"] .year-chip,
        html[data-theme="dark"] [class*="-wrap"] .filter-bar .form-select,
        html[data-theme="dark-blue"] [class*="-wrap"] .ql-item,
        html[data-theme="dark-blue"] [class*="-wrap"] .att-card,
        html[data-theme="dark-blue"] [class*="-wrap"] .today-tab,
        html[data-theme="dark-blue"] [class*="-wrap"] .today-banner,
        html[data-theme="dark-blue"] [class*="-wrap"] .emp-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .accordion-item,
        html[data-theme="dark-blue"] [class*="-wrap"] .accordion-button,
        html[data-theme="dark-blue"] [class*="-wrap"] .btn-ghost,
        html[data-theme="dark-blue"] [class*="-wrap"] .social-btn,
        html[data-theme="dark-blue"] [class*="-wrap"] .co-item,
        html[data-theme="dark-blue"] [class*="-wrap"] .form-section,
        html[data-theme="dark-blue"] [class*="-wrap"] .side-card,
        html[data-theme="dark-blue"] [class*="-wrap"] .declare-box,
        html[data-theme="dark-blue"] [class*="-wrap"] .preview-box,
        html[data-theme="dark-blue"] [class*="-wrap"] .date-chip,
        html[data-theme="dark-blue"] [class*="-wrap"] .time-chip,
        html[data-theme="dark-blue"] [class*="-wrap"] .days-chip,
        html[data-theme="dark-blue"] [class*="-wrap"] .year-chip,
        html[data-theme="dark-blue"] [class*="-wrap"] .filter-bar .form-select {
            background: var(--card) !important;
            background-color: var(--card) !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }

        html[data-theme="dark"] [class*="-wrap"] .ql-item:hover,
        html[data-theme="dark"] [class*="-wrap"] .btn-ghost:hover,
        html[data-theme="dark"] [class*="-wrap"] [class*="-metric"]:hover,
        html[data-theme="dark-blue"] [class*="-wrap"] .ql-item:hover,
        html[data-theme="dark-blue"] [class*="-wrap"] .btn-ghost:hover,
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-metric"]:hover {
            background: var(--soft) !important;
            border-color: rgba(255,155,68,.45) !important;
            color: var(--ink) !important;
        }

        /* Tables: dark body + readable text */
        html[data-theme="dark"] .table,
        html[data-theme="dark-blue"] .table,
        html[data-theme="dark"] [class*="-wrap"] .table,
        html[data-theme="dark-blue"] [class*="-wrap"] .table {
            --bs-table-bg: var(--card) !important;
            --bs-table-color: var(--ink) !important;
            --bs-table-border-color: var(--line) !important;
            --bs-table-striped-bg: var(--soft) !important;
            --bs-table-striped-color: var(--ink) !important;
            --bs-table-hover-bg: var(--soft) !important;
            --bs-table-hover-color: var(--ink) !important;
            --bs-table-accent-bg: transparent !important;
            color: var(--ink) !important;
            background-color: var(--card) !important;
        }
        html[data-theme="dark"] .table > :not(caption) > * > *,
        html[data-theme="dark-blue"] .table > :not(caption) > * > *,
        html[data-theme="dark"] [class*="-wrap"] .table > :not(caption) > * > *,
        html[data-theme="dark-blue"] [class*="-wrap"] .table > :not(caption) > * > * {
            background-color: var(--card) !important;
            color: var(--ink) !important;
            border-color: var(--line) !important;
            box-shadow: none !important;
        }
        html[data-theme="dark"] .table thead th,
        html[data-theme="dark-blue"] .table thead th,
        html[data-theme="dark"] [class*="-wrap"] .table thead th,
        html[data-theme="dark-blue"] [class*="-wrap"] .table thead th {
            background: var(--table-head) !important;
            background-color: var(--table-head) !important;
            color: var(--muted) !important;
            border-color: var(--line) !important;
        }
        html[data-theme="dark"] .table tbody tr:nth-of-type(odd) > *,
        html[data-theme="dark-blue"] .table tbody tr:nth-of-type(odd) > * {
            background-color: var(--soft) !important;
            color: var(--ink) !important;
        }

        /* Text / labels inside wraps */
        html[data-theme="dark"] [class*="-wrap"] h1,
        html[data-theme="dark"] [class*="-wrap"] h2,
        html[data-theme="dark"] [class*="-wrap"] h3,
        html[data-theme="dark"] [class*="-wrap"] h4,
        html[data-theme="dark"] [class*="-wrap"] h5,
        html[data-theme="dark"] [class*="-wrap"] h6,
        html[data-theme="dark"] [class*="-wrap"] p,
        html[data-theme="dark"] [class*="-wrap"] label,
        html[data-theme="dark"] [class*="-wrap"] .name,
        html[data-theme="dark"] [class*="-wrap"] .cat-name,
        html[data-theme="dark"] [class*="-wrap"] .type-name,
        html[data-theme="dark"] [class*="-wrap"] .tt,
        html[data-theme="dark"] [class*="-wrap"] .hn,
        html[data-theme="dark"] [class*="-wrap"] .v,
        html[data-theme="dark"] [class*="-wrap"] .amt,
        html[data-theme="dark"] [class*="-wrap"] .strip-name,
        html[data-theme="dark"] [class*="-wrap"] .emp-name,
        html[data-theme="dark"] [class*="-wrap"] .hero-name,
        html[data-theme="dark-blue"] [class*="-wrap"] h1,
        html[data-theme="dark-blue"] [class*="-wrap"] h2,
        html[data-theme="dark-blue"] [class*="-wrap"] h3,
        html[data-theme="dark-blue"] [class*="-wrap"] h4,
        html[data-theme="dark-blue"] [class*="-wrap"] h5,
        html[data-theme="dark-blue"] [class*="-wrap"] h6,
        html[data-theme="dark-blue"] [class*="-wrap"] p,
        html[data-theme="dark-blue"] [class*="-wrap"] label,
        html[data-theme="dark-blue"] [class*="-wrap"] .name,
        html[data-theme="dark-blue"] [class*="-wrap"] .cat-name,
        html[data-theme="dark-blue"] [class*="-wrap"] .type-name,
        html[data-theme="dark-blue"] [class*="-wrap"] .tt,
        html[data-theme="dark-blue"] [class*="-wrap"] .hn,
        html[data-theme="dark-blue"] [class*="-wrap"] .v,
        html[data-theme="dark-blue"] [class*="-wrap"] .amt,
        html[data-theme="dark-blue"] [class*="-wrap"] .strip-name,
        html[data-theme="dark-blue"] [class*="-wrap"] .emp-name,
        html[data-theme="dark-blue"] [class*="-wrap"] .hero-name {
            color: var(--ink) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] .sub,
        html[data-theme="dark"] [class*="-wrap"] .hint,
        html[data-theme="dark"] [class*="-wrap"] .empty,
        html[data-theme="dark"] [class*="-wrap"] .desc,
        html[data-theme="dark"] [class*="-wrap"] .k,
        html[data-theme="dark"] [class*="-wrap"] .ts,
        html[data-theme="dark"] [class*="-wrap"] .att-k,
        html[data-theme="dark"] [class*="-wrap"] .att-h,
        html[data-theme="dark"] [class*="-wrap"] .form-label,
        html[data-theme="dark"] [class*="-wrap"] .text-muted,
        html[data-theme="dark"] [class*="-wrap"] .upload-hint,
        html[data-theme="dark-blue"] [class*="-wrap"] .sub,
        html[data-theme="dark-blue"] [class*="-wrap"] .hint,
        html[data-theme="dark-blue"] [class*="-wrap"] .empty,
        html[data-theme="dark-blue"] [class*="-wrap"] .desc,
        html[data-theme="dark-blue"] [class*="-wrap"] .k,
        html[data-theme="dark-blue"] [class*="-wrap"] .ts,
        html[data-theme="dark-blue"] [class*="-wrap"] .att-k,
        html[data-theme="dark-blue"] [class*="-wrap"] .att-h,
        html[data-theme="dark-blue"] [class*="-wrap"] .form-label,
        html[data-theme="dark-blue"] [class*="-wrap"] .text-muted,
        html[data-theme="dark-blue"] [class*="-wrap"] .upload-hint {
            color: var(--muted) !important;
        }

        /* Heroes use theme surfaces (no forced white text on light cards) */
        html[data-theme="dark"] [class*="-wrap"] .att-hero,
        html[data-theme="dark"] [class*="-wrap"] .lv-hero,
        html[data-theme="dark"] [class*="-wrap"] .ex-hero,
        html[data-theme="dark"] [class*="-wrap"] .pf-hero,
        html[data-theme="dark"] [class*="-wrap"] .hs-hero,
        html[data-theme="dark"] [class*="-wrap"] .co-hero,
        html[data-theme="dark"] [class*="-wrap"] .br-hero,
        html[data-theme="dark"] [class*="-wrap"] .db-hero,
        html[data-theme="dark"] [class*="-wrap"] .welcome-bar,
        html[data-theme="dark-blue"] [class*="-wrap"] .att-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .lv-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .ex-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .pf-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .hs-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .co-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .br-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .db-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .welcome-bar {
            background: var(--card) !important;
            background-image: none !important;
            color: var(--ink) !important;
            border: 1px solid var(--line) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] .att-hero h2,
        html[data-theme="dark"] [class*="-wrap"] .db-hero h2,
        html[data-theme="dark"] [class*="-wrap"] .welcome-bar h2,
        html[data-theme="dark-blue"] [class*="-wrap"] .att-hero h2,
        html[data-theme="dark-blue"] [class*="-wrap"] .db-hero h2,
        html[data-theme="dark-blue"] [class*="-wrap"] .welcome-bar h2 {
            color: var(--ink) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] .att-hero p,
        html[data-theme="dark"] [class*="-wrap"] .welcome-bar p,
        html[data-theme="dark"] [class*="-wrap"] .welcome-bar .meta,
        html[data-theme="dark-blue"] [class*="-wrap"] .att-hero p,
        html[data-theme="dark-blue"] [class*="-wrap"] .welcome-bar p,
        html[data-theme="dark-blue"] [class*="-wrap"] .welcome-bar .meta {
            color: var(--muted) !important;
        }

        /* Light-style heroes (POSH etc.) keep theme ink */
        html[data-theme="dark"] [class*="-wrap"] .posh-hero,
        html[data-theme="dark"] [class*="-wrap"] .posh-hero h2,
        html[data-theme="dark"] [class*="-wrap"] .posh-hero p,
        html[data-theme="dark-blue"] [class*="-wrap"] .posh-hero,
        html[data-theme="dark-blue"] [class*="-wrap"] .posh-hero h2,
        html[data-theme="dark-blue"] [class*="-wrap"] .posh-hero p {
            color: var(--ink) !important;
            background: var(--card) !important;
        }
        html[data-theme="dark"] [class*="-wrap"] .posh-hero p,
        html[data-theme="dark-blue"] [class*="-wrap"] .posh-hero p {
            color: var(--muted) !important;
        }

        /* Status pills stay colorful (don't force ink) */
        html[data-theme="dark"] [class*="-wrap"] .pill,
        html[data-theme="dark"] [class*="-wrap"] [class*="-pill"],
        html[data-theme="dark"] [class*="-wrap"] .days-pill,
        html[data-theme="dark-blue"] [class*="-wrap"] .pill,
        html[data-theme="dark-blue"] [class*="-wrap"] [class*="-pill"],
        html[data-theme="dark-blue"] [class*="-wrap"] .days-pill {
            /* keep original pill colors */
        }

        /* Topbar — light theme stays white; dark themes keep dark surfaces */
        html[data-theme="dark"] .header .tb-chip,
        html[data-theme="dark"] .header .tb-icon-btn,
        html[data-theme="dark"] .header .tb-user,
        html[data-theme="dark"] .header .tb-toggle,
        html[data-theme="dark-blue"] .header .tb-chip,
        html[data-theme="dark-blue"] .header .tb-icon-btn,
        html[data-theme="dark-blue"] .header .tb-user,
        html[data-theme="dark-blue"] .header .tb-toggle {
            background: var(--card) !important;
            border-color: var(--line) !important;
            color: var(--ink) !important;
        }
        html[data-theme="dark"] .header .page-title-box h3,
        html[data-theme="dark-blue"] .header .page-title-box h3 {
            color: var(--ink) !important;
        }
        html[data-theme="light"] .header .page-title-box h3 {
            color: #172033 !important;
        }
        html[data-theme="dark"] .header .page-title-box .tb-sub,
        html[data-theme="dark-blue"] .header .page-title-box .tb-sub {
            color: var(--muted) !important;
        }

        /* Bootstrap / content outside wraps */
        html[data-theme="dark"] .content,
        html[data-theme="dark-blue"] .content {
            color: var(--text);
        }
        html[data-theme="dark"] .content .text-muted,
        html[data-theme="dark-blue"] .content .text-muted {
            color: var(--muted) !important;
        }

        /* Clean enterprise sidebar — colors from theme tokens */
        .sidebar {
            --sb-w: 260px;
            --sb-mini: 72px;
            width: var(--sb-w); flex-shrink: 0;
            background: var(--sidebar) !important;
            color: var(--sidebar-text); position: sticky; top: 0; height: 100vh; z-index: 1040;
            display: flex; flex-direction: column;
            border-right: 1px solid var(--border);
            box-shadow: none;
            transition: width .22s ease;
            overflow: visible;
        }
        .sidebar-inner {
            display: flex; flex-direction: column; height: 100%; min-height: 0; width: 100%;
        }
        .sidebar .brand {
            padding: .95rem 1.1rem; display: flex; align-items: center; gap: .7rem;
            border-bottom: 1px solid var(--border);
            background: var(--sidebar);
            flex-shrink: 0; min-height: 64px;
        }
        .sidebar .brand-mark {
            width: 36px; height: 36px; border-radius: 8px; overflow: hidden;
            background: var(--soft); display: inline-flex; align-items: center; justify-content: center;
            border: 1px solid var(--border); flex-shrink: 0;
        }
        .sidebar .brand-mark img { width: 100%; height: 100%; object-fit: contain; padding: 3px; }
        .sidebar .brand-text { min-width: 0; overflow: hidden; white-space: nowrap; transition: opacity .15s ease; }
        .sidebar .brand-text .brand-name {
            display: block; font-weight: 700; font-size: 1rem; letter-spacing: -.01em; line-height: 1.15; color: var(--ink);
        }
        .sidebar .brand-text .brand-sub {
            display: block; font-size: .65rem; color: var(--muted); font-weight: 600;
            text-transform: uppercase; letter-spacing: .06em; margin-top: .15rem;
        }
        .sidebar-menu {
            flex: 1; overflow-y: auto; overflow-x: hidden; padding: .55rem .65rem .85rem;
            scrollbar-width: thin; scrollbar-color: var(--border) transparent;
        }
        .sidebar-menu::-webkit-scrollbar { width: 5px; }
        .sidebar-menu::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        .sidebar-menu .nav-section {
            padding: .7rem .65rem .25rem; font-size: .65rem; letter-spacing: .06em;
            text-transform: uppercase; color: var(--muted); font-weight: 700;
            white-space: nowrap; overflow: hidden;
        }
        .sidebar-vertical > li > a,
        .sidebar-menu .submenu > ul > li > a {
            color: var(--sidebar-text) !important; text-decoration: none;
            display: flex; gap: .65rem; align-items: center;
            padding: .55rem .7rem; border-radius: 8px; margin: .05rem .1rem;
            font-size: .9rem; font-weight: 500; transition: background .12s ease, color .12s ease;
            border: 0; position: relative; white-space: nowrap;
        }
        .sidebar-vertical > li > a > span:not(.menu-arrow):not(.sidebar-badge),
        .sidebar-menu .submenu > ul > li > a > span {
            overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-vertical > li > a > i:first-child,
        .sidebar-menu .submenu > a > i:first-child {
            width: 1.1rem; text-align: center; color: var(--icon); font-size: .9rem; flex-shrink: 0;
        }
        .sidebar-vertical > li > a:hover,
        .sidebar-menu .submenu > ul > li > a:hover {
            background: var(--soft); color: var(--ink) !important;
        }
        .sidebar-vertical > li > a:hover > i:first-child,
        .sidebar-menu .submenu.open > a > i:first-child {
            color: var(--primary);
        }
        .sidebar-vertical > li > a.active,
        .sidebar-menu .submenu > ul > li > a.active {
            background: var(--sidebar-active);
            color: var(--ink) !important; border-color: transparent;
            box-shadow: none;
            font-weight: 600;
        }
        .sidebar-vertical > li > a.active::before {
            content: ""; position: absolute; left: 0; top: 8px; bottom: 8px; width: 3px;
            background: var(--primary); border-radius: 0 3px 3px 0;
        }
        .sidebar-vertical > li > a.active > i:first-child { color: var(--primary); }
        .sidebar-menu .submenu > ul {
            display: none; list-style: none; padding: .1rem 0 .25rem .15rem; margin: 0;
            border-left: 2px solid var(--border); margin-left: 1.05rem;
        }
        .sidebar-menu .submenu.open > ul { display: block; }
        .sidebar-menu .submenu > a .menu-arrow {
            margin-left: auto; transition: transform .2s ease; opacity: .55; font-size: .7rem; flex-shrink: 0; color: var(--muted);
        }
        .sidebar-menu .submenu.open > a {
            background: var(--soft); color: var(--ink) !important;
        }
        .sidebar-menu .submenu.open > a .menu-arrow { transform: rotate(90deg); opacity: .9; color: var(--primary); }
        .sidebar-menu .submenu > ul > li > a {
            padding: .42rem .65rem .42rem .75rem; font-size: .84rem; font-weight: 500; margin: .04rem .1rem;
        }
        .sidebar-menu .submenu > ul > li > a.active {
            background: color-mix(in srgb, var(--primary) 16%, transparent); color: var(--primary) !important;
            box-shadow: none; border: 0; font-weight: 600;
        }
        .sidebar-menu .submenu > ul > li > a.active::before { display: none; }
        .sidebar-badge {
            margin-left: auto; min-width: 1.15rem; height: 1.15rem; padding: 0 .35rem;
            border-radius: 6px; background: #12B76A; color: #fff;
            font-size: .65rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center;
            animation: none; flex-shrink: 0;
        }
        .sidebar-foot {
            flex-shrink: 0; padding: .75rem .85rem .9rem;
            border-top: 1px solid var(--border);
            background: var(--sidebar);
        }
        .sidebar-user {
            display: flex; align-items: center; gap: .6rem; text-decoration: none; color: inherit;
            padding: .45rem .5rem; border-radius: 8px; border: 1px solid var(--border);
            background: var(--soft); transition: background .12s ease;
        }
        .sidebar-user:hover { background: var(--sidebar-active); color: var(--ink); }
        .sidebar-user img, .sidebar-user .sb-avatar {
            width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
            border: 1px solid var(--border); background: var(--card);
        }
        .sidebar-user .sb-avatar {
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .72rem; font-weight: 700; color: var(--primary); background: color-mix(in srgb, var(--primary) 16%, transparent);
        }
        .sidebar-user .sb-meta { min-width: 0; overflow: hidden; }
        .sidebar-user .sb-name {
            display: block; font-size: .84rem; font-weight: 650; color: var(--ink);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .sidebar-user .sb-role {
            display: block; font-size: .66rem; color: var(--muted); text-transform: capitalize;
        }

        /* Collapsed / mini sidebar (desktop) */
        .app-shell.sidebar-mini .sidebar {
            width: var(--sb-mini);
            overflow: visible;
        }
        .app-shell.sidebar-mini .sidebar .brand {
            justify-content: center; padding: .85rem .5rem;
        }
        .app-shell.sidebar-mini .sidebar .brand-text,
        .app-shell.sidebar-mini .sidebar-menu .nav-section,
        .app-shell.sidebar-mini .sidebar-menu .menu-arrow,
        .app-shell.sidebar-mini .sidebar-user .sb-meta,
        .app-shell.sidebar-mini .sidebar-vertical > li > a > span:not(.sidebar-badge):not(.menu-arrow),
        .app-shell.sidebar-mini .sidebar-menu .submenu > ul {
            display: none !important;
        }
        .app-shell.sidebar-mini .sidebar-menu {
            padding: .5rem .35rem .75rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .app-shell.sidebar-mini .sidebar-vertical > li {
            position: relative;
        }
        .app-shell.sidebar-mini .sidebar-vertical > li > a,
        .app-shell.sidebar-mini .sidebar-menu .submenu > a {
            justify-content: center; padding: .55rem .4rem; margin: .1rem .15rem; gap: 0;
        }
        .app-shell.sidebar-mini .sidebar-vertical > li > a > i:first-child,
        .app-shell.sidebar-mini .sidebar-menu .submenu > a > i:first-child {
            width: auto; font-size: 1rem; margin: 0;
        }
        .app-shell.sidebar-mini .sidebar-badge {
            position: absolute; top: 2px; right: 2px; margin: 0;
            min-width: 1rem; height: 1rem; font-size: .58rem; padding: 0 .25rem;
            animation: none;
        }
        .app-shell.sidebar-mini .sidebar-foot { padding: .55rem .35rem .7rem; }
        .app-shell.sidebar-mini .sidebar-user { justify-content: center; padding: .35rem; }

        .app-shell.sidebar-mini .sidebar-vertical > li > a:hover,
        .app-shell.sidebar-mini .sidebar-menu .submenu > a:hover {
            background: var(--soft);
        }
        .app-shell.sidebar-mini .sidebar-vertical > li > a:hover > i:first-child,
        .app-shell.sidebar-mini .sidebar-menu .submenu > a:hover > i:first-child {
            color: var(--primary);
        }

        #sidebar-hover-tip {
            position: fixed; z-index: 2000; display: none;
            background: #172033; color: #fff;
            border: 1px solid #172033;
            padding: .4rem .7rem; border-radius: 8px;
            font-size: .8rem; font-weight: 600; white-space: nowrap;
            box-shadow: 0 4px 12px rgba(16,24,40,.15);
            pointer-events: none;
        }
        #sidebar-hover-tip.show { display: block; }
        #sidebar-hover-tip::before {
            content: ""; position: absolute; left: -5px; top: 50%; transform: translateY(-50%) rotate(45deg);
            width: 8px; height: 8px; background: #172033; border-left: 1px solid #172033; border-bottom: 1px solid #172033;
        }

        .main { flex: 1; min-width: 0; display: flex; flex-direction: column; transition: margin .22s ease; }
        .page-wrapper { flex: 1; }
        .page-wrapper .content { padding: 1.25rem 1.5rem 1.75rem; }

        /* Page header — theme-aware */
        .page-header { margin-bottom: 1rem; }
        .page-header .page-title {
            font-size: 1.75rem; font-weight: 700; color: var(--ink); margin: 0 0 .2rem;
            letter-spacing: -.02em;
        }
        .breadcrumb { margin: 0; padding: 0; background: transparent; font-size: .84rem; }
        .breadcrumb .breadcrumb-item + .breadcrumb-item::before { color: var(--muted); }
        .breadcrumb a { color: var(--muted); text-decoration: none; }
        .breadcrumb .breadcrumb-item.active { color: var(--sidebar-text); }

        /* Primary action button */
        .btn.add-btn, .btn-add {
            background: var(--primary); border-color: var(--primary); color: var(--primary-text, #fff);
            border-radius: 8px; padding: .45rem .95rem; font-weight: 600; font-size: .875rem;
        }
        .btn.add-btn:hover, .btn-add:hover { background: var(--primary-hover); border-color: var(--primary-hover); color: var(--primary-text, #fff); }
        .btn-success { background: #12B76A; border-color: #12B76A; }
        .btn-primary { background: var(--primary); border-color: var(--primary); color: var(--primary-text, #fff); }

        /* Stats info cards (legacy) */
        .stats-info {
            background: var(--card); border: 1px solid var(--border); border-radius: .5rem;
            padding: 1rem 1.15rem; margin-bottom: 1rem; text-align: center;
            box-shadow: 0 1px 2px var(--shadow);
            transition: box-shadow .15s, transform .15s;
        }
        a.stats-info-link { text-decoration: none; color: inherit; display: block; }
        a.stats-info-link:hover .stats-info { box-shadow: 0 4px 14px rgba(15,39,68,.12); transform: translateY(-1px); }
        .stats-info h6 { color: var(--muted); font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; margin: 0 0 .35rem; font-weight: 600; }
        .stats-info h4, .stats-info .value { font-size: 1.6rem; font-weight: 700; color: var(--ink); margin: 0; }

        .stat-card, .card-soft, .card {
            background: var(--card); border: 1px solid var(--border); border-radius: .5rem;
            box-shadow: 0 1px 2px var(--shadow, rgba(0,0,0,.03));
            color: var(--ink);
        }
        .stat-card { padding: 1.1rem 1.25rem; }
        .stat-card .value { font-size: 1.75rem; font-weight: 700; color: var(--ink); }
        .card-soft { padding: 0; }
        .card-soft > .card-body, .card-soft.p-3, .card-soft.p-4 { padding: 1rem 1.15rem !important; }
        .card-header-soft {
            padding: .85rem 1.15rem; border-bottom: 1px solid var(--border);
            font-weight: 600; background: var(--soft); border-radius: .5rem .5rem 0 0;
            color: var(--ink);
        }

        /* Tables like custom-table */
        .table { background: var(--card); color: var(--ink); }
        .table thead th {
            background: var(--table-head); border-bottom: 1px solid var(--border);
            font-size: .8rem; text-transform: uppercase; letter-spacing: .02em; color: var(--muted); font-weight: 600;
            white-space: nowrap;
        }
        .table.custom-table td, .table.table-striped td { vertical-align: middle; }
        .table-responsive {
            background: var(--card); border: 1px solid var(--border); border-radius: .5rem; padding: .25rem;
            color: var(--ink);
        }

        .filter-row {
            background: var(--card); border: 1px solid var(--border); border-radius: .5rem;
            padding: 1rem; margin-bottom: 1rem; color: var(--ink);
        }

        .badge-status-new { background: #ffc107; color: #000; }
        .badge-status-pending { background: #ff9b44; color: #fff; }
        .badge-status-approved, .badge-status-success { background: #55ce63; color: #fff; }
        .badge-status-declined, .badge-status-danger { background: #f62d51; color: #fff; }

        .avatar-sm {
            width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: #eee;
        }
        .table-avatar { display: flex; align-items: center; gap: .65rem; margin: 0; font-size: .95rem; }
        .action-links a { margin-right: .35rem; }

        .sidebar-backdrop {
            display: none; position: fixed; inset: 0; background: rgba(15,39,68,.45); z-index: 1035;
            backdrop-filter: blur(2px);
        }
        @media (max-width: 991px) {
            .sidebar { position: fixed; transform: translateX(-100%); transition: transform .22s ease, width .22s ease; width: var(--sb-w) !important; }
            .sidebar.open { transform: translateX(0); }
            .app-shell.sidebar-mini .sidebar { width: var(--sb-w) !important; }
            .app-shell.sidebar-mini .sidebar .brand-text,
            .app-shell.sidebar-mini .sidebar-menu .nav-section,
            .app-shell.sidebar-mini .sidebar-menu .menu-arrow,
            .app-shell.sidebar-mini .sidebar-user .sb-meta,
            .app-shell.sidebar-mini .sidebar-vertical > li > a > span:not(.sidebar-badge):not(.menu-arrow) {
                display: initial !important;
            }
            .app-shell.sidebar-mini .sidebar-menu .submenu.open > ul { display: block !important; position: static; min-width: 0; box-shadow: none; border: 0; border-left: 2px solid rgba(255,255,255,.2); margin-left: 1rem; background: transparent; padding: .1rem 0 .25rem .2rem; }
            #sidebar-hover-tip { display: none !important; }
            .sidebar-backdrop.show { display: block; }
            .page-wrapper .content { padding: 1rem; }
        }

        /* Theme-aware card headers */
        .hrm-card > .card-header,
        .db-wrap .hrm-card > .card-header {
            background: var(--table-head) !important;
            color: var(--ink) !important;
            border-color: var(--border) !important;
        }
        .hrm-card.hrm-card-danger > .card-header {
            background: rgba(240, 68, 56, 0.12) !important;
            border-color: rgba(240, 68, 56, 0.25) !important;
            color: #F04438 !important;
        }
    </style>
    @stack('styles')
    <link href="{{ asset('css/hrm-corporate.css') }}?v=20260814o" rel="stylesheet">
</head>
<body>
@php($user = auth()->user())
<div class="app-shell">
    @include('layouts.sidebar')
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="main">
        @include('layouts.topbar')
        <div class="page-wrapper">
            <div class="content container-fluid">
                @hasSection('page_header')
                    @yield('page_header')
                @else
                    @include('partials.page-header', [
                        'title' => trim($__env->yieldContent('heading')) ?: 'Leadforgrow',
                        'breadcrumbs' => [
                            ['label' => 'Dashboard', 'url' => auth()->user()?->isAdmin() ? route('dashboard.admin') : route('dashboard.employee')],
                            ['label' => trim($__env->yieldContent('heading')) ?: 'Page'],
                        ],
                        'actions' => trim($__env->yieldContent('page_actions')) ?: null,
                    ])
                @endif

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<script src="{{ asset('js/hrm-rich-editor.js') }}?v=20260815d"></script>
<script src="{{ asset('js/hrm-employee-select.js') }}?v=20260815a"></script>
<script>
(function () {
    const shell = document.querySelector('.app-shell');
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const KEY = 'hrm_sidebar_mini';

    // Stable floating tip (avoids CSS overflow vibrate)
    let tip = document.getElementById('sidebar-hover-tip');
    if (!tip) {
        tip = document.createElement('div');
        tip.id = 'sidebar-hover-tip';
        document.body.appendChild(tip);
    }

    function isMobile() {
        return window.matchMedia('(max-width: 991px)').matches;
    }

    function hideTip() {
        tip.classList.remove('show');
        tip.textContent = '';
    }

    function showTip(anchor, text) {
        if (!text || !shell?.classList.contains('sidebar-mini') || isMobile()) {
            hideTip();
            return;
        }
        tip.textContent = text;
        tip.classList.add('show');
        const rect = anchor.getBoundingClientRect();
        const tipRect = tip.getBoundingClientRect();
        let top = rect.top + (rect.height - tipRect.height) / 2;
        let left = rect.right + 12;
        if (top < 8) top = 8;
        if (top + tipRect.height > window.innerHeight - 8) {
            top = window.innerHeight - tipRect.height - 8;
        }
        tip.style.top = top + 'px';
        tip.style.left = left + 'px';
    }

    function applyMini(on) {
        if (!shell) return;
        const enable = !!on && !isMobile();
        shell.classList.toggle('sidebar-mini', enable);
        hideTip();
        try { localStorage.setItem(KEY, on ? '1' : '0'); } catch (e) {}
        document.getElementById('toggle_btn')?.setAttribute('aria-expanded', enable ? 'false' : 'true');
    }

    try {
        if (!isMobile() && localStorage.getItem(KEY) === '1') {
            shell?.classList.add('sidebar-mini');
        }
    } catch (e) {}

    document.querySelectorAll('.sidebar-vertical > li > a, .sidebar-menu .submenu > a').forEach(function (a) {
        if (!a.getAttribute('data-title')) {
            const label = a.querySelector('span:not(.menu-arrow):not(.sidebar-badge)');
            a.setAttribute('data-title', (label?.textContent || a.getAttribute('title') || '').trim());
        }

        a.addEventListener('mouseenter', function () {
            showTip(a, a.getAttribute('data-title') || '');
        });
        a.addEventListener('mouseleave', hideTip);
        a.addEventListener('focus', function () {
            showTip(a, a.getAttribute('data-title') || '');
        });
        a.addEventListener('blur', hideTip);
    });

    document.querySelector('.sidebar-menu')?.addEventListener('scroll', hideTip);

    document.getElementById('toggle_btn')?.addEventListener('click', function () {
        if (isMobile()) {
            sidebar?.classList.toggle('open');
            backdrop?.classList.toggle('show');
            hideTip();
            return;
        }
        applyMini(!shell?.classList.contains('sidebar-mini'));
    });

    backdrop?.addEventListener('click', function () {
        sidebar?.classList.remove('open');
        this.classList.remove('show');
    });

    window.addEventListener('resize', function () {
        hideTip();
        if (isMobile()) {
            shell?.classList.remove('sidebar-mini');
        } else {
            sidebar?.classList.remove('open');
            backdrop?.classList.remove('show');
            try {
                if (localStorage.getItem(KEY) === '1') shell?.classList.add('sidebar-mini');
            } catch (e) {}
        }
    });

    document.querySelectorAll('.sidebar-menu .submenu > a').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            // Mini mode: expand full sidebar so submenu can open cleanly (no vibrate)
            if (shell?.classList.contains('sidebar-mini') && !isMobile()) {
                applyMini(false);
                this.parentElement.classList.add('open');
                return;
            }
            this.parentElement.classList.toggle('open');
        });
    });

    // Keep sidebar scroll where the user left it after page navigation
    (function persistSidebarScroll() {
        const menu = document.querySelector('.sidebar-menu');
        if (!menu) return;
        const SCROLL_KEY = 'hrm_sidebar_scroll';

        function saveScroll() {
            try {
                sessionStorage.setItem(SCROLL_KEY, String(menu.scrollTop));
            } catch (e) {}
        }

        function restoreScroll() {
            try {
                const y = sessionStorage.getItem(SCROLL_KEY);
                if (y === null) return;
                menu.scrollTop = parseInt(y, 10) || 0;
            } catch (e) {}
        }

        restoreScroll();
        requestAnimationFrame(restoreScroll);
        setTimeout(restoreScroll, 50);
        setTimeout(restoreScroll, 150);

        let scrollTimer;
        menu.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(saveScroll, 40);
        }, { passive: true });

        menu.querySelectorAll('a[href]').forEach(function (a) {
            a.addEventListener('click', saveScroll);
        });

        window.addEventListener('beforeunload', saveScroll);
    })();
})();
</script>
@auth
<script src="{{ asset('js/hrm-notify-sounds.js') }}?v=20260814a"></script>
<script>
window.HrmNotifyPrefs = {
    chatSound: @json((bool) (auth()->user()?->notify_chat_sound ?? true)),
    callRingtone: @json((bool) (auth()->user()?->notify_call_ringtone ?? true)),
    appSound: @json((bool) (auth()->user()?->notify_app_sound ?? true)),
};
window.HrmAppNotifyConfig = {
    pollUrl: @json(route('notifications.poll')),
    readAllUrl: @json(route('notifications.read-all')),
    readUrlTpl: @json(url('/my-notifications/__ID__/read')),
    latestId: @json((int) ($layoutNotifyLatestId ?? 0)),
};
</script>
<script src="{{ asset('js/hrm-app-notifications.js') }}?v=20260815a"></script>
<script>
(function () {
    const url = @json(route('chat.heartbeat'));
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    function ping() {
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: '{}',
            credentials: 'same-origin',
            keepalive: true,
        }).catch(function () {});
    }
    ping();
    setInterval(ping, 30000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') ping();
    });
})();
</script>
@endauth
@stack('scripts')
</body>
</html>
