@extends('layout', ['activeTab' => 'filters'])

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">{{ isset($filter) ? 'Gmail フィルター編集' : '新規 Gmail フィルター作成' }}</h5>
    </div>
    <div class="card-body">
        <form action="{{ $basePath }}/filters/{{ isset($filter) ? 'update' : 'store' }}" method="POST">
            <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">
            @if(isset($filter))
                <input type="hidden" name="id" value="{{ $filter['id'] }}">
            @endif

            <h6 class="text-primary border-bottom pb-2 mb-3">1. 検索条件 (Criteria)</h6>

            <div class="mb-3">
                <label for="from" class="form-label">From (送信元)</label>
                <input type="text" class="form-control" id="from" name="from" value="{{ $filter['criteria']['from'] ?? '' }}" placeholder="例: news@example.com">
            </div>

            <div class="mb-3">
                <label for="to" class="form-label">To (送信先)</label>
                <input type="text" class="form-control" id="to" name="to" value="{{ $filter['criteria']['to'] ?? '' }}" placeholder="例: me@example.com">
            </div>

            <div class="mb-3">
                <label for="subject" class="form-label">件名 (Subject)</label>
                <input type="text" class="form-control" id="subject" name="subject" value="{{ $filter['criteria']['subject'] ?? '' }}" placeholder="例: [お知らせ]">
            </div>

            <div class="mb-3">
                <label for="query" class="form-label">含むキーワード (Has the words)</label>
                <input type="text" class="form-control" id="query" name="query" value="{{ $filter['criteria']['query'] ?? '' }}" placeholder="例: unsubscribe">
            </div>

            <div class="mb-3">
                <label for="negatedQuery" class="form-label">除外キーワード (Doesn't have)</label>
                <input type="text" class="form-control" id="negatedQuery" name="negatedQuery" value="{{ $filter['criteria']['negatedQuery'] ?? '' }}" placeholder="例: 重要">
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="hasAttachment" name="hasAttachment" value="1" {{ !empty($filter['criteria']['hasAttachment']) ? 'checked' : '' }}>
                <label class="form-check-label" for="hasAttachment">添付ファイルあり</label>
            </div>

            <h6 class="text-primary border-bottom pb-2 mb-3 mt-4">2. 実行する操作 (Action)</h6>

            <div class="mb-3">
                <label for="addLabel" class="form-label">追加するラベル (Label Name or ID)</label>
                <input type="text" class="form-control" id="addLabel" name="addLabel" value="{{ !empty($filter['action']['addLabelIds']) ? implode(', ', $filter['action']['addLabelIds']) : '' }}" placeholder="例: TRASH, UNREAD, または作成済みラベルID">
                <div class="form-text">ゴミ箱へ移動する場合は <code>TRASH</code> を指定します。既読にする場合はラベルの削除で <code>UNREAD</code> を指定します。</div>
            </div>

            <div class="mb-3">
                <label for="removeLabel" class="form-label">削除するラベル (Label Name or ID)</label>
                <input type="text" class="form-control" id="removeLabel" name="removeLabel" value="{{ !empty($filter['action']['removeLabelIds']) ? implode(', ', $filter['action']['removeLabelIds']) : '' }}" placeholder="例: UNREAD">
                <div class="form-text">既読化する場合は <code>UNREAD</code> を指定します。</div>
            </div>

            <div class="mb-3">
                <label for="forward" class="form-label">転送先アドレス</label>
                <input type="email" class="form-control" id="forward" name="forward" value="{{ $filter['action']['forward'] ?? '' }}" placeholder="例: other@example.com">
                <div class="form-text">※Gmail設定で事前に確認済みの転送先アドレスである必要があります。</div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ $basePath }}/filters" class="btn btn-secondary">キャンセル</a>
                <button type="submit" class="btn btn-primary">{{ isset($filter) ? '更新する' : '作成する' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
