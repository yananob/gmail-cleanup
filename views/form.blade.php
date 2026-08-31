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
                <label for="date_before" class="form-label">対象期間 (date_before)</label>
                <select class="form-select" id="date_before" name="date_before" required>
                    <option value="P1M" {{ ($config['date_before'] ?? '') === 'P1M' ? 'selected' : '' }}>1ヶ月</option>
                    <option value="P3M" {{ ($config['date_before'] ?? '') === 'P3M' ? 'selected' : '' }}>3ヶ月</option>
                    <option value="P6M" {{ ($config['date_before'] ?? '') === 'P6M' ? 'selected' : '' }}>6ヶ月</option>
                    <option value="P1Y" {{ ($config['date_before'] ?? '') === 'P1Y' ? 'selected' : '' }}>1年</option>
                </select>
                <div class="form-text">指定した期間より前のメールを削除対象にします。</div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ $basePath }}/" class="btn btn-secondary">キャンセル</a>
                <div>
                    <button type="button" id="preview-button" class="btn btn-info me-2">プレビュー</button>
                    <button type="submit" class="btn btn-primary">{{ isset($config) ? '更新する' : '保存する' }}</button>
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
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>From</th>
                            <th>件名</th>
                            <th>スニペット</th>
                            <th style="min-width: 170px;">操作</th>
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
    function copyTextToClipboard(text, successCallback) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(successCallback).catch(() => {
                fallbackCopyText(text, successCallback);
            });
        } else {
            fallbackCopyText(text, successCallback);
        }
    }

    function fallbackCopyText(text, successCallback) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            if (successCallback) successCallback();
        } catch (err) {
            alert('コピーに失敗しました');
        }
        document.body.removeChild(textArea);
    }

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
            const response = await fetch('{{ $basePath }}/preview', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('プレビューの取得に失敗しました。');
            }

            const messages = await response.json();

            if (messages.length === 0) {
                emptyMessage.style.display = 'block';
            } else {
                messages.forEach(msg => {
                    const row = document.createElement('tr');

                    const dateCell = document.createElement('td');
                    dateCell.className = 'text-nowrap small';
                    dateCell.textContent = msg.date || '-';
                    row.appendChild(dateCell);

                    const fromCell = document.createElement('td');
                    fromCell.className = 'small';
                    fromCell.textContent = msg.from || '-';
                    row.appendChild(fromCell);

                    const subjectCell = document.createElement('td');
                    subjectCell.className = 'small fw-bold';
                    subjectCell.textContent = msg.subject || '(無題)';
                    row.appendChild(subjectCell);

                    const snippetCell = document.createElement('td');
                    snippetCell.className = 'text-muted small';
                    snippetCell.textContent = msg.snippet || '';
                    row.appendChild(snippetCell);

                    const actionCell = document.createElement('td');
                    actionCell.className = 'text-nowrap';

                    const btnGroup = document.createElement('div');
                    btnGroup.className = 'btn-group btn-group-sm';

                    const copyFromBtn = document.createElement('button');
                    copyFromBtn.type = 'button';
                    copyFromBtn.className = 'btn btn-outline-secondary';
                    copyFromBtn.textContent = 'Fromをコピー';
                    copyFromBtn.addEventListener('click', function() {
                        copyTextToClipboard(msg.from || '', () => {
                            const originalText = copyFromBtn.textContent;
                            copyFromBtn.textContent = 'コピー完了!';
                            copyFromBtn.classList.remove('btn-outline-secondary');
                            copyFromBtn.classList.add('btn-success');
                            setTimeout(() => {
                                copyFromBtn.textContent = originalText;
                                copyFromBtn.classList.remove('btn-success');
                                copyFromBtn.classList.add('btn-outline-secondary');
                            }, 1500);
                        });
                    });
                    btnGroup.appendChild(copyFromBtn);

                    const copySubjectBtn = document.createElement('button');
                    copySubjectBtn.type = 'button';
                    copySubjectBtn.className = 'btn btn-outline-secondary';
                    copySubjectBtn.textContent = '件名をコピー';
                    copySubjectBtn.addEventListener('click', function() {
                        copyTextToClipboard(msg.subject || '', () => {
                            const originalText = copySubjectBtn.textContent;
                            copySubjectBtn.textContent = 'コピー完了!';
                            copySubjectBtn.classList.remove('btn-outline-secondary');
                            copySubjectBtn.classList.add('btn-success');
                            setTimeout(() => {
                                copySubjectBtn.textContent = originalText;
                                copySubjectBtn.classList.remove('btn-success');
                                copySubjectBtn.classList.add('btn-outline-secondary');
                            }, 1500);
                        });
                    });
                    btnGroup.appendChild(copySubjectBtn);

                    actionCell.appendChild(btnGroup);
                    row.appendChild(actionCell);

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