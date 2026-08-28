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
                <label class="form-label d-block">追加するラベル</label>
                @php
                    $currentAddLabels = $filter['action']['addLabelIds'] ?? [];
                    $commonAddLabels = [
                        'TRASH' => 'TRASH (ゴミ箱)',
                        'UNREAD' => 'UNREAD (未読)',
                        'INBOX' => 'INBOX (受信トレイ)',
                        'SPAM' => 'SPAM (迷惑メール)',
                        'STARRED' => 'STARRED (スター付き)',
                        'IMPORTANT' => 'IMPORTANT (重要)',
                    ];
                    $extraAddLabels = array_diff($currentAddLabels, array_keys($commonAddLabels));
                @endphp
                @foreach($commonAddLabels as $labelKey => $labelName)
                    <div class="form-check form-check-inline mb-2">
                        <input class="form-check-input" type="checkbox" name="addLabel[]" id="addLabel_{{ $labelKey }}" value="{{ $labelKey }}" {{ in_array($labelKey, $currentAddLabels) ? 'checked' : '' }}>
                        <label class="form-check-label" for="addLabel_{{ $labelKey }}">{{ $labelName }}</label>
                    </div>
                @endforeach
                @foreach($extraAddLabels as $extraLabel)
                    <div class="form-check form-check-inline mb-2">
                        <input class="form-check-input" type="checkbox" name="addLabel[]" id="addLabel_{{ $extraLabel }}" value="{{ $extraLabel }}" checked>
                        <label class="form-check-label" for="addLabel_{{ $extraLabel }}">{{ $extraLabel }}</label>
                    </div>
                @endforeach
            </div>

            <div class="mb-3">
                <label class="form-label d-block">削除するラベル</label>
                @php
                    $currentRemoveLabels = $filter['action']['removeLabelIds'] ?? [];
                    $commonRemoveLabels = [
                        'UNREAD' => 'UNREAD (既読化)',
                        'INBOX' => 'INBOX (アーカイブ / 受信トレイをスキップ)',
                        'TRASH' => 'TRASH (ゴミ箱から戻す)',
                        'SPAM' => 'SPAM (迷惑メールを解除)',
                        'STARRED' => 'STARRED (スターを外す)',
                        'IMPORTANT' => 'IMPORTANT (重要マークを外す)',
                    ];
                    $extraRemoveLabels = array_diff($currentRemoveLabels, array_keys($commonRemoveLabels));
                @endphp
                @foreach($commonRemoveLabels as $labelKey => $labelName)
                    <div class="form-check form-check-inline mb-2">
                        <input class="form-check-input" type="checkbox" name="removeLabel[]" id="removeLabel_{{ $labelKey }}" value="{{ $labelKey }}" {{ in_array($labelKey, $currentRemoveLabels) ? 'checked' : '' }}>
                        <label class="form-check-label" for="removeLabel_{{ $labelKey }}">{{ $labelName }}</label>
                    </div>
                @endforeach
                @foreach($extraRemoveLabels as $extraLabel)
                    <div class="form-check form-check-inline mb-2">
                        <input class="form-check-input" type="checkbox" name="removeLabel[]" id="removeLabel_{{ $extraLabel }}" value="{{ $extraLabel }}" checked>
                        <label class="form-check-label" for="removeLabel_{{ $extraLabel }}">{{ $extraLabel }}</label>
                    </div>
                @endforeach
            </div>

            <div class="mb-3">
                <label for="forward" class="form-label">転送先アドレス</label>
                <input type="email" class="form-control" id="forward" name="forward" value="{{ $filter['action']['forward'] ?? '' }}" placeholder="例: other@example.com">
                <div class="form-text">※Gmail設定で事前に確認済みの転送先アドレスである必要があります。</div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ $basePath }}/filters" class="btn btn-secondary">キャンセル</a>
                <div>
                    <button type="button" id="preview-button" class="btn btn-info me-2">プレビュー</button>
                    <button type="submit" class="btn btn-primary">{{ isset($filter) ? '更新する' : '作成する' }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="preview-container" class="mt-4" style="display: none;">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">プレビュー結果 (最新20件)</h5>
            <button type="button" class="btn-close" id="close-preview"></button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>件名</th>
                            <th>スニペット</th>
                        </tr>
                    </thead>
                    <tbody id="preview-results">
                    </tbody>
                </table>
            </div>
            <div id="preview-empty" class="text-center py-3" style="display: none;">
                該当するメールは見つかりませんでした。
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('preview-button').addEventListener('click', async function() {
        const button = this;
        const form = button.closest('form');
        const formData = new FormData(form);
        const container = document.getElementById('preview-container');
        const resultsTable = document.getElementById('preview-results');
        const emptyMessage = document.getElementById('preview-empty');

        button.disabled = true;
        button.textContent = '読み込み中...';
        container.style.display = 'block';
        resultsTable.innerHTML = '';
        emptyMessage.style.display = 'none';

        try {
            const response = await fetch('{{ $basePath }}/filters/preview', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || 'プレビューの取得に失敗しました。');
            }

            const messages = await response.json();

            if (messages.length === 0) {
                emptyMessage.style.display = 'block';
            } else {
                messages.forEach(msg => {
                    const row = document.createElement('tr');

                    const dateCell = document.createElement('td');
                    dateCell.className = 'text-nowrap';
                    dateCell.textContent = msg.date;
                    row.appendChild(dateCell);

                    const subjectCell = document.createElement('td');
                    subjectCell.textContent = msg.subject;
                    row.appendChild(subjectCell);

                    const snippetCell = document.createElement('td');
                    snippetCell.className = 'text-muted small';
                    snippetCell.textContent = msg.snippet;
                    row.appendChild(snippetCell);

                    resultsTable.appendChild(row);
                });
            }
        } catch (error) {
            alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = 'プレビュー';
            container.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });

    document.getElementById('close-preview').addEventListener('click', function() {
        document.getElementById('preview-container').style.display = 'none';
    });
</script>
@endsection
