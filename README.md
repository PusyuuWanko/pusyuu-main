# プシューゲートウェイ

プシューサービスのホームと、各サービスが共有する部品(pusyuusystem)・プロフィールページです。

稼働中のサイト: https://pusyuuwanko.com

## 動かすときの注意

鍵・DB接続情報などの秘密(env相当)は、このリポジトリには含まれていません。
本番環境ではプロジェクトフォルダの1つ上の階層にある `.pusyuuHiddenFiles/` に置いて読み込んでいます。
また、運用中に溜まる利用者データ(投稿・メッセージ・ログ等)も `.gitignore` で除外しています。

## 共有ライブラリ

`pusyuusystem/scripts/php_scripts/` に置く共有ライブラリは、それぞれ個別のリポジトリで公開しています。
動かすときは、各リポジトリの PHP ファイルをこのフォルダに置いてください(`.gitignore` で除外しています)。

| ファイル | リポジトリ | 内容 |
| --- | --- | --- |
| `meikiee_client.php` | [meikiee_client](https://github.com/PusyuuWanko/meikiee_client) | アカウント基盤(プシューメイキィ)と話すクライアント |
| `panic_handler.php` | [panic_handler](https://github.com/PusyuuWanko/panic_handler) | 致命的なエラーのときの案内画面 |
| `pusyuu_registry_client.php` | [pusyuu_registry_client](https://github.com/PusyuuWanko/pusyuu_registry_client) | 台帳 API を読む係 |
| `pusyuu_session.php` | [pusyuu_session](https://github.com/PusyuuWanko/pusyuu_session) | セッションライブラリ(回数制限を含む) |
| `usage_tracker.php` | [usage_tracker](https://github.com/PusyuuWanko/usage_tracker) | プロダクトごとのアクセス記録 |

`notation.php`(規約表示)は、このリポジトリの `documents/pusyuuNotation.json` を読む main 専用のものなので、ここに含めています。

## ライセンス

[MIT License](LICENSE) © 2021-2026 ISAMI ABE
