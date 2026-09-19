---
name: 41-release-to-wordpress-org
description: バージョン更新 PR のマージ後に、main から配布物を作って WordPress.org の SVN trunk へ反映する準備をする。利用者による svn commit の後は、公開状態を確認して Issue に記録する。
disable-model-invocation: true
argument-hint: "バージョンと、リリース Issue 番号(任意)。例: 0.2.0 #21"
---

`$ARGUMENTS` からリリースするバージョンと、リリース Issue の番号(任意)を抽出し、[spec/DEVELOPMENT_WORKFLOW.md](../../../spec/DEVELOPMENT_WORKFLOW.md) の「WordPress.org へのリリース」に従って作業してください。バージョンは `x.y.z` 形式で、空白、`,`、`、` 区切りを許可し、Issue 番号の `#` は除去します。

このコマンドは、バージョン更新の Issue・実装・コミット・PR(`/01`〜`/31` で実施し、マージ済みであること)より後の工程を担当します。同じコマンドを繰り返し実行しても、状態を壊さないこと。

## 開始前

- バージョンがない、`x.y.z` 形式でない場合は停止する。
- `git fetch origin` の後、`git status --short` を確認する。作業ツリーの変更は破棄、退避、コミットしない(配布物は `origin/main` から作るため、作業ツリーには触れない)。
- `git show origin/main:<path>` で、次の 5 か所のバージョン表記がすべて指定のバージョンであることを確認する。1 つでも異なる場合は停止し、バージョン更新が `main` にマージされていないことを報告する。
  - `ototsugu-connector.php` の `Version`
  - `readme.txt` の `Stable tag`(Changelog に `= <バージョン> =` があることも確認する)
  - `spec/SPECIFICATION.md` の現行バージョン
  - `blocks/event-list/editor.asset.php` の `version`
  - `includes/class-single-event.php` のスタイルのバージョン文字列
- Issue 番号がある場合は `gh issue view <number> --json number,title,body,state,url` で取得し、受入条件を確認する。
- SVN の作業コピーを探す。既定は、このリポジトリの隣にある `svn-<プラグインのスラッグ>` とする。見つからない場合は停止し、チェックアウトの方法を案内する。
- `svn` が PATH にない場合は、TortoiseSVN の `bin` を、そのコマンドの実行中だけ PATH に加える。

## 段階の判定

`svn ls https://plugins.svn.wordpress.org/<スラッグ>/tags` に `<バージョン>/` があるかで判定する。

- ない: 段階 1(準備)を実行する
- ある: 段階 2(確認と記録)を実行する

## 段階 1: 準備

1. 一時ディレクトリ(スクラッチパッドがあればそこ)に、`git archive --format=tar origin/main` の内容を展開する。
2. `.distignore` に書かれたファイル・フォルダを、その一時ディレクトリ内から削除する。削除するのは一時ディレクトリの中だけとし、削除前に対象のパスを確認する。
3. 配布物の一覧を確認する。`.distignore` の対象を含まず、`readme.txt`、`ototsugu-connector.php`、`includes/`、`blocks/`、`assets/`、`uninstall.php`、`LICENSE` を含むこと。前回のタグとの差(追加・削除されたファイル)を報告する。
4. 利用できる場合は、ローカルの WordPress に配布物を展開して有効化し、`wp plugin check` で ERROR がないこと、一覧ブロックと詳細ページが表示されることを確認する。実施しない場合は、その理由を報告する。
5. SVN の作業コピーを確認する。次のいずれかがある場合は停止し、状況を報告する。
   - `svn status` に出力がある(未コミットの変更が残っている)
   - 直下に `assets`、`tags`、`trunk`、`.svn` 以外のものがある、または `assets` の下にリポジトリ全体の入れ子チェックアウトがある(削除は利用者が実行する。Claude は再帰的な削除をしない)
6. `svn update` を実行する。
7. **PowerShell で** `robocopy <配布物> <作業コピー>\trunk /MIR /XD .svn` を実行する(終了コード 0〜7 は成功)。Git Bash では `/MIR` がパスとして解釈されるため使わない。
8. **PowerShell で** `svn add --force trunk` を実行する。`svn status` に `!`(削除されたファイル)がある場合は `svn delete` で登録する。`?` が残る場合は停止して報告する。
9. `svn status` の内容(追加・更新・削除の件数と一覧)を報告し、`diff -r`(`.svn` を除く)で trunk が配布物と一致することを確認する。trunk の `Version` と `Stable tag` も確認する。
10. 利用者が自分の端末で実行するコマンドを、次の順序で示して停止する。`<SVNユーザー名>` は利用者のものを使う(不明なら利用者に尋ねる。推測しない)。

```powershell
cd <SVN の作業コピー>
svn status                       # 想定した変更だけであることを確認する
svn commit trunk -m "Version <バージョン>" --username <SVNユーザー名>
svn update
svn cp trunk tags/<バージョン>
svn commit tags/<バージョン> -m "Tagging version <バージョン>" --username <SVNユーザー名>
```

### 段階 1 の禁止事項

- `svn commit` を実行しない。パスワードを入力できず、処理が固まって作業コピーが壊れる原因になる。
- `svn cp trunk tags/<バージョン>` を、trunk のコミットが成功する前に実行しない。
- `tags/` の既存のタグを変更・削除しない。
- 配布物の作成と `.distignore` の除去は、一時ディレクトリの中だけで行う。作業ツリーと SVN の作業コピーの既存ファイルを、`robocopy /MIR` 以外の方法で削除しない。

## 段階 2: 確認と記録

次を確認する。確認できない項目は、推測で PASS にしない。

- `svn ls https://plugins.svn.wordpress.org/<スラッグ>/tags` に `<バージョン>/` がある
- `svn log -l 5` に、trunk と tag のコミットがある
- trunk と `tags/<バージョン>` の `readme.txt` の `Stable tag` が、どちらも `<バージョン>` である
- WordPress.org の API(`https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=<スラッグ>`)の `version` が `<バージョン>` である
- 公開ページ(`https://wordpress.org/plugins/<スラッグ>/`)の `softwareVersion` が `<バージョン>` である

公開ページと API の反映には時間がかかることがある。反映されていない場合は、その旨を報告して Issue を閉じない。

Issue 番号がある場合は、`gh issue comment <number> --body-file -` で結果を記録する。コメントには、経緯、受入条件ごとの判定と根拠(コミットのリビジョン、確認した値)、次の作業を含める。すべて PASS の場合のみ、`gh issue close <number> --reason completed` で閉じる。

## 報告

次の順序で報告する。

```markdown
## リリース結果(段階 1: 準備 / 段階 2: 確認と記録)
### 対象
### 実施内容
### 検証
### 利用者が実行すること
### 未解決事項
```

リリース後の作業として、翻訳を受け付けている場合は、translate.wordpress.org での翻訳の登録が必要かを確認し、報告に含める。
