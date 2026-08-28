@extends('layout', ['activeTab' => 'cleanup'])

@section('content')
<div class="card">
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
@endsection
