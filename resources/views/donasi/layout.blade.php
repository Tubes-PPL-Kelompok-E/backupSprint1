<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - BloodConnect</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, sans-serif; background: #f6f6f8; color: #222; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 16px; }
        h1 { font-size: 1.4rem; margin: 8px 0 16px; }
        .card { background: #fff; border-radius: 12px; padding: 8px 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .alert { border-radius: 10px; padding: 10px 14px; margin-bottom: 14px; }
        .alert.ok { background: #e7f6ec; border: 1px solid #a6d9b8; }
        .alert.err { background: #fde6e6; border: 1px solid #f0b4b4; }
        .alert ul { margin: 0; padding-left: 18px; }
        .bar { display: flex; gap: 10px; flex-wrap: wrap; justify-content: space-between; margin-bottom: 14px; }
        .search { display: flex; gap: 6px; flex: 1; min-width: 220px; }
        input, select, textarea { width: 100%; padding: 9px 10px; border: 1px solid #ccc; border-radius: 8px; font: inherit; }
        label { display: block; margin-bottom: 12px; font-weight: 600; }
        label input, label select, label textarea { margin-top: 4px; font-weight: 400; }
        button, .btn { background: #c62828; color: #fff; border: 0; border-radius: 8px; padding: 9px 14px; font: inherit; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn.light, button.light { background: #eee; color: #222; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        th { font-size: .85rem; color: #666; }
        .empty { padding: 24px; text-align: center; color: #777; }
        .badge { padding: 2px 8px; border-radius: 999px; font-size: .8rem; background: #e7f6ec; }
        .badge.dibatalkan { background: #fde6e6; }
        .status { border-radius: 12px; padding: 16px 18px; margin-bottom: 20px; }
        .status.ok { background: #e7f6ec; border: 1px solid #a6d9b8; }
        .status.wait { background: #fdf1e3; border: 1px solid #f0c48e; }
        .status strong { display: block; font-size: 1.1rem; margin-bottom: 4px; }
        .inline { display: flex; gap: 6px; margin-top: 6px; }
        @media (max-width: 700px) {
            table, thead, tbody, tr, th, td { display: block; }
            thead { display: none; }
            tr { padding: 10px 0; border-bottom: 1px solid #eee; }
            td { border: 0; padding: 3px 0; }
            td::before { content: attr(data-label) ": "; font-weight: 600; color: #666; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>@yield('title')</h1>

    @if (session('success'))
        <div class="alert ok">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @yield('content')
</div>
</body>
</html>