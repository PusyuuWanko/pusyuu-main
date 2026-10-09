# プシューゲートウェイ

プシューサービスのホームと、各サービスが共有する部品(pusyuusystem)・プロフィールページです。

稼働中のサイト: https://pusyuuwanko.com

## 動かすときの注意

鍵・DB接続情報などの秘密(env相当)は、このリポジトリには含まれていません。
本番環境ではプロジェクトフォルダの1つ上の階層にある `.pusyuuHiddenFiles/` に置いて読み込んでいます。
また、運用中に溜まる利用者データ(投稿・メッセージ・ログ等)も `.gitignore` で除外しています。

## 個別に公開している部品

各サービスが共有する部品は、それぞれ個別のリポジトリで公開しています。
動かすときは、各リポジトリのファイルを同じ場所に置いてください(`.gitignore` で除外しています)。

### PHP ライブラリ(`pusyuusystem/scripts/php_scripts/`)

| ファイル | リポジトリ | 内容 |
| --- | --- | --- |
| `meikiee_client.php` | [meikiee_client](https://github.com/PusyuuWanko/meikiee_client) | アカウント基盤(プシューメイキィ)と話すクライアント |
| `panic_handler.php` | [panic_handler](https://github.com/PusyuuWanko/panic_handler) | 致命的なエラーのときの案内画面 |
| `pusyuu_registry_client.php` | [pusyuu_registry_client](https://github.com/PusyuuWanko/pusyuu_registry_client) | 台帳 API を読む係 |
| `pusyuu_session.php` | [pusyuu_session](https://github.com/PusyuuWanko/pusyuu_session) | セッションライブラリ(回数制限を含む) |
| `usage_tracker.php` | [usage_tracker](https://github.com/PusyuuWanko/usage_tracker) | プロダクトごとのアクセス記録 |

### API(`pusyuusystem/apis/`)

| フォルダ | リポジトリ | 内容 |
| --- | --- | --- |
| `pusyuu_blog/` | [pusyuu_blog](https://github.com/PusyuuWanko/pusyuu_blog) | ブログ記事の一覧と保存 |
| `pusyuu_ips/` | [pusyuu_ips](https://github.com/PusyuuWanko/pusyuu_ips) | 投稿データの中継・保存 |
| `pusyuu_push/` | [pusyuu_push](https://github.com/PusyuuWanko/pusyuu_push) | 全サービス共通のプッシュ通知 |
| `server_status/` | [server_status](https://github.com/PusyuuWanko/server_status) | サーバーの状態を返す |

### スクリプト(`pusyuusystem/scripts/`)

| ファイル | リポジトリ |
| --- | --- |
| `dragg-and-drop.js` | [Drag_and_drop_in_JS](https://github.com/PusyuuWanko/Drag_and_drop_in_JS) の `v2/` |
| `internet_checker.js` | [internet_connect_checker](https://github.com/PusyuuWanko/internet_connect_checker) の `internet_checker_v3/` |
| `notify-sound.js` | [notify-sound](https://github.com/PusyuuWanko/notify-sound) |
| `privacy_notice.js` | [privacy_notice](https://github.com/PusyuuWanko/privacy_notice) |
| `push-subscribe.js` | [push-subscribe](https://github.com/PusyuuWanko/push-subscribe) |
| `pusyuuImageEffect.js` | [pusyuuImageEffect](https://github.com/PusyuuWanko/pusyuuImageEffect) |
| `pusyuuWallpaper.js` | [pusyuuWallpaper](https://github.com/PusyuuWanko/pusyuuWallpaper) |
| `request-indicator.js` | [request-indicator](https://github.com/PusyuuWanko/request-indicator) |
| `scroll-enhance.js` | [scroll-enhance](https://github.com/PusyuuWanko/scroll-enhance) |
| `touch-device-tool-tip.js` | [touch-device-tool-tip](https://github.com/PusyuuWanko/touch-device-tool-tip) |

`main.js`・`main-push.js`・`push-sw.js` は main 自身の画面用なので、このリポジトリに含めています。
`anime.min.js` は外部のライブラリ([anime.js](https://github.com/juliangarnier/anime)、MIT License)です。

`notation.php`(規約表示)は、このリポジトリの `documents/pusyuuNotation.json` を読む main 専用のものなので、ここに含めています。

## ライセンス

[MIT License](LICENSE) © 2021-2026 ISAMI ABE
