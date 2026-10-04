<?php

/*error_reporting(E_ALL);
ini_set('display_errors', true);*/

require_once __DIR__ . '/contact_crypto.php';

// 二重送信防止機能
session_start();
if (empty($_SESSION["POST_TOKEN"])) {
  $_SESSION["POST_TOKEN"] = bin2hex(random_bytes(32));
}

// フォームのボタンが押されたら
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // フォームから送信されたデータを各変数に格納
  $name = isset($_POST["name"]) ? $_POST["name"] : "";
  $furigana = isset($_POST["furigana"]) && $_POST["furigana"] !== "" ? $_POST["furigana"] : "なし";
  $email = isset($_POST["email"]) && $_POST["email"] !== "" ? $_POST["email"] : "なし";
  $sex = isset($_POST["sex"]) ? $_POST["sex"] : "";
  $item = isset($_POST["item"]) ? $_POST["item"] : "";
  $content = isset($_POST["content"]) ? $_POST["content"] : "";
  if (empty($name) || empty($content) || empty($sex) || empty($item)) {
    echo "あなたにポストは管理者が規定した範囲を超えた動作を行っているか、名前または、お問い合わせ内容が空です。";
    exit;
  }
}

function jsonDataSave($name, $furigana = "", $email = "", $sex, $item, $content) {
  $message = [
    "id" => bin2hex(random_bytes(8)),
    "created_at" => time(),
    "name" => $name,
    "furigana" => $furigana,
    "email" => $email,
    "sex" => $sex,
    "item" => $item,
    "content" => $content
  ];

  // 公開鍵で封印してから1行(jsonl)として追記する。この関数(confirm.php)は
  // 公開鍵しか扱わないため、既存のお問い合わせ内容を読み返すことはできない
  // (=読み返す必要がある管理側の操作は、秘密鍵を持つ管理画面でのみ行う)。
  $publicKey = contactPublicKey();
  if ($publicKey === null) {
    error_log('[contact] 公開鍵を取得できなかったため、お問い合わせを保存できませんでした。');
    echo "サーバー側の設定に問題が発生したため、送信を完了できませんでした。お手数ですが、しばらくしてからもう一度お試しください。";
    exit;
  }

  $line = contactSealRecord($message, $publicKey);
  $file = __DIR__ . '/kS8mqLsY.jsonl';
  file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);
}

function dataBaseDataSave($name, $furigana = "", $email = "", $sex, $item, $content) {
  $hostname = "localhost";
  $username = "pusyuuapp";
  $password = "password"; // 現在仮な為使う場合には変更
  $database = "contact_info";
  
  try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("INSERT INTO inquiries (name, furigana, email, sex, item, content) VALUES (:name, :furigana, :email, :sex, :item, :content)");
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":furigana", $furigana);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":sex", $sex);
    $stmt->bindParam(":item", $item);
    $stmt->bindParam(":content", $content);
    
    $stmt->execute();
  } catch(PDOException $error) {
    echo "database error ：" . $error->getMessage();
  }
}

// 送信ボタンが押されたら
if (isset($_POST["submit"])) {
  if (isset($_POST["POST_TOKEN"]) && $_POST["POST_TOKEN"] === $_SESSION["POST_TOKEN"]) {
    $_SESSION["POST_TOKEN"] = bin2hex(random_bytes(32));

    // 送信ボタンが押された時に動作する処理をここに記述する
    if (!empty($name) && !empty($content) && !empty($sex) && !empty($item)) {

      jsonDataSave($name, $furigana, $email, $sex, $item, $content);

      // 送信完了ページに画面遷移させる
      header("Location: ./confirm.php?thanks");
      exit;
    } else {
      echo("エラー：必要項目（名前、お問い合わせ、お問い合わせ内容、性別）のいずれかに文字が入力されていません。");
      echo("<br />");
      echo('万が一、このエラーが何度も出て先にすすめないようでしたら<a href="../../../pusyuusystem/pages/contact/">こちらのお問い合わせフォーム</a>をお使いください。');
      exit();
    }
  } else {
    echo("二重送信です。");
    exit();
  }
} else if (empty($_SERVER['HTTP_REFERER'])) {
  // 直接アクセスの場合の処理
  echo "直接のアクセスは許可されていません。";
  exit;
}

if (isset($_GET['thanks'])) {
  echo '
    <!DOCTYPE html>
    <html lang="ja">
      <head>
        <meta charset="UTF-8" />
        <meta name="robots" content="noindex,nofollow" />
        <meta name="viewport" content="width=device-width, user-scalable=yes, maximum-scale=6.0, minimum-scale=1.0" />
        <title>プシューサービス - お問い合わせフォーム</title>
        <link rel="stylesheet" href="style.css" />
        <link rel="shortcut icon" href="../../../pusyuusystem/images/favicon.ico" />
      </head>
      <body>
        <header>
          <h1>プシューサービスお問い合わせフォーム</h1>
        </header>
        <main class="main center">
          <div class="main_wrapper">
            <h3>お問い合わせ 送信完了</h3>
            <p>お問い合わせありがとうございました。内容を確認のうえ回答させて頂きます。それまでしばらくお待ちください。</p>
            <div class="center">
              <button type="button" onclick="location.href = \'/\'">プシューゲートウェイへ戻る</button>
            </div>
          </div>
        </main>
      </body>
    </html>
  ';
  exit();
}
?>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <meta name='robots' content='noindex,nofollow' />
    <meta name="viewport" content="width=device-width, user-scalable=yes, maximum-scale=6.0, minimum-scale=1.0" />
    <title>プシューサービス - お問い合わせフォーム</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="shortcut icon" href="../../../pusyuusystem/images/favicon.ico" />
  </head>
  <body>
    <header>
      <h1>プシューサービスお問い合わせフォーム</h1>
    </header>
    <main class="main">
      <div class="form_design-1">
        <h1 class="contact-title">お問い合わせ 内容確認</h1>
        <p>お問い合わせ内容のご確認をおねがします、お問い合わせの内容はこちらでよろしいでしょうか？</p>
        <p>もし、よろしければ「送信する」ボタンを押して送信を確定させて下さい。</p>
        <form action="confirm.php" method="post">
          <input type="hidden" name="name" value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="furigana" value="<?php echo htmlspecialchars($furigana, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="sex" value="<?php echo htmlspecialchars($sex, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="item" value="<?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?>" />
          <input type="hidden" name="content" value="<?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?>" />
          <label>お名前</label>
          <p><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></p>
          <label>ふりがな</label>
          <p><?php echo htmlspecialchars($furigana, ENT_QUOTES, 'UTF-8'); ?></p>
          <label>メールアドレス</label>
          <p><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></p>
          <label>性別</label>
          <p><?php echo htmlspecialchars($sex, ENT_QUOTES, 'UTF-8'); ?></p>
          <label>お問い合わせ項目</label>
          <p><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></p>
          <label>お問い合わせ内容</label>
          <p><?php echo nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')); ?></p>
          <button type="button" onclick="history.back(-1)">内容を修正する</button>
          <button type="submit" name="submit">送信する</button>
          <input type="hidden" name="POST_TOKEN" value="<?php echo htmlspecialchars($_SESSION["POST_TOKEN"], ENT_QUOTES, 'UTF-8'); ?>" />
        </form>
      </div>
    </main>
  </body>
</html>
