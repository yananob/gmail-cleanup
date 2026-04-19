@extends('layout')

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ isset($config) ? 'ルール編集' : '新規ルール作成' }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ isset($config) ? $basePath . '/update' : $basePath . '/store' }}" method="POST">
            <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">
            @if(isset($id))
                <input type="hidden" name="id" value="{{ $id }}">
            @endif

            <div class="mb-3">
                <label for="keyword" class="form-label">キーワード</label>
                <input type="text" class="form-control" id="keyword" name="keyword" value="{{ $config['keyword'] ?? '' }}" placeholder='例: "hogehoge", -"exclude"'>
                <div class="form-text">メール本文に含まれるキーワード。</div>
            </div>

            <div class="mb-3">
                <label for="from" class="form-label">From</label>
                <input type="text" class="form-control" id="from" name="from" value="{{ $config['from'] ?? '' }}" placeholder="例: info@example.com, @example.com">
            </div>

            <div class="mb-3">
                <label for="to" class="form-label">To</label>
                <input type="text" class="form-control" id="to" name="to" value="{{ $config['to'] ?? '' }}" placeholder="例: notify@myapp.com">
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label">件名</label>
                <input type="text" class="form-control" id="subject" name="subject" value="{{ $config['subject'] ?? '' }}" placeholder="例: エラー通知">
            </div>

            <div class="mb-3">
                <label for="label" class="form-label">ラベル</label>
                <input type="text" class="form-control" id="label" name="label" value="{{ $config['label'] ?? '' }}" placeholder="例: mailmag">
            </div>

            <div class="mb-3">
                <label for="date_before" class="form-label">期限 (date_before)</label>
                <input type="text" class="form-control" id="date_before" name="date_before" value="{{ $config['date_before'] ?? '' }}" placeholder="例: P1M (1ヶ月), P3M (3ヶ月), P1Y (1年)">
                <div class="form-text">ISO 8601 期間形式で指定します。</div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ $basePath }}/" class="btn btn-secondary">キャンセル</a>
                <button type="submit" class="btn btn-primary">{{ isset($config) ? '更新する' : '保存する' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
