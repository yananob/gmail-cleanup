@extends('layout', ['activeTab' => 'cleanup'])

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">削除ルール一覧</h5>
        <a href="{{ $basePath }}/create" class="btn btn-primary btn-sm">新規作成</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>キーワード</th>
                        <th>From</th>
                        <th>To</th>
                        <th>件名</th>
                        <th>ラベル</th>
                        <th>対象期間 (date_before)</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($configs as $config)
                    <tr>
                        <td>{{ $config['data']['keyword'] ?? '-' }}</td>
                        <td>{{ $config['data']['from'] ?? '-' }}</td>
                        <td>{{ $config['data']['to'] ?? '-' }}</td>
                        <td>{{ $config['data']['subject'] ?? '-' }}</td>
                        <td>{{ $config['data']['label'] ?? '-' }}</td>
                        <td>{{ $config['data']['date_before'] ?? '-' }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ $basePath }}/edit?id={{ $config['id'] }}" class="btn btn-outline-secondary">編集</a>
                                <form action="{{ $basePath }}/delete" method="POST" onsubmit="return confirm('本当に削除しますか？');">
                                    <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">
                                    <input type="hidden" name="id" value="{{ $config['id'] }}">
                                    <button type="submit" class="btn btn-outline-danger">削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @if(empty($configs))
                    <tr>
                        <td colspan="7" class="text-center">設定がありません。</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">クリーンナップ登録支援 (半年以前のメール検索)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">
            半年（6ヶ月）以上前のメールを検索して表示します。メールの件名やFromをコピーして新規ルールの登録に利用できます。
        </p>
        <form id="assist-search-form" class="row g-2 align-items-center mb-3">
            <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">
            <input type="hidden" name="date_before" value="P6M">
            <div class="col-md-8 col-sm-12">
                <input type="text" class="form-control" name="keyword" id="assist-keyword" placeholder="絞り込みキーワード (例: メルマガ, noreply@...) [省略可]">
            </div>
            <div class="col-md-4 col-sm-12 text-end">
                <button type="button" id="assist-search-btn" class="btn btn-info w-100">半年以前のメールを検索</button>
            </div>
        </form>

        <div id="assist-results-container" style="display: none;">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead>
                        <tr>
                            <th>日付</th>
                            <th>From</th>
                            <th>件名</th>
                            <th>スニペット</th>
                            <th style="min-width: 220px;">操作</th>
                        </tr>
                    </thead>
                    <tbody id="assist-results-body">
                    </tbody>
                </table>
            </div>
            <div id="assist-empty" class="text-center py-3 text-muted" style="display: none;">
                半年以上前の該当するメールは見つかりませんでした。
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

    document.getElementById('assist-search-btn').addEventListener('click', async function() {
        const button = this;
        const form = document.getElementById('assist-search-form');
        const formData = new FormData(form);
        const container = document.getElementById('assist-results-container');
        const resultsBody = document.getElementById('assist-results-body');
        const emptyMessage = document.getElementById('assist-empty');

        button.disabled = true;
        button.textContent = '検索中...';
        container.style.display = 'block';
        resultsBody.innerHTML = '';
        emptyMessage.style.display = 'none';

        try {
            const response = await fetch('{{ $basePath }}/preview', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.error || 'メールの検索に失敗しました。');
            }

            const messages = await response.json();

            if (messages.length === 0) {
                emptyMessage.style.display = 'block';
            } else {
                const basePath = '{{ $basePath }}';
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

                    const createRuleLink = document.createElement('a');
                    createRuleLink.className = 'btn btn-outline-primary';
                    createRuleLink.textContent = '登録';
                    createRuleLink.title = 'このFromと件名でルール作成';
                    const params = new URLSearchParams();
                    if (msg.from) params.set('from', msg.from);
                    if (msg.subject) params.set('subject', msg.subject);
                    createRuleLink.href = `${basePath}/create?${params.toString()}`;
                    btnGroup.appendChild(createRuleLink);

                    actionCell.appendChild(btnGroup);
                    row.appendChild(actionCell);

                    resultsBody.appendChild(row);
                });
            }
        } catch (error) {
            alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = '半年以前のメールを検索';
        }
    });
</script>
@endsection
