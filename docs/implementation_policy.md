# 実装方針ガイドライン

このドキュメントは、本プロジェクト（Cloud Functions + Firestore + PHP）の設計思想、環境変数の運用、および主要な実装パターンをまとめたものです。

---

## 1. アーキテクチャ概要

本アプリケーションは、Google Cloud Functions を基盤としたサーバーレスアーキテクチャを採用しています。

- **ランタイム**: PHP 8.2 以上
- **データベース**: Google Cloud Firestore (Native Mode)
- **エントリポイント (`index.php`)**:
  - `main_http`: 動作確認用のHTTPリクエストを処理します。
  - `main_event`: Pub/Sub などのイベントを処理し、Gmailの整理を実行します。

---

## 2. 環境変数の管理

| 変数名 | 説明 | 備考 |
| :--- | :--- | :--- |
| `APP_ENV` | 実行環境の指定。`production`, `test`, `local` のいずれか。 | |
| `FIREBASE_SERVICE_ACCOUNT` | Firestore および Gmail API 操作用のサービスアカウントキー（JSON形式）。 | |

---

## 3. Firestore の構成

設定情報は Firestore に保存されます。

- **ルートコレクション**: `gmail-cleanup`
  - 環境が `production` 以外の場合は `gmail-cleanup-test` となります。
- **パス**: `{ROOT}/configs/configs/{rule_id}`

---

## 4. コーディング規約とルール

- **日付操作**: 必ず `Carbon\Carbon` を使用してください。
- **命名規則**:
  - PHP/JavaScript の変数・メソッド名は `camelCase`。
  - クラス名は `PascalCase`。
- **ログ出力**: monolog を使用して `php://stdout` に出力します。
- **Gmail API**: `google/apiclient` を使用します。
