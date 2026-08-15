<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · Leadforgrow HRM</title>
    <link rel="icon" href="{{ $appIconUrl ?? asset('assets/img/logo2.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #2563EB;
            --blue-hover: #1D4ED8;
            --text: #172033;
            --muted: #667085;
            --border: #E4E7EC;
            --bg: #F7F8FA;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--text);
            background: var(--bg);
        }
        .login-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            width: min(420px, 100%);
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(16,24,40,.05);
            padding: 1.75rem 1.5rem 1.5rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: .7rem;
            margin-bottom: 1.35rem;
        }
        .brand img {
            height: 40px;
            width: auto;
            max-width: 140px;
            object-fit: contain;
        }
        .brand .name {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
            line-height: 1.2;
        }
        .brand .sub {
            font-size: .7rem;
            color: #98A2B3;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0 0 .3rem;
            color: var(--text);
        }
        .lead {
            color: var(--muted);
            font-size: .9rem;
            margin: 0 0 1.25rem;
        }
        .form-label {
            font-size: .84rem;
            font-weight: 600;
            color: #344054;
            margin-bottom: .3rem;
        }
        .form-control {
            border-radius: 8px;
            border-color: var(--border);
            padding: .6rem .8rem;
            font-size: .9rem;
            min-height: 42px;
        }
        .form-control:focus {
            border-color: #93C5FD;
            box-shadow: 0 0 0 .15rem rgba(37,99,235,.15);
        }
        .input-wrap { position: relative; }
        .input-wrap .toggle-eye {
            position: absolute;
            right: .45rem;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
        }
        .input-wrap .form-control { padding-right: 2.5rem; }
        .form-check-input:checked {
            background-color: var(--blue);
            border-color: var(--blue);
        }
        .form-check-label { color: var(--muted); font-size: .86rem; }
        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 8px;
            background: var(--blue);
            color: #fff;
            font-weight: 700;
            font-size: .9rem;
            padding: .65rem 1rem;
            min-height: 44px;
        }
        .btn-login:hover { background: var(--blue-hover); color: #fff; }
        .alert-login {
            border: 1px solid #FECDCA;
            background: #FEF3F2;
            color: #B42318;
            border-radius: 8px;
            padding: .6rem .8rem;
            font-size: .86rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .login-meta {
            margin-top: 1rem;
            text-align: center;
            font-size: .78rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
<div class="login-shell">
    <div class="login-card">
        <div class="brand">
            <img src="{{ $appLogoUrl ?? asset('assets/img/logo2.png') }}" alt="Leadforgrow"
                 onerror="this.style.display='none'">
            <div>
                <div class="name">Leadforgrow</div>
                <div class="sub">HRM Management</div>
            </div>
        </div>

        <h2>Sign in</h2>
        <p class="lead">Use your official work email and password.</p>

        @if($errors->any())
            <div class="alert-login">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" autocomplete="on">
            @csrf
            <div class="mb-3">
                <label class="form-label" for="email">Official email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="form-control" required autofocus placeholder="you@company.com"
                       autocomplete="username">
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" id="password" class="form-control"
                           required placeholder="••••••••" autocomplete="current-password">
                    <button type="button" class="toggle-eye" id="togglePassword" aria-label="Show password">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                <label class="form-check-label" for="remember">Keep me signed in</label>
            </div>

            <button class="btn-login" type="submit">Sign in</button>
        </form>

        <p class="login-meta">Leadforgrow HRM · Secure employee workspace</p>
    </div>
</div>

<script>
document.getElementById('togglePassword')?.addEventListener('click', function () {
    const input = document.getElementById('password');
    const icon = this.querySelector('i');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
</script>
</body>
</html>
