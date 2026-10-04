<?php
// 住所のうち台帳(pusyuu_registry)から決めるもの。main 自身のファイルやページへの
// リンクは「/」から始めます(main はどの顔でもホストの一番上に居るため)。
// OGP・Twitterカードの画像のように、よそ(SNS)が取りに来るものだけは公開の顔の住所です。
// 台帳が読めないときは「/」から始まる形に倒します。
$directry = __DIR__ . '/../../../scripts/php_scripts/pusyuu_registry_client.php';
if (file_exists($directry)) {
  require_once($directry);
}
$mainPublicBaseHtml = htmlspecialchars((class_exists('PusyuuRegistryClient') ? PusyuuRegistryClient::publicUrl('main') : null) ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name="description" content="<?php echo $description; ?>" />
    <meta name="viewport" content="width=device-width, user-scalable=yes, maximum-scale=6.0, minimum-scale=1.0" />
    <meta name="copyright" content="© 2020-2024 created by PusyuuWanko/" />
    <meta name="twitter:card" content="Summary_large_Image" />
    <meta name="twitter:site" content="@PusyuuWanko" />
    <meta name="twitter:title" content="<?php echo $title; ?>" />
    <meta name="twitter:description" content="<?php echo $description; ?>" />
    <meta name="twitter:image" content="<?= $mainPublicBaseHtml ?>/pusyuusystem/icons/gateway_page_logo.png" />
    <link rel="shortcut icon" href="../../../../pusyuusystem/images/favicon.ico" />
    <title><?php echo $title; ?></title>
  </head>
  <body>
    <main class="main">
      <div class="main_wrapper">
        <section class="section">
          <h2>プライバシーポリシー</h2>
          <div style="width: 100%; overflowX: auto;">
            <pre align="right">制定日：（タイムゾーンUTC+09:00）の 2022年07月19日<br>訂正日：（タイムゾーンUTC+09:00）の 2024年10月04日</pre>
          </div>
          <hr>
          <p>Pusyuu（以下「私」という）が提供する「プシューサービス」（以下「本サービス」という）をご利用頂く際の取扱いにつき定めるものです。本プライバシーポリシーに同意した上で本サービスをご利用ください。</p>
          <h3>第１条（プライバシーポリシーについて）</h3>
          <ol>
            <li>私が運営する本サービスについて、これを利用する者（以下「利用者」という）は、本プライバシーポリシーに必ず同意頂き、ご利用ください。</li>
            <li>私は、利用者の承諾を得ることなく、本規約の内容を改定することができるものとし、会員はこれを承諾するものとします。本規約が改定された後の本サービスの提供条件は、改定後の本規約の条件によるものとします。</li>
            <li>私は、本規約を改定するときは、その内容について私所定の方法により利用者に通知します。</li>
            <li>前２項に定める本規約の改定の効力は、私が前項により通知を行った時点から生じるものとします。本規約の内容の改定を承諾しない会員については、本サービスを利用できないものとします。</li>
            <li>本プライバシーポリシー等の他、本サービスからリンクされた他のサイトについては、そのサイトのプライバシーポリシーに同意した上でご利用ください。</li>
          </ol>
          <h3>第２条（利用者が投稿できるサービスについて）</h3>
          <ul>
            <li>本サービスの問い合わせフォームやプシューIPS/PusyuuIPS等の利用者が投稿できるサービスはプライバシー配慮のためここに記述してあるものとします。そのサービスは以下の通りです。</li>
            <ol>
              <li><h4>アクセスカウンター</h4>アクセスカウンターではページに利用者等がアクセスした合計の数を計算するものであって、個人を特定するものではありません。※リロードはカウントされません。</li>
              <li><h4>Contact(お問い合わせ)</h4>お問い合わせフォームで利用者等が本サービスについての改善点やお困りごとの相談に使う場所であり、個人を特定するものではありません。お問い合わせいただいた情報はお問合せ以外に使用することはありません。</li>
              <li><h4>プシューIPS/PusyuuIPS</h4>プシューIPS/PusyuuIPSは、感想見たり投稿したり共有できるサービスであり、個人を特定するものではありません。
              <ul>
                <li><h5>誤って投稿してしまった等の削除願いの申し出について</h5>削除申し出の申請は（<a aria-label="お問い合わせフォーム" href="../../../../pusyuusystem/pages/contact" target="_blank">こちら</a>）から可能です。なお”プシューサービスをなくせ”などの削除願いは対応しかねますのでご承知ください。</li>
                <li><h5>本サービスでの情報送信の際のお願い</h5>本サービスは暗号化されていますが万全を期して情報送信の際はご不便おかけしますが、安全な回線を通して送信を行ってください。</li>
                <li><h5>本サービスの投稿データの保存方法について</h5>利用者が投稿したデータは”データベースではなくJSONで保存されます。”ですので個人情報の入力を控えてください。</li>
              </ul>
              </li>
            </ol>
          </ul>
          <h3>第３条（リンクについて）</h3>
          <p>リンク先の利用規約、プライバシーポリシーをお読みください。</p>
          <h3>第４条（本サービスの広告配信への処理や目的について）</h3>
          <ul>
            <li>本サービスでは、サービスの存続を目的に収益を得るためプシューサービスの一部で広告、忍者AdMax以下「広告プロバイダー1」と言う、googleAdsense以下「広告プロバイダー2」というにより広告が表示されます、その為処理と目的を以下のように記述しておきます。</li>
            <ol>
              <li>
                <h4>広告へのCookie(クッキー)使用について</h4>
                <span>本サービスの存続を目的に収益を得るためプシューサービスの一部で広告が表示されます。Cookieは広告プロバイダーの解釈により判断されるため<a aria-label="広告プロバイダー1のクッキーポリシー" href="https://corp.ninja.co.jp/privacy/cookie" target="_blank">広告プロバイダー1のクッキーポリシー</a>そして<a aria-label="広告プロバイダー2のクッキーポリシー" href="https://support.google.com/adsense/answer/7549925">広告プロバイダー2のAdSense が Cookie を使用する仕組み</a>をご覧ください。
                </span>
              </li>
              <li>
                <h4>情報の外部送信先について</h4>
                <span>本サービスでは広告プロバイダー1、広告プロバイダー2を使用しております、広告の表示のためにCookieが使われ広告プロバイダーへ送信されます。先程も申したとおり<a aria-label="広告プロバイダー1のクッキーポリシー" href="https://corp.ninja.co.jp/privacy/cookie" target="_blank">広告プロバイダー1のクッキーポリシー</a>そして<a aria-label="広告プロバイダー2のクッキーポリシー" href="https://support.google.com/adsense/answer/7549925">広告プロバイダー2のAdSense が Cookie を使用する仕組み</a>により解釈により処理されます。ですがJavaScriptまたはCookieとを無効にすることにより、広告への広告IDの保存や送信をブロックができます。設定方法はお使いのブラウザのヘルプから参照いただけます。わからない場合は（<a aria-label="お問い合わせフォーム" href="../../../../pusyuusystem/pages/contact" target="_blank">お問い合わせフォーム</a>）から<strong>返信が必要な為メールアドレスの記述ありの場合のみ</strong>可能です。
                </span>
              </li>
              <li>
                <h4>広告配置箇所</h4>
                <span>広告が表示されるサービスは次のとおりです（プシューゲートウェイ、プシューIPS）
                </span>
              </li>
            </ol>
          </ul>
          <p>
          <h3>第5条（本サービス本体のデータ収集について）</h3>
          <p>利用者が投稿できるサービス以外のプシューサービスでは一切データをいただきません。</p>
          <p>安心して本サービスをご利用ください。</p>
          <strong style="color: #00b1ff; font-size: 20px;">以上</strong>
        </section>
      </div>
    </main>
  </body>
</html>
