<?php

/**
 * お問い合わせフォーム用の公開鍵暗号(libsodium crypto_box_seal、X25519)。
 *
 * 送信側(confirm.php)はここにある関数のうち contactPublicKey()/contactSealRecord()
 * だけを使う。秘密鍵ファイルには一切アクセスしないので、公開フォーム側のコードや
 * その脆弱性経由で秘密鍵が漏れることはない。
 *
 * 復号は管理画面(main/SystemFolder/oppai/services/contact/index.php、ログイン必須)
 * からのみ行う。秘密鍵は .pusyuuHiddenFiles 配下(Webから配信されない場所、
 * pips/p-meikiee連携の合言葉ファイルと同じ置き場所)に保存する。
 */

if (!defined('CONTACT_SECRET_KEY_RELATIVE')) {
  define('CONTACT_SECRET_KEY_RELATIVE', '.pusyuuHiddenFiles/contact_secret_key.php');
}
if (!defined('CONTACT_PUBLIC_KEY_FILE')) {
  define('CONTACT_PUBLIC_KEY_FILE', __DIR__ . '/contact_public_key.php');
}

/** __DIR__から上位ディレクトリを遡って.pusyuuHiddenFiles/contact_secret_key.phpを探す(pips/p-meikiee連携と同じ探索方法)。見つからなければnull。 */
function contactFindHiddenSecretKeyFile(): ?string {
  $dir    = __DIR__;
  $result = null;

  // 見つかったら$resultが埋まり、その時点でループを抜けます。
  // 上限を8階層にしているのは、見つからないときにルートまで延々と上らせないためです。
  for ($i = 0; $i <= 8 && $result === null; $i++) {
    $candidate = $dir . '/' . CONTACT_SECRET_KEY_RELATIVE;
    $parent    = dirname($dir);

    if (file_exists($candidate)) {
      $result = $candidate;
    } else if ($parent === $dir) {
      break; // これ以上は上れない(ルートに着いた)
    } else {
      $dir = $parent;
    }
  }

  return $result;
}

/**
 * 送信側(confirm.php)専用: 公開鍵バイナリ(32byte)を返す。
 * まだ鍵ペアが一度も生成されていない場合(=サイト設置直後で、管理画面・
 * お問い合わせフォームのどちらもまだ一度もアクセスされていない場合)は、
 * ここで新しい鍵ペアを生成する。生成した秘密鍵はこの関数のローカル変数に
 * しか残らず、戻り値にも含まれない(呼び出し元は公開鍵しか受け取れない)。
 */
function contactPublicKey(): ?string {
  // 保存済みの公開鍵が「在って・文字列で・正しい長さに復号できる」ときだけ、それを使います。
  // 一つでも欠けていれば、鍵がまだ無い場合と同じ扱い(その場で作り直す)にします。
  $stored  = null;
  $encoded = file_exists(CONTACT_PUBLIC_KEY_FILE) ? require CONTACT_PUBLIC_KEY_FILE : null;
  $decoded = is_string($encoded) ? base64_decode($encoded, true) : false;

  if ($decoded !== false && strlen((string)$decoded) === SODIUM_CRYPTO_BOX_PUBLICKEYBYTES) {
    $stored = $decoded;
  } else {
    $stored = null;
  }

  if ($stored !== null) {
    $result = $stored;
  } else {
    $bootstrap = contactSecretKeyBootstrap();
    $result    = sodium_crypto_box_publickey_from_secretkey($bootstrap['secret']);
  }

  return $result;
}

/**
 * 秘密鍵バイナリ(32byte)を返す(管理側の復号、またはまだ鍵が無い場合の
 * 初回生成のいずれかでのみ使う)。
 * まだ鍵が存在しない場合はここで新しい鍵ペアを生成し、秘密鍵を.pusyuuHiddenFiles、
 * 公開鍵をcontact_public_key.phpへ保存する(どちらの側からの初回アクセスでも
 * 一度だけ実行される)。
 */
function contactSecretKeyBootstrap(): array {
  // 保存済みの秘密鍵が「在って・文字列で・正しい長さに復号できる」ときだけ、それを使います。
  // 一つでも欠けていれば新しく作り直します(=このサイトはまだ鍵を持っていない、という扱い)。
  $file    = contactFindHiddenSecretKeyFile();
  $encoded = ($file === null) ? null : require $file;
  $decoded = is_string($encoded) ? base64_decode($encoded, true) : false;
  $stored  = null;
  $result  = [];

  if ($decoded !== false && strlen((string)$decoded) === SODIUM_CRYPTO_BOX_SECRETKEYBYTES) {
    $stored = $decoded;
  } else {
    $stored = null;
  }

  if ($stored !== null) {
    $result = ['secret' => $stored, 'generated' => false];
  } else {
    // 初回のみ: 新しい鍵ペアをその場で生成する(ローカルで用意した値ではなく、
    // 実サーバーのCSPRNGでその都度作る)。
    $keyPair = sodium_crypto_box_keypair();
    $secret  = sodium_crypto_box_secretkey($keyPair);
    $public  = sodium_crypto_box_publickey($keyPair);

    // main/pusyuusystem/pages/contact から4つ上 = webserver_drive直下。
    $hiddenDir = dirname(__DIR__, 4) . '/.pusyuuHiddenFiles';
    if (!is_dir($hiddenDir)) {
      mkdir($hiddenDir, 0700, true);
    }
    $secretFile = $hiddenDir . '/contact_secret_key.php';
    file_put_contents(
      $secretFile,
      "<?php\n// お問い合わせフォーム用 crypto_box 秘密鍵。このファイルはWebから配信されない場所に置くこと。\nreturn '" . base64_encode($secret) . "';\n",
      LOCK_EX
    );
    @chmod($secretFile, 0600);

    file_put_contents(
      CONTACT_PUBLIC_KEY_FILE,
      "<?php\n// お問い合わせフォーム用 crypto_box 公開鍵。confirm.phpが読み込む(公開情報なので機密ではない)。\nreturn '" . base64_encode($public) . "';\n",
      LOCK_EX
    );

    $result = ['secret' => $secret, 'generated' => true];
  }

  return $result;
}

/** 管理側専用: sodium_crypto_box_seal_open()に渡せる完全な鍵ペア文字列を返す。 */
function contactKeyPair(): array {
  $bootstrap = contactSecretKeyBootstrap();
  $secret    = $bootstrap['secret'];
  $public    = sodium_crypto_box_publickey_from_secretkey($secret);

  return [
    'keypair'   => sodium_crypto_box_keypair_from_secretkey_and_publickey($secret, $public),
    'generated' => $bootstrap['generated'],
  ];
}

/** 1件分のレコード配列を公開鍵で封印し、jsonlの1行として保存できる文字列(改行なし)を返す。 */
function contactSealRecord(array $record, string $publicKey): string {
  $json   = json_encode($record, JSON_UNESCAPED_UNICODE);
  $sealed = sodium_crypto_box_seal($json, $publicKey);
  return base64_encode($sealed);
}

/** jsonlの1行(base64)を秘密鍵で開封してレコード配列を返す。壊れている/復号できない場合はnull。 */
function contactOpenRecord(string $line, string $keyPair): ?array {
  // 開封できない事情は「空行」「base64として壊れている」「鍵が合わない」「中身がJSONでない」の
  // 4つあり、どれも結末は同じ(nullを返す)です。理由ごとに途中で抜けず横に並べておくと、
  // 後から「この場合はログに残したい」と思ったときに、足す場所がその1行に決まります。
  $line   = trim($line);
  $sealed = ($line === '') ? false : base64_decode($line, true);
  $json   = ($sealed === false) ? false : sodium_crypto_box_seal_open($sealed, $keyPair);
  $record = ($json === false) ? null : json_decode($json, true);
  $result = null;

  if ($line === '') {
    $result = null;
  } else if ($sealed === false) {
    $result = null;
  } else if ($json === false) {
    $result = null;
  } else if (!is_array($record)) {
    $result = null;
  } else {
    $result = $record;
  }

  return $result;
}
