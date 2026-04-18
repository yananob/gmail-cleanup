# Gmail 自動削除アプリ

古いメールを自動削除（ゴミ箱へ移動）するGoogle Cloud Functionsアプリケーションです。`config.json` で削除ルールを定義し、定期的に実行することで、メールボックスを自動整理できます。

---

## 概要

### 機能

このアプリケーションは、Google Gmailの以下の条件に基づいて自動的にメールを削除します：

- **送信元アドレス（from）**：指定したメールアドレスから受信したメール
- **送信先アドレス（to）**：指定した宛先へのメール
- **件名（subject）**：指定した文字列を含むメール
- **ラベル（label）**：指定したGmailラベルが付いているメール
- **キーワード（keyword）**：メール本文に含まれるキーワード
- **日付（date_before）**：指定した期間より前のメール

複数の条件を組み合わせる場合、**全ての条件を満たす**メールが削除対象になります（AND結合）。

### 実行環境

- **ランタイム**：PHP 8.2 以上
- **プラットフォーム**：Google Cloud Functions
- **トリガー**：Cloud Pub/Sub イベント（定期実行用）

### 削除処理の詳細

対象メールはゴミ箱へ移動します（完全削除ではなく、ゴミ箱から復元可能な状態）。ただし、スパムフォルダ内のメールは処理対象に含まれません。

---

## セットアップ

### 前提条件

- Google Cloud プロジェクトの作成
- Gmail API の有効化
- Google OAuth 2.0 認証情報の取得

### 初期設定

詳細なセットアップ手順とアーキテクチャについては、[docs/implementation_policy.md](docs/implementation_policy.md) を参照してください。

### デプロイ

デプロイスクリプトは `miscs/merge_and_deploy_main.sh` を実行してください。

---

## config.json スキーマ

### 構造

```json
{
  "targets": [
    {
      "keyword": "検索キーワード",
      "from": "送信元メールアドレス",
      "to": "送信先メールアドレス",
      "subject": "件名",
      "label": "ラベル名",
      "date_before": "ISO 8601 期間形式"
    }
  ]
}
```

### フィールド詳細

#### `keyword` （オプション）

メール本文に含まれるキーワード。複数キーワードの指定や除外条件も可能です。

- 例：`"hogehoge"` → 「hogehoge」を含むメール
- 例：`"-\"life is beautiful\""` → 「life is beautiful」を含まないメール
- 例：`"python AND tutorial"` → 「python」と「tutorial」の両方を含むメール

#### `from` （オプション）

送信元メールアドレス。部分一致で指定可能です。

- 例：`"info@example.com"` → 完全一致のメール
- 例：`"@example.com"` → ドメイン指定で、example.com からのメール全て

#### `to` （オプション）

送信先メールアドレス。`from` と同様に部分一致で指定可能です。

- 例：`"notify@myapp.com"` → 完全一致
- 例：`"@blogger.com"` → blogger.com への全メール

#### `subject` （オプション）

件名に含まれるテキスト。

- 例：`"エラー通知"` → 件名に「エラー通知」を含むメール
- 例：`"[ALERT]"` → 件名に「[ALERT]」を含むメール

#### `label` （オプション）

Gmailで設定したラベル。

- 例：`"mailmag"` → 「mailmag」ラベルが付いているメール
- 例：`"未読"` → 未読ラベルが付いているメール

#### `date_before` （オプション）

指定した期間より前のメール。ISO 8601 期間形式で指定します。

| 指定例 | 意味 | 削除対象 |
|:---|:---|:---|
| `"P1M"` | 1ヶ月 | 1ヶ月より前のメール |
| `"P3M"` | 3ヶ月 | 3ヶ月より前のメール |
| `"P6M"` | 6ヶ月 | 6ヶ月より前のメール |
| `"P1Y"` | 1年 | 1年より前のメール |
| `"P1Y2M"` | 1年2ヶ月 | 1年2ヶ月より前のメール |

期間形式の詳細：
- `P` で始まるISO 8601形式
- `Y`=年、`M`=月、`D`=日、`W`=週
- 例：`P1Y2M3D` = 1年2ヶ月3日前

---

## ルール定義例

### 例1：メールマガジンの古いメールを削除

1ヶ月より前の「mailmag」ラベルが付いているメールを削除します。

```json
{
  "targets": [
    {
      "label": "mailmag",
      "date_before": "P1M"
    }
  ]
}
```

### 例2：特定の送信者からの通知メールを削除

「info@example.com」から送信された、且つ件名に「通知」を含む6ヶ月前のメールを削除します。

```json
{
  "targets": [
    {
      "from": "info@example.com",
      "subject": "通知",
      "date_before": "P6M"
    }
  ]
}
```

### 例3：複数のルール（複数ターゲット）

複数の削除ルールを定義できます。各ターゲットは独立して評価されます。

```json
{
  "targets": [
    {
      "label": "mailmag",
      "date_before": "P1M"
    },
    {
      "from": "alerts@system.example.com",
      "date_before": "P3M"
    },
    {
      "from": "noreply@github.com",
      "subject": "notification",
      "date_before": "P6M"
    }
  ]
}
```

### 例4：除外条件を使った削除

キーワードの除外条件（`-` 記号）を使って、特定のメールを対象から除外します。

```json
{
  "targets": [
    {
      "keyword": "-\"important\"",
      "label": "mailmag",
      "date_before": "P1M"
    }
  ]
}
```

このルールは、「mailmag」ラベルが付いており、本文に「important」という単語を含まない、1ヶ月より前のメールを削除します（AND結合）。

---

## 条件の組み合わせ方

同一ターゲット内で複数のフィールドを指定した場合、**全ての条件を満たす**メールが削除対象になります。

### AND結合の例

次の設定では、以下の**全て**を満たすメールが削除対象です：

```json
{
  "targets": [
    {
      "from": "sales@example.com",
      "subject": "特別オファー",
      "date_before": "P1M"
    }
  ]
}
```

- ✅ 送信元が「sales@example.com」**かつ**
- ✅ 件名に「特別オファー」を含む**かつ**
- ✅ 1ヶ月より前

この3つの条件を**全て**満たすメールが削除対象です。

---

## 実装詳細

- **アーキテクチャ、環境変数、コーディング規約**については [docs/implementation_policy.md](docs/implementation_policy.md) を参照してください。
- **削除処理の実装**は `src/GmailCleanupHandler.php`（コア処理）、`src/Query.php`（検索クエリ生成）で定義されています。
- **テスト**は `tests/run_tests.sh` で実行可能です。
