<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login | Ongkir API</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
    <main class="login-page">
        <section class="brand-panel">
            <div>
                <span class="brand-mark">OA</span>
                <p class="eyebrow">Velocity Developer</p>
                <h1>Ongkir API</h1>
                <p class="subtitle">
                    Kelola data kode pos dan referensi pengiriman dari satu dashboard yang rapi.
                </p>
            </div>
        </section>

        <section class="form-panel">
            <div class="login-card">
                <div class="mb-4">
                    <p class="eyebrow mb-2">Masuk akun</p>
                    <h2>Login</h2>
                </div>

                @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
                @endif

                @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    Email atau password tidak sesuai.
                </div>
                @endif

                <form method="POST" action="{{ route('login.attempt') }}" class="d-grid gap-3">
                    @csrf

                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                            autofocus>
                        @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="current-password"
                            required>
                        @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </section>
    </main>

    <style>
        :root {
            --ink: #1c2430;
            --muted: #637083;
            --line: #d9e0ea;
            --surface: #ffffff;
            --panel: #f6f8fb;
            --primary: #0f766e;
            --primary-dark: #115e59;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            font-family: 'Instrument Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--panel);
        }

        .login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(360px, 520px);
        }

        .brand-panel {
            display: flex;
            align-items: center;
            padding: clamp(32px, 7vw, 96px);
            color: #ffffff;
            background:
                linear-gradient(135deg, rgba(15, 118, 110, 0.96), rgba(20, 83, 45, 0.9)),
                url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1600&q=80') center / cover;
        }

        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            margin-bottom: 28px;
            border: 1px solid rgba(255, 255, 255, 0.45);
            border-radius: 8px;
            font-weight: 700;
            letter-spacing: 0;
            background: rgba(255, 255, 255, 0.14);
        }

        .eyebrow {
            margin: 0;
            color: inherit;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
            opacity: 0.78;
        }

        h1,
        h2 {
            margin: 0;
            font-weight: 700;
            letter-spacing: 0;
        }

        h1 {
            max-width: 620px;
            font-size: clamp(2.25rem, 6vw, 4.75rem);
            line-height: 1;
        }

        h2 {
            font-size: 1.8rem;
        }

        .subtitle {
            max-width: 560px;
            margin: 22px 0 0;
            color: rgba(255, 255, 255, 0.84);
            font-size: 1.08rem;
            line-height: 1.65;
        }

        .form-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px;
            background: var(--surface);
        }

        .login-card {
            width: 100%;
            max-width: 390px;
        }

        .form-label {
            color: #344052;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .form-control {
            min-height: 46px;
            border-color: var(--line);
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(15, 118, 110, 0.16);
        }

        .form-check-input:checked {
            border-color: var(--primary);
            background-color: var(--primary);
        }

        .btn-primary {
            min-height: 46px;
            border-color: var(--primary);
            border-radius: 8px;
            font-weight: 700;
            background: var(--primary);
        }

        .btn-primary:hover,
        .btn-primary:focus {
            border-color: var(--primary-dark);
            background: var(--primary-dark);
        }

        @media (max-width: 860px) {
            .login-page {
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 34vh;
                align-items: flex-end;
                padding: 28px;
            }

            .brand-mark {
                margin-bottom: 18px;
            }

            .subtitle {
                margin-top: 14px;
                font-size: 1rem;
            }

            .form-panel {
                align-items: flex-start;
                padding: 28px;
            }
        }
    </style>
</body>

</html>
