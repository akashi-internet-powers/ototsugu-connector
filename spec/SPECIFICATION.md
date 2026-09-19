# Ototsugu Connector 仕様書

## 1. 文書の位置づけ

この文書は Ototsugu Connector プラグインの機能仕様と設計上の決定事項をまとめたものです。実装と仕様に差異がある場合は、変更内容をこの文書へ反映し、コード・テスト・利用手順を同時に更新します。

- 対象リポジトリ: `ototsugu-connector`
- プラグイン名: Ototsugu Connector
- 現行バージョン: `0.2.0`
- ライセンス: GPLv2 or later
- 最低対応環境: WordPress 6.0、PHP 7.4

## 2. 目的

各団体の WordPress にプラグインを導入するだけで、相談会日程を管理し、Web ページと REST API から公開できるようにします。

相談会日程を中央の通知ネットワークやアプリへ提供する「接続プラグイン」として動作します。予約処理そのものは外部予約システムへ委譲します。

## 3. 責務と非責務

### 責務

- 相談会日程のカスタム投稿タイプを提供する
- 管理画面から日程情報を登録・更新できるようにする
- 日程を標準 WordPress REST API で公開する
- 日程一覧を動的ブロックで Web 表示する
- 受付中の日程に外部予約システムへのリンクを表示する
- 住所から Google マップ検索リンクを生成する

### 非責務

- 予約フォームの提供
- 予約データや申込者情報の保存
- 定員の自動管理・自動満席判定
- プッシュ通知の送信処理
- QR コード生成
- Google マップの埋め込み表示

## 4. 構成

```text
ototsugu-connector.php           プラグインの起動、フック登録、rewrite 更新
includes/
  class-date.php                  開催日の表示整形(書式は翻訳可能、月名・曜日名はロケール準拠)
  class-post-type.php             投稿タイプと投稿メタの登録
  class-meta-box.php              管理画面の入力 UI と保存
  class-rest-api.php              REST API 拡張の登録箇所
blocks/event-list/
  block.json                      ブロックのメタデータと属性
  editor.js                       ブロックエディター用登録と設定 UI
  editor.asset.php                エディタースクリプトの依存関係
  render.php                      公開画面のサーバーサイド描画
  style.css                       一覧・表・カードの表示スタイル
```

## 5. データモデル

### 5.1 カスタム投稿タイプ

- 投稿タイプ: `consultation_event`
- 公開状態: WordPress の `publish` を使用する
- REST API: `show_in_rest: true`
- 編集機能: タイトル、本文、アイキャッチ画像、カスタムフィールド
- 詳細ページ: 通常の WordPress 投稿 URL を使用する

投稿ステータスは受付状況を表すためには使用しません。満席・終了後も日程を公開し続ける必要があるため、受付状況は `status` メタで管理します。

### 5.2 投稿メタ

| キー | 型 | 必須 | 用途 |
| --- | --- | --- | --- |
| `start_at` | string | 推奨 | 日付と同日内の表示順を決める日時。公開画面では時刻を表示しません |
| `time_note` | string | 任意 | 公開画面に表示する時間帯の自由記述。例: `10:00〜 / 13:00〜` |
| `location_name` | string | 任意 | 会場名 |
| `location_address` | string | 任意 | 会場住所。Google マップ検索の元データ |
| `status` | string | 推奨 | `open`、`full`、`closed` |
| `reservation_url` | string | 任意 | 外部予約システムの URL |

`location` は旧仕様との互換性のため読み取り用に残しています。新規データでは `location_name` と `location_address` を使用します。

### 5.3 日時と時間帯

`start_at` は `datetime-local` 形式で保存します。同日に複数の日程がある場合、時刻部分を一覧の並び順に使用します。画面へ表示する時間は `time_note` のみです。

複数の時間枠は複数投稿に分割せず、1つの相談会日程の `time_note` に自由記述で登録します。厳密な枠管理や枠ごとの予約管理は行いません。

### 5.4 Google マップリンク

`location_address` がある場合、次の形式で検索 URL を生成します。

```text
https://www.google.com/maps/search/?api=1&query={会場名と住所}
```

検索クエリは会場名と住所を結合し、URL エンコードします。URL をメタとして保存しないため、住所変更時にも表示時に最新リンクが生成されます。

将来、地図埋め込みを追加する場合は、リンク生成と埋め込み表示を別の表示責務として拡張します。

## 6. 管理画面仕様

管理画面の「相談会日程」から新規作成・編集を行います。

### 入力項目

- タイトル: 相談会名
- 本文: 相談会の説明
- 開催日・並び順: 日付と同日内の順序を決める日時
- 時間帯: 公開表示用の自由記述
- 場所の名称: 会場名
- 住所: 会場住所
- ステータス: 受付中、満席、終了
- 予約 URL: 外部予約システムの URL
- アイキャッチ画像: カード形式で表示する画像

保存時は nonce、投稿編集権限、オートセーブを確認します。テキストは `sanitize_text_field`、URL は `esc_url_raw` で保存します。

### 詳細画面の表示設定

「相談会日程」>「設定」で、サイト全体の詳細画面レイアウトを選択できます。

| 値 | 表示 |
| --- | --- |
| `standard` | 相談会詳細情報を本文の前に表示します（初期値） |
| `two-pane` | デスクトップでは本文を左、相談会詳細情報カードを右に表示します |

`two-pane` はモバイル幅では1カラムに切り替わり、本文の下に詳細情報カードを表示します。設定値が未設定または不正な場合は `standard` として扱います。

## 7. Web 表示ブロック

- ブロック名: `相談会日程一覧`
- 内部名: `ototsugu-connector/event-list`
- 描画方式: サーバーサイドレンダリング
- データ取得: `consultation_event` の公開済み投稿
- 並び順: `start_at` の昇順

### 表示形式

| 値 | 表示 |
| --- | --- |
| `list` | 1件を1行で表示 |
| `table` | 開催日、相談会名、場所、ステータスなどを表形式で表示 |
| `card` | 日付を左、情報を中央、アイキャッチ画像を右に表示 |

### ブロック属性

| 属性 | 型 | 初期値 | 内容 |
| --- | --- | --- | --- |
| `layout` | string | `list` | 表示形式 |
| `dateFormat` | string | `full` | 曜日付きの日付表示形式 |
| `showDetailLink` | boolean | `true` | 詳細ページへのリンク表示 |
| `showReservationLink` | boolean | `true` | 予約 URL リンク表示 |

`dateFormat` の値は言語に依存しない内部値です。表示は、サイトのロケールに従います。

| 値 | 内容 | 英語ロケールの例 | 日本語ロケールの例 |
| --- | --- | --- | --- |
| `full`、`date` | 年月日と曜日 | `September 6, 2026 (Sun)` | `2026年9月6日（日）` |
| `slash` | スラッシュ区切りの年月日と曜日 | `2026/09/06 (Sun)` | `2026/09/06（日）` |
| `short` | 年を省いた月日と曜日 | `Sep 6 (Sun)` | `9月6日（日）` |

### リンク表示条件

- 詳細リンク: `showDetailLink` が有効な場合に表示
- 予約リンク: `showReservationLink` が有効、`status` が `open`、`reservation_url` が存在する場合に表示
- 地図リンク: `location_address` が存在する場合に表示

## 8. REST API

標準の WordPress REST API を使用します。

### 一覧

```text
GET /wp-json/wp/v2/consultation_event
```

### 詳細

```text
GET /wp-json/wp/v2/consultation_event/{id}
```

公開済みの日程の取得は未ログインで行えます。下書きの取得、登録、更新には WordPress の認証と権限が必要です。

カスタム項目はレスポンスの `meta` に含まれます。場所は `location_name` と `location_address` を使用し、地図 URL は API に保存・公開しません。

独自のレスポンス形状が必要になった場合は `includes/class-rest-api.php` の `register_fields` へ実装を追加し、アプリ側の型定義と同時に更新します。

## 9. セキュリティと入力処理

- 直接アクセス時は `ABSPATH` の存在を確認して終了する
- 管理画面保存時に nonce を検証する
- 投稿編集権限を確認する
- テキスト入力をサニタイズする
- URL を URL 用 API でサニタイズする
- HTML 出力時は文脈に応じてエスケープする
- 外部リンクには `target="_blank"` と `rel="noopener"` を使用する
- 予約処理や個人情報をプラグイン内に保持しない

## 10. 互換性と運用

- Local の MariaDB 環境で開発・検証する
- 本番と同じ PHP、WordPress、テーマ、パーマリンク設定でリリース前確認を行う
- プラグイン有効化時に rewrite ルールを更新する
- 詳細ページが 404 の場合は、プラグインの再有効化またはパーマリンク設定の保存で rewrite ルールを再生成する
- 外部アプリから Local の REST API を参照する場合は、公開可能な検証 URL またはトンネルを用意する

### 多言語対応

- 表示文字列は、翻訳関数と Text Domain `ototsugu-connector` で包む。元の文字列(msgid)は英語とする
- 対象は、PHP、ブロックエディター用スクリプト(`editor.js`)、`block.json` の `title` と `description`、プラグインヘッダーの `Description`
- 翻訳は translate.wordpress.org で受け付ける。プラグインには言語ファイルを同梱せず、`Domain Path` ヘッダーも設けない
- 日付の書式文字列は翻訳可能とし、月名・曜日名は `wp_date()` でサイトのロケールに従って取得する。日付と曜日の組み立ては `includes/class-date.php` に集約する
- `status` の保存値(`open`、`full`、`closed`)、ブロック属性の値、REST API の応答は言語に依存しない。翻訳されるのは画面上の表示だけである
- 利用者が入力した内容(タイトル、本文、時間帯、場所の名称、住所)は翻訳の対象外とする
- 本仕様書に書く画面上の名称は日本語表示のものである。英語表示では、「相談会日程」は Consultation Events、「相談会日程一覧」は Consultation Event List、「相談会 詳細情報」は Consultation Event Details となる

## 11. 将来拡張

優先度や実装時期は別途決定します。

- Google マップの地図埋め込み
- REST API のアプリ向けレスポンス整形
- 投稿一覧のフィルタリングやページネーション
- 複数時間枠の構造化
- 定員情報との連携
- QR コード生成
- 中央管理側への通知連携

## 12. 変更時の確認事項

仕様や実装を変更した場合は、次を確認します。

- 管理画面で新規登録・編集・保存ができる
- REST API の公開データが意図どおりである
- 一行、表、カードの表示を確認する
- 詳細、予約、地図リンクの表示条件を確認する
- 同日複数日程の並び順を確認する
- 既存の旧 `location` データが壊れず表示される
- 英語ロケールと日本語ロケールの両方で、画面上の文字列と日付・曜日の表示を確認する(翻訳関数の外に日本語や英語の文言を直書きしない)
- Local とリポジトリの実装ファイルが一致している
- `git diff --check` と PHP/JSON/JavaScript の診断を実行する
