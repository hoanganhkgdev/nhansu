<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Quản lý nhân sự GHPGVN')</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#073a21">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <style>
        :root { --brand: #007a39; --brand-dark: #073a21; --brand-soft: #e8f5ed; --bg: #f4f8f5; --line: #dbe7df; --muted: #5f6f66; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #1f2a24; background: var(--bg); }
        a { color: var(--brand); }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 0 16px; }
        main { padding: 24px 0 48px; }
        h1.center { text-align: center; }
        h1 { font-size: 1.5rem; margin: 0 0 16px; }
        form.filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        form.filters input, form.filters select { padding: 8px 10px; border: 1px solid var(--line); border-radius: 6px; font: inherit; background: #fff; }
        form.filters input[type=search] { flex: 1 1 240px; }
        button, .btn { padding: 8px 16px; border: 0; border-radius: 6px; background: var(--brand); color: #fff; font: inherit; cursor: pointer; text-decoration: none; display: inline-block; }
        .table-wrap { overflow-x: auto; background: #fff; border: 1px solid var(--line); border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--line); vertical-align: top; }
        th { background: #eef5f0; font-weight: 600; white-space: nowrap; }
        tr:last-child td { border-bottom: 0; }
        td.num { text-align: center; color: var(--muted); width: 56px; }
        .empty { padding: 24px; text-align: center; color: var(--muted); background: #fff; border: 1px solid var(--line); border-radius: 8px; }
        .pager { margin-top: 16px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: center; }
        .pager a, .pager span { padding: 6px 12px; border: 1px solid var(--line); border-radius: 6px; background: #fff; text-decoration: none; }
        .pager .current { background: var(--brand); color: #fff; border-color: var(--brand); }
        .pager .disabled { color: var(--muted); }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 8px; padding: 20px; max-width: 720px; margin: 0 auto; }
        .card img.portrait { display: block; max-width: 100%; max-height: 420px; margin: 0 auto 16px; border-radius: 6px; }
        dl { display: grid; grid-template-columns: 140px 1fr; gap: 8px 16px; margin: 0; }
        dt { font-weight: 600; color: var(--muted); }
        dd { margin: 0; }
        .actions { margin-top: 16px; display: flex; gap: 12px; }
        @media (max-width: 560px) { dl { grid-template-columns: 1fr; gap: 0; } dd { margin-bottom: 10px; } }
        @media print {   .actions, form.filters { display: none; } body { background: #fff; } .card { border: 0; } }
    </style>
    @stack('head')
</head>
<body>
<main>
    <div class="wrap">
        @yield('content')
    </div>
</main>
</body>
</html>
