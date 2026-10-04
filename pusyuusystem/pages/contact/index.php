<?php
session_start();
$_SESSION["CONTACT_CSRF_TOKEN"] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name='robots' content='noindex,nofollow' />
    <meta name="viewport" content="width=device-width, user-scalable=yes, maximum-scale=6.0, minimum-scale=1.0" />
    <title>プシューサービス - お問い合わせフォーム</title>
    <link rel="stylesheet" href="style.css" />
    <link rel="shortcut icon" href="../../../pusyuusystem/images/favicon.ico" />
    <script type="text/javascript" src="contact.js"></script>
  </head>
  <body>
    <header>
      <h1>プシューサービスお問い合わせフォーム</h1>
    </header>
    <main class="main">
      <div class="form_design-1">
        <h2 class="contact-title">お問い合わせ 内容入力</h1>
        <p>お問い合わせ内容をご入力の上、「確認画面へ」ボタンをクリックしてください。</p>
        <form action="confirm.php" method="post" name="form" onsubmit="return validate()">
          <label>お名前<span>必須</span></label>
          <input type="text" name="name" placeholder="例：阿部勇生" value="" />
          <label>ふりがな<span class="free">任意</span></label>
          <input type="text" name="furigana" placeholder="例：あべいさみ" value="" />
          <label>メールアドレス<span>必須</span></label>
          <input type="text" name="email" placeholder="例：youremail@pusyuuwanko.com" value="" />
          <label>性別<span>必須</span></label>
          <div class="center">
            <div><input type="radio" name="sex" value="男性" checked />男性</div>
            <div><input type="radio" name="sex" value="女性" />女性</div>
            <div><input type="radio" name="sex" value="その他" />その他</div>
          </div>
          <label>お問い合わせ項目<span>必須</span></label>
          <select name="item">
            <option value="">お問い合わせ項目を選択してください</option>
            <option value="ご質問・お問い合わせ">ご質問・お問い合わせ</option>
            <option value="ご意見・ご感想">ご意見・ご感想</option>
          </select>
          <label>お問い合わせ内容<span>必須</span></label>
          <textarea name="content" rows="5" placeholder="お問合せ内容を入力"></textarea>
          <button type="submit">確認画面へ</button>
          <input type="hidden" name="contact_csrf_token" value="<?php echo $_SESSION["CONTACT_CSRF_TOKEN"]; ?>" />
        </form>
      </div>
    </main>
    <footer>
    </footer>
  </body>
</html>