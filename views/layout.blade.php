<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail Cleanup 設定</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 900px; margin-top: 50px; }
        .card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    </style>
</head>
<body>
    <div class="container">
        <header class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0">Gmail Cleanup / フィルター管理</h1>
                @if(isset($basePath))
                    <span class="badge bg-secondary">Env: {{ $basePath }}</span>
                @endif
            </div>
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? 'cleanup') === 'cleanup' ? 'active' : '' }}" href="{{ $basePath }}/">クリーンアップルール</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ ($activeTab ?? '') === 'filters' ? 'active' : '' }}" href="{{ $basePath }}/filters">Gmail フィルター設定</a>
                </li>
            </ul>
        </header>

        @if(isset($message))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <main>
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
