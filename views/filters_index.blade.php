@extends('layout', ['activeTab' => 'filters'])

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Gmail フィルター一覧</h5>
        <a href="{{ $basePath }}/filters/create" class="btn btn-primary btn-sm">新規フィルター作成</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>条件 (Criteria)</th>
                        <th>実行する操作 (Action)</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($filters as $filter)
                    <tr>
                        <td><code>{{ $filter['id'] }}</code></td>
                        <td>
                            <ul class="list-unstyled mb-0 small">
                                @if(!empty($filter['criteria']['from']))
                                    <li><strong>From:</strong> {{ $filter['criteria']['from'] }}</li>
                                @endif
                                @if(!empty($filter['criteria']['to']))
                                    <li><strong>To:</strong> {{ $filter['criteria']['to'] }}</li>
                                @endif
                                @if(!empty($filter['criteria']['subject']))
                                    <li><strong>件名:</strong> {{ $filter['criteria']['subject'] }}</li>
                                @endif
                                @if(!empty($filter['criteria']['query']))
                                    <li><strong>キーワード/含む:</strong> {{ $filter['criteria']['query'] }}</li>
                                @endif
                                @if(!empty($filter['criteria']['negatedQuery']))
                                    <li><strong>除外キーワード:</strong> {{ $filter['criteria']['negatedQuery'] }}</li>
                                @endif
                                @if(!empty($filter['criteria']['hasAttachment']))
                                    <li><strong>添付ファイルあり:</strong> はい</li>
                                @endif
                                @if(empty($filter['criteria']))
                                    <li class="text-muted">（指定なし）</li>
                                @endif
                            </ul>
                        </td>
                        <td>
                            <ul class="list-unstyled mb-0 small">
                                @if(!empty($filter['action']['addLabelIds']))
                                    <li><span class="badge bg-info text-dark">ラベル追加</span> {{ implode(', ', $filter['action']['addLabelIds']) }}</li>
                                @endif
                                @if(!empty($filter['action']['removeLabelIds']))
                                    <li><span class="badge bg-secondary">ラベル削除</span> {{ implode(', ', $filter['action']['removeLabelIds']) }}</li>
                                @endif
                                @if(!empty($filter['action']['forward']))
                                    <li><span class="badge bg-warning text-dark">転送</span> {{ $filter['action']['forward'] }}</li>
                                @endif
                                @if(empty($filter['action']))
                                    <li class="text-muted">（なし）</li>
                                @endif
                            </ul>
                        </td>
                        <td>
                            <form action="{{ $basePath }}/filters/delete" method="POST" onsubmit="return confirm('このフィルターを削除してもよろしいですか？');">
                                <input type="hidden" name="csrf_token" value="{{ $csrfToken }}">
                                <input type="hidden" name="id" value="{{ $filter['id'] }}">
                                <button type="submit" class="btn btn-outline-danger btn-sm">削除</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if(empty($filters))
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">Gmailフィルターが登録されていません。</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
