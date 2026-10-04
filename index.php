<?php
require_once __DIR__ . '/pusyuusystem/scripts/php_scripts/panic_handler.php';
pusyuuSetPanicProductName('プシューサービス');

require_once __DIR__ . '/pusyuusystem/scripts/php_scripts/rate_limiter.php';
pusyuuCheckRateLimit();

// 他のプロダクトの住所・サーバ間で繋ぐ先(通知API)は、台帳(pusyuu_registry)から受け取ります。
// 無い環境では、プロダクト一覧と通知だけが止まります。
$directry = __DIR__ . '/pusyuusystem/scripts/php_scripts/pusyuu_registry_client.php';
if (file_exists($directry)) {
  require_once($directry);
}

// =====================================================================
// お知らせの通知(プッシュ通知・RSS)との窓口。
//
// 実体は pusyuusystem/apis/pusyuu_push/index.php にあり、ここが持つのは
// 「mainにとっての意味」だけです——誰宛てなのか、何と書くのか。
//
// 【mainでの宛先(userid)は、この端末に振った匿名のID】
// mainにはログインもセッションもありません。通知APIから見ると userid はただの
// 不透明な文字列で、中身の意味は問われない(PUSH_INTEGRATION_SPEC.md 1節が
// 「匿名actorのIDのような各サービス固有の識別子でも構わない」と明記)ため、
// ブラウザごとに振った乱数をそのまま宛先に使います。
//
// 【Cookieは、通知を受け取ると決めた人にだけ発行します】
// ただ見に来ただけの人には何も置きません。IDを作るのは購読ボタンを押した瞬間だけで、
// それまでこのサイトは訪問者を区別しません。ここを「先に配っておく」形に変えると、
// 何もしていない人にまで識別子を配ることになります。
// =====================================================================
class MainPush {
  const SERVICE     = 'main';
  // 通知APIは main(台帳のID)自身の中にありますが、呼ぶのはサーバ間のHTTPです。
  // 繋ぐ先と名乗るホスト名は台帳から受け取ります。
  // 【ここに 127.0.0.1 やドメインを書き戻さないこと】以前は 127.0.0.1 と
  // pusyuuwanko.com を固定していました。構成を変えるたびに探して直すことになります。
  const API_REGISTRY_ID = 'main';
  const API_PATH        = '/pusyuusystem/apis/pusyuu_push/index.php';
  const DEVICE_COOKIE     = 'pusyuu_main_device';
  const DEVICE_COOKIE_TTL = 63072000; // 2年。購読が生きている間は同じ宛先で居たい

  /** accountsと同じ鍵ファイルからHMACで導出する合言葉。 */
  private static function secret(): string {
    static $resolved = false;
    static $secret = '';

    // 1リクエストにつき1回だけ探します。2回目以降は前回の答えをそのまま返します。
    // (見つからなかった場合も「見つからなかった」という答えを覚えます。
    //  覚えないと、ページ内で通知APIを呼ぶたびに最大8階層を探し直します)
    if ($resolved) {
      // 前回の答えをそのまま使います。
    } else {
      $resolved = true;
      $dir = __DIR__;

      for ($i = 0; $i <= 8; $i++) {
        $candidate = $dir . '/.pusyuuHiddenFiles/pips_account_key.php';
        $parent    = dirname($dir);

        if (file_exists($candidate)) {
          $encoded = require $candidate;
          $key = is_string($encoded) ? base64_decode($encoded, true) : false;
          if ($key !== false && strlen($key) === 32) {
            $secret = hash_hmac('sha256', 'pusyuu_push_api', $key);
          }
          break;
        } else if ($parent === $dir) {
          break; // これ以上は上れない
        } else {
          $dir = $parent;
        }
      }
    }

    return $secret;
  }

  /** 通知APIを1回叩く。届かなくても例外は投げず、失敗を表す配列を返す。 */
  public static function api(string $action, array $params = []): array {
    $params['api_secret'] = self::secret();
    $result = [];

    if ($params['api_secret'] === '') {
      error_log('[main push] 合言葉を導出できません(.pusyuuHiddenFiles/pips_account_key.php が見つからない)。');
      $result = ['ok' => false, 'error' => 'no_secret'];
    } else {
      $body = http_build_query($params);

      // 通信は台帳を読む係(PusyuuRegistryClient::call)の1本だけです。宛先・Host・証明書の
      // 確かめ方はあちらが台帳から決めます。台帳の係が無い環境では届かない扱いにします。
      if (class_exists('PusyuuRegistryClient')) {
        $response = PusyuuRegistryClient::call(self::API_REGISTRY_ID, 'POST', self::API_PATH . '?api=' . urlencode($action), ['Content-Type: application/x-www-form-urlencoded'], $body, 5);
        $raw = ($response['problem'] === '') ? $response['body'] : false;
      } else {
        $raw = false;
      }

      $decoded = ($raw === false || $raw === null || $raw === '') ? null : json_decode((string)$raw, true);

      if ($raw === false || $raw === null || $raw === '') {
        $result = ['ok' => false, 'error' => 'api_unreachable'];
      } else if (!is_array($decoded)) {
        $result = ['ok' => false, 'error' => 'api_broken_response'];
      } else {
        $result = $decoded;
      }
    }

    return $result;
  }

  /**
   * この端末の宛先ID。$create が true の時だけ、無ければ発行してCookieに置きます。
   * 読むだけの場面で発行しないこと(理由はクラス冒頭のコメント)。
   */
  public static function deviceId(bool $create = false): ?string {
    $existing = $_COOKIE[self::DEVICE_COOKIE] ?? null;
    $result   = null;

    if (is_string($existing) && preg_match('/\A[0-9a-f]{32}\z/', $existing)) {
      $result = $existing;
    } else if (!$create) {
      // 読むだけの場面。無いなら無いと答えます(ここで発行しないこと)。
      $result = null;
    } else {
      $id     = bin2hex(random_bytes(16));
      $host   = strtolower((string)strtok((string)($_SERVER['HTTP_HOST'] ?? ''), ':'));
      $secure = !($host === 'localhost' || $host === '127.0.0.1' || $host === '::1');
      setcookie(self::DEVICE_COOKIE, $id, [
        'expires'  => time() + self::DEVICE_COOKIE_TTL,
        'path'     => '/',
        'secure'   => $secure,
        'httponly' => true,   // JSからは読む必要がありません
        'samesite' => 'Lax',
      ]);
      // 同じリクエストの中でも使えるようにしておきます($_COOKIEは次回からしか入りません)。
      $_COOKIE[self::DEVICE_COOKIE] = $id;
      $result = $id;
    }

    return $result;
  }

  /**
   * 他所のサイトから投げ込まれたPOSTを弾く。
   *
   * mainにはセッションもトークンもないので、Originヘッダで見ます。Originを
   * 送らない相手(古いブラウザ、Service Workerからの再購読)もあるため、
   * 「食い違っている時だけ拒む」という判断にしてあります。ここで守りたいのは
   * 「よそのサイトのフォームから勝手に登録させられること」で、Originが無い相手を
   * 一律に締め出すと、正規のService Workerまで通れなくなります。
   */
  public static function sameOriginPost(): bool {
    $origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $host   = (string)($_SERVER['HTTP_HOST'] ?? '');
    $result = false;

    if ($origin === '') {
      // Originを名乗らない相手(古いブラウザ、Service Workerからの再購読)は通します。
      $result = true;
    } else if ($origin === 'https://' . $host || $origin === 'http://' . $host) {
      $result = true;
    } else {
      // 名乗ったうえで食い違っている。よそのサイトからの投げ込みとみなします。
      $result = false;
    }

    return $result;
  }

  /** お知らせが1件追加されたことを、購読している人へ知らせる。 */
  public static function announceNotification(string $info, bool $emergency): void {
    $body = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($info)) ?? ''), 0, 120);
    $res = self::api('broadcast', [
      'service' => self::SERVICE,
      'title'   => $emergency ? '【緊急】プシューサービスからのお知らせ' : 'プシューサービスからのお知らせ',
      'body'    => $body,
      // 通知は要求と無関係に誰かへ届くので、今の入口ではなく公開の顔の住所を使います。
      'url'     => class_exists('PusyuuRegistryClient') ? (string)PusyuuRegistryClient::publicUrl('main', '/#notifications') : '',
      // 同じtagにしておくと、続けて追加した時に通知が積み上がらず1件にまとまります。
      'tag'     => 'main-notification',
    ]);
    if (empty($res['ok'])) {
      error_log('[main push] お知らせの通知を送れませんでした。error=' . var_export($res['error'] ?? null, true));
    }
  }

  /**
   * push-sw.js の push イベントから叩かれ、表示する中身をJSONで返す。
   * inboxは明示的にackするまで消えないため、取得したら必ずその場でackします。
   */
  public static function inboxJson(string $deviceId): string {
    $res = self::api('inbox', ['service' => self::SERVICE, 'userid' => $deviceId]);
    $items = !empty($res['ok']) ? ($res['items'] ?? []) : [];
    if (!empty($items)) {
      $ids = array_values(array_filter(array_map(
        static fn($it) => is_string($it['id'] ?? null) ? $it['id'] : null, $items
      )));
      self::api('ack', ['service' => self::SERVICE, 'userid' => $deviceId, 'ids' => $ids]);
    }
    return (string)json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
  }
}

// =====================================================================
// 通知まわりの特殊な受け口。
//
// アクセス記録(下のlog.json)や利用状況の記録より手前で終わらせます。これらは
// 人がページを見に来たわけではないので、訪問として数えると記録が濁ります。
// =====================================================================

// Service Workerの本体。pusyuusystem/scripts/配下に置いたまま、
// Service-Worker-Allowed: / をPHPから付けることで、ルート直下へのファイル配置にも
// .htaccess(mod_headers)にも頼らず scope: './' を成立させます。
if (isset($_GET['push_sw'])) {
  header('Content-Type: text/javascript; charset=UTF-8');
  // 【「/」にしないこと】main がパスの下に置かれると、範囲がホスト全体になり他のサービスと重なります。
  // 相対の ./ はこのスクリプトの住所(= main のトップ)から解決されます。
  header('Service-Worker-Allowed: ./');
  readfile(__DIR__ . '/pusyuusystem/scripts/push-sw.js');
  exit;
}

// VAPID公開鍵。購読ボタンを押した時にだけ取りに来ます。
//
// 【なぜページに埋め込まないか】埋め込むと、通知を使う気の無い人の表示のたびに
// 通知APIへの往復が1回増えます。押した人だけが払えばよい費用です。
if (isset($_GET['push_key'])) {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store');
  $keyRes = MainPush::api('vapid_public_key');
  echo json_encode(['key' => !empty($keyRes['ok']) ? (string)($keyRes['public_key'] ?? '') : '']);
  exit;
}

// SWのpushイベントから叩かれる、表示内容の取得口。
if (isset($_GET['push_inbox'])) {
  header('Content-Type: application/json; charset=utf-8');
  $mainDevice = MainPush::deviceId(); // ここでは発行しない(購読済みの端末しか来ない)
  if ($mainDevice === null) {
    // 購読していない端末。エラーにするとSW側のcatchに落ちて原因が見えなくなるので、
    // 空の配列を返すのが正しい応答です。
    echo json_encode(['items' => []]);
    exit;
  }
  echo MainPush::inboxJson($mainDevice);
  exit;
}

// 購読の登録・解除。JSからfetch()で呼ばれます。
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    && (isset($_POST['push_subscribe']) || isset($_POST['push_unsubscribe']))) {
  header('Content-Type: application/json; charset=utf-8');
  if (!MainPush::sameOriginPost()) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
  }
  if (isset($_POST['push_subscribe'])) {
    // ここが、このサイトで唯一Cookieを発行する場所です。
    $mainDevice = MainPush::deviceId(true);
    $res = MainPush::api('subscribe', [
      'service'      => MainPush::SERVICE,
      'userid'       => $mainDevice,
      'subscription' => (string)($_POST['subscription'] ?? ''),
      'ua'           => mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
    ]);
  } else {
    $mainDevice = MainPush::deviceId();
    $res = $mainDevice === null
      ? ['ok' => true] // もともと登録が無いので、解除は済んでいるのと同じ
      : MainPush::api('unsubscribe', [
          'service'  => MainPush::SERVICE,
          'userid'   => $mainDevice,
          'endpoint' => (string)($_POST['endpoint'] ?? ''),
        ]);
  }
  echo json_encode(['ok' => !empty($res['ok'])]);
  exit;
}

// RSS設定画面へ送り出すだけの分岐。押されたその場でチケットを発行して転送します
// (URLに短命の資格情報が載るため、ページのHTMLには埋め込みません)。
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['notify_settings'])) {
  if (!MainPush::sameOriginPost()) {
    http_response_code(403);
    exit;
  }
  // RSSだけで受け取りたい人もいるので、ここでもIDを発行します。
  $mainDevice = MainPush::deviceId(true);
  $ticketRes  = MainPush::api('settings_ticket', [
    'service'    => MainPush::SERVICE,
    'userid'     => $mainDevice,
    // 戻り先は、今この画面を見ている本人が入ってきた入口に合わせます(台帳の顔から)。
    'return_url' => class_exists('PusyuuRegistryClient') ? (string)PusyuuRegistryClient::url('main', '/') : '',
  ]);
  if (!empty($ticketRes['ok']) && !empty($ticketRes['url'])) {
    header('Location: ' . (string)$ticketRes['url']);
  } else {
    // 何も起きないと押した人には区別がつかないので、必ず理由を残して戻します。
    error_log('[main push] 設定画面のチケットを発行できませんでした。');
    header('Location: ./?notify_error=1#notifications');
  }
  exit;
}

require_once __DIR__ . '/pusyuusystem/scripts/php_scripts/usage_tracker.php';
pusyuuRecordProductUsage('main');

// 保存ファイルのパス
$logfile = __DIR__ . '/log/log.json';

// 捕まえたいパラメータ
$keys = ['ref', 'utm_source', 'utm_medium', 'utm_campaign', 'req'];

// 初期配列
$data = [];

// JSからの判定
$P_InternetCheck = $_GET["P-internetChecker"] ?? null;

// JS専用ヘッダーによる判定
if (isset($P_InternetCheck) && ($_SERVER["HTTP_X_APP_SOURCE"] ?? "") === "P-interntChecker") {
  return;
}

// 通常のアクセスを記録
$data["pusyuuParamStatus"] = $P_InternetCheck;

// GETパラメータから取得
foreach ($keys as $key) {
  if (isset($_GET[$key]) && $_GET[$key] !== '') {
      $data[$key] = $_GET[$key];
  }
}

// リファラも取得
if (!empty($_SERVER['HTTP_REFERER'])) {
  $data['referrer'] = $_SERVER['HTTP_REFERER'];
}

// タイムスタンプを付与
$data['timestamp'] = date('Y-m-d H:i:s');

// 既存のJSONを読み込み
$logs = [];
if (file_exists($logfile)) {
  $json = file_get_contents($logfile);
  $logs = json_decode($json, true);
  if (!is_array($logs)) $logs = [];
}

// 新しいデータを追加
$logs[] = $data;

// JSONとして上書き保存（配列を維持）
file_put_contents($logfile, json_encode($logs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

/*
// 確認用レスポンス
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'count' => count($logs)], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
*/

$notificationPerPage = 5;
$getNotification = "通知は今はないようです。";
$getNotificationPagination = "";
$notificationTimes = [];
$notification = file_get_contents("./pusyuusystem/documents/notification.json");
if ($notification !== null) {
  $getNotification = "";
  $notificationData = json_decode($notification, true);
  if (isset($notificationData)) {
    // 時間でソートする関数
    usort($notificationData, function($a, $b) {
      return strtotime($b["time"]) - strtotime($a["time"]); // 新しい順にソート
    });

    // 「今日の新着」件数はページに関係なく全件から数える
    foreach ($notificationData as $notificationItem) {
      $notificationTimes[] = isset($notificationItem["time"]) ? $notificationItem["time"] : "";
    }

    // ページ番号の決定（不正・範囲外な値は丸め込む）
    $notificationTotalCount = count($notificationData);
    $notificationTotalPages = (int)max(1, ceil($notificationTotalCount / $notificationPerPage));
    $notificationRequestedPage = $_GET["notification_page"] ?? "1";
    if (is_string($notificationRequestedPage) && ctype_digit($notificationRequestedPage) && (int)$notificationRequestedPage > $notificationTotalPages) {
      $notificationCurrentPage = $notificationTotalPages;
    } else if (is_string($notificationRequestedPage) && ctype_digit($notificationRequestedPage) && (int)$notificationRequestedPage >= 1) {
      $notificationCurrentPage = (int)$notificationRequestedPage;
    } else {
      $notificationCurrentPage = 1;
    }

    $notificationPageItems = array_slice($notificationData, ($notificationCurrentPage - 1) * $notificationPerPage, $notificationPerPage);
    foreach ($notificationPageItems as $notification) {
      $notification["info"] = str_replace("\n", "<br>\n", $notification["info"]);
      $emangency = "";
      if (isset($notification["emergency"])) {
        switch($notification["emergency"]) {
          case "true":
            $emangency = '<i style="color: #ff0000;"><span style="color: #fff;">緊急度</span>緊急</i>';
          break;
          default:
            $emangency = '<i style="color: #00ff00;"><span style="color: #fff;">緊急度</span>通常</i>';
          break;
        }
      }
      $getNotification .= '
        <li>
          <time>' . $notification["time"] . '</time>
          ' . $emangency . '
          <p><span>通知内容</span>' . $notification["info"] . '</p>
        </li>
      ';
    }

    if ($notificationTotalPages > 1) {
      if ($notificationCurrentPage <= 1) {
        $notificationPrevLink = '<span>前へ</span>';
      } else {
        $notificationPrevLink = '<a href="?notification_page=' . ($notificationCurrentPage - 1) . '#notifications">前へ</a>';
      }
      if ($notificationCurrentPage >= $notificationTotalPages) {
        $notificationNextLink = '<span>次へ</span>';
      } else {
        $notificationNextLink = '<a href="?notification_page=' . ($notificationCurrentPage + 1) . '#notifications">次へ</a>';
      }

      $notificationPageLinks = "";
      for ($notificationPageNum = 1; $notificationPageNum <= $notificationTotalPages; $notificationPageNum++) {
        if ($notificationPageNum === $notificationCurrentPage) {
          $notificationPageLinks .= '<span>' . $notificationPageNum . '</span>';
        } else {
          $notificationPageLinks .= '<a href="?notification_page=' . $notificationPageNum . '#notifications">' . $notificationPageNum . '</a>';
        }
      }

      $getNotificationPagination = '
        <nav>
          ' . $notificationPrevLink . '
          ' . $notificationPageLinks . '
          ' . $notificationNextLink . '
        </nav>
      ';
    }
  }
}

function nowNotificationInfo($switchLogic = "") {
  global $notificationTimes;
  $nowNotification = $result = null;
  if (isset($notificationTimes) && is_array($notificationTimes)) {
    foreach ($notificationTimes as $notificationTime) {
      $notificationDate = substr($notificationTime, 0, 10);
      if ($notificationDate  == date('Y-m-d')) {
        $nowNotification += 1;
      }
    }
  }

  //print_r(!($nowNotification === 0) . "と" . !($nowNotification === null) . "と" . !($nowNotification === 0 || $nowNotification === null));

  if (!($nowNotification === 0 || $nowNotification === null)) {// $nowNotificationの値を双方tureか否かを検証する式にしたため()で反転させたつもりが演算子がひっかくり変える事は想定になく&&は||に反転しnowNotificationの結果の比較が&&だと思ってたものが実は||になっていたことでいつまでもHTMLが消えないという状態だった、勉強になったという記録。
    if ($switchLogic === "number") {
      $result = $nowNotification;
    } else if ($switchLogic === "normal") {
      $result = '<div>' . $nowNotification . '件</div>';
    } else if ($switchLogic === "newarrival") {
      $result = '<p style="color: #ffaa00">新着通知が' . $nowNotification . '件あります。</p>';
    } else {
      $result = '<span style="color: #fff; background: rgba(255,0,0,0.9); padding: 5px; margin: 0px 10px 0px; z-index: 15; border-radius: 50px;" id="notificationNumInfo">' . $nowNotification . '件</span>';
    }
  } else {
    $result = "";
  }

  return $result;
}

$defaultTitle = "プシューサービス - HOME（プシューゲートウェイ）";
$title = empty(nowNotificationInfo("number")) ? $defaultTitle : "（" . nowNotificationInfo("number") . "）" . $defaultTitle;
$description = "プシューサービスでは、メモ帳、PusyuuIPS（SNS、掲示板的なサービス）、等がありそれらはすべてWebアプリなので、どの端末（スマートフォン、パソコン、テレビ等）でもブラウザ（Safari、Chrome、FileFox、Silk、等）があれば様々な環境で動かせます。他にもプシューコントやプシューサービスの最新情報を提供を行っていますので、バグや故障の際も安心してご利用いただけます。";

// プロダクト一覧は台帳(pusyuu_registry)から受け取り、自分(台帳のID main)は外します。
// 【自分を URL で見分けないこと】以前は今のホスト名と一覧のURLを突き合わせていたため、
// 21emon.wjg.jp など別の入口から来ると自分が一覧に混ざりました。
// 画面に出す住所のうち、台帳(pusyuu_registry)から決めるもの。
//   - 自分(main)のファイルやページへのリンクは、このページから見た相対パス(./)です。
//     main はホストの一番上とは限らず、パスの下に置かれることもあるので、
//     「/」から始めないこと。どの顔(pusyuuwanko.com / 21emon.wjg.jp / pusyuu.com)から
//     入っても、そのまま正しく開きます。
//   - OGP画像や相互リンク用に配る住所のように、よそ(SNS・他のサイト)へ渡るものは
//     公開の顔の住所にします。台帳が読めないときは「/」から始まる形に倒します。
//   - 他のプロダクト(メイキィ等)へのリンクは、今の入口に合った台帳の顔にします。
$mainPublicBase = (class_exists('PusyuuRegistryClient') ? PusyuuRegistryClient::publicUrl('main') : null) ?? '';
$mainPublicBaseHtml = htmlspecialchars($mainPublicBase, ENT_QUOTES, 'UTF-8');
$mainPublicAddressHtml = htmlspecialchars(($mainPublicBase === '') ? '/' : $mainPublicBase, ENT_QUOTES, 'UTF-8');
$meikieeUrlHtml = htmlspecialchars((class_exists('PusyuuRegistryClient') ? PusyuuRegistryClient::url('p-meikiee') : null) ?? '', ENT_QUOTES, 'UTF-8');
// main.js(年明けの案内)がPIPSへ飛ばす先。JSは台帳を読めないので、script タグの data 属性で渡します。
$pipsUrlHtml = htmlspecialchars((class_exists('PusyuuRegistryClient') ? PusyuuRegistryClient::url('pips') : null) ?? '', ENT_QUOTES, 'UTF-8');

$productLinks = class_exists('PusyuuRegistryClient') ? PusyuuRegistryClient::links('main') : null;
if ($productLinks === null) {
  $setProductData = '<p style="color: #ff0000;">プロダクト一覧の取得に問題が発生し表示できませんでした。おそらく開発者の設定ミスです。</p>';
} else if ($productLinks === []) {
  $setProductData = "現在、表示できるプロダクトは無いようです。";
} else {
  $setProductData = "";
  $fallbackImage = './pusyuusystem/images/avater.jpg';
  foreach ($productLinks as $product) {
    $isImage = ($product["image"] !== '') ? $product["image"] : $fallbackImage;
    $setProductData .= '
        <div class="product">
          <a href="' . htmlspecialchars($product['url'], ENT_QUOTES, 'UTF-8') . '">
            <img src="' . htmlspecialchars($isImage, ENT_QUOTES, 'UTF-8') . '" />
            <div>
              <h3>' . $product['title'] . '</h3>
              <p>' . $product["description"] . '</p>
            </div>
          </a>
        </div>
      ';
  }
}

$getSougoLink = $getLikeLink = '
  <div class="sougo">
    <h3>通知</h3>
    <p>プシューが個人的に好きなリンクは登録されていないようです。</p>
  </div>
';
$sougoLinks = file_get_contents("./pusyuusystem/documents/sougolinks.json");
if (isset($sougoLinks)) {
  $sougoData = json_decode($sougoLinks, true);
  if (json_last_error() === JSON_ERROR_NONE) {
    if (!empty($sougoData["sougos"])) {
      $getSougoLink = "";
      foreach($sougoData["sougos"] as $sougoLink) {
        if ($sougoLink["isLostSougo"] == false) {
          $image_or_title = empty($sougoLink["image"]) ? '<h3>' . $sougoLink["title"] . '</h3>' : '<img src="' . $sougoLink["image"] . '" alt="' . $sougoLink["title"] . '" />';
          $getSougoLink .= '
            <div class="sougo">
              <a href="' . $sougoLink["link"] . '" target="_blank">
                ' . $image_or_title . '
                <p>' . $sougoLink["description"] . '</p>
              </a>
            </div>
          ';
        } else if ($sougoLink["isLostSougo"] == true) {
          $image_or_title = empty($sougoLink["image"]) ? '<h3>' . $sougoLink["title"] . '</h3>' : '<img src="' . $sougoLink["image"] . '" alt="' . $sougoLink["title"] . '" />';
          $getSougoLink .= '
            <!--
              <div class="sougo">
                <a href="' . $sougoLink["link"] . '" target="_blank">
                  ' . $image_or_title . '
                  <p>' . $sougoLink["description"] . '</p>
                </a>
              </div>
            -->
          ';
        }
      }
    }
    if (!empty($sougoData["likes"])) {
      $getLikeLink = "";
      foreach ($sougoData["likes"] as $likeLink) {
        $image_or_title = empty($likeLink["image"]) ? '<h3>' . $likeLink["title"] . '</h3>' : '<img src="' . $likeLink["image"] . '" alt="' . $likeLink["title"] . '" /> ';
        $getLikeLink .= '
          <div class="sougo">
            <a href="' . $likeLink["link"] . '" target="_blank">
              ' . $image_or_title . '
              <p>' . $likeLink["description"] . '</p>
            </a>
          </div>
        ';
      }
    }
  } else {
    $getSougoLink = $getLikeLink = '
      <style>
        #sougoLinkError > h3 {
          color: #fff000;
        }
        #sougoLinkError > p {
          color: #ff0000;
        }
        #sougoLinkError > p > span {
          color: #00aaff;
        }
        #sougoLinkError > p > a {
          color: #00ff00;
          transition: all 0.3s;
        }
        #sougoLinkError > p > a:hover {
          color: #00aaff;
        }
      </style>
      <div class="sougo" id="sougoLinkError">
        <h3>エラー</h3>
        <p>相互リンクと個人的に好きなリンク集を表示する際に問題が発生し表示することができませんでした。この問題は開発者の設定ミスの可能性があります、そしてあなたにはなにかする手段がありませんエラー情報とともに開発者にこのことをいち早くお伝えください。エラー情報は次のとおりです：<span>' . json_last_error_msg() . '</span></p>
        <p>お問い合わせはこちらから：<a href="./pusyuusystem/pages/contact" target="_blank">開発者に問い合わせる</a></p>
      </div>
    ';
  }
}

function loadingDisp() {
  $manuallyClosed = (isset($_POST["nojs"]) && $_POST["nojs"] == "true");
  $result = "";

  if ($manuallyClosed) {
    $result = '';
  } else {
    $result = '
      <div id="loading">
        <div>
          <div>
            <img src="./pusyuusystem/images/avater.jpg" alt="avater" oncontextmenu="return false;" onselectstart="return false;" onmousedown="return false;"/>
            <p>Now Loading</p>
            <div>
              <form method="post">
                <p>あなたのデバイスではJSが有効ではありません、手動でローリング画面を解除する必要があります。</p>
                <button type="submit" name="nojs" value="true">ローリング画面手動解除</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    ';
  }

  return $result;
}

/**
 * 実績(achievements.json)1件の行き先。"registry" に台帳(pusyuu_registry)のIDがあれば、
 * url をそのプロダクトの中のパスとして、今の入口に合う顔の住所を付けます。
 * 【achievements.json にドメインを書き戻さないこと】台帳が読めないときは url のまま
 * (=このページのホストの / から)に倒れます。
 */
function pusyuuZissekiUrl(array $zisseki): string {
  $registryId = (string)($zisseki["registry"] ?? "");
  $base = ($registryId !== "" && class_exists('PusyuuRegistryClient')) ? PusyuuRegistryClient::url($registryId) : null;
  return ($base === null) ? (string)$zisseki["url"] : $base . (string)$zisseki["url"];
}

function dispZisseki() {
  // 実績は main 自身のデータなので、ファイルとして直接読みます。
  // 【http://localhost/... で読まないこと】以前はそうしていたため、localhost が
  // どのバーチャルホストに当たるかという構成しだいで、読めたり読めなかったりしました。
  $zissekiFile = @file_get_contents(__DIR__ . "/pusyuusystem/documents/achievements.json");
  $zissekiData = ($zissekiFile === false) ? null : json_decode($zissekiFile, true);
  $result      = "";

  if ($zissekiFile === false) {
    $result = '<p style="color: #ff0000;">エラー：実績データの取得中に問題が発生したため表示できませんでした。</p>';
  } else if (!isset($zissekiData)) {
    $result = '<p style="color: #ff0000;">エラー：実績データの解析中に問題が発生したため表示できませんでした。</p>';
  } else {
    foreach ($zissekiData as $zisseki) {
      $result .= ' 
        <article>
          <a href="' . htmlspecialchars(pusyuuZissekiUrl($zisseki), ENT_QUOTES, 'UTF-8') . '" target="_blank">
            <img src="' . htmlspecialchars('./' . (string)$zisseki["image"], ENT_QUOTES, 'UTF-8') . '" alt="' . $zisseki["title"] . '画像" />
            <div>
              <h3>' . $zisseki["title"] . '</h3>
              <p>' . $zisseki["description"] . '</p>
            </div> 
          </a>
        </article>
      ';
    }
  }

  return $result;
}

require_once("./pusyuusystem/scripts/php_scripts/notation.php");
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=yes, maximum-scale=6.0, minimum-scale=1.0" />
    <meta name="description" content="<?php echo($description); ?>" />
    <meta name="copyright" content="© 2020-2025 created by PusyuuWanko/" />
    <meta name="keywords" content="プシューサービス,PusyuuServuce,pusyuu service,プシューページ,PusyuuPage,プシュー,Pusyuu,pusyuu,プシューゲートウェイ,pusyuu gatewey" />
    <meta property="og:title" content="<?php echo($title); ?>" />
    <meta property="og:site_name" content="<?php echo($title); ?>" />
    <meta property="og:description" content="<?php echo($description); ?>" />
    <meta property="og:image" content="<?= $mainPublicBaseHtml ?>/pusyuusystem/images/NanimoyakunitatanaiPage.png" />
    <meta property="og:type" content="application" />
    <link rel="manifest" href="./pusyuusystem/documents/manifest.json" />
    <link rel="shortcut icon" href="./pusyuusystem/images/favicon.ico" />
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="./pusyuusystem/styles/main.css" />
    <script src="./pusyuusystem/scripts/anime.min.js"></script>
    <script src="./pusyuusystem/scripts/main.js" data-pips-url="<?= $pipsUrlHtml ?>"></script>
    <script src="./pusyuusystem/scripts/scroll-enhance.js"></script>
    <script src="./pusyuusystem/scripts/internet_checker.js"></script>
    <script src="./pusyuusystem/scripts/touch-device-tool-tip.js"></script>
    <!-- privacy_notice.js の script タグより前に置く -->
    <script>
      window.PusyuuPrivacyNoticeConfig = {
        // ---- 機能1(初回アクセス時の自動お知らせ)のカスタマイズ ----
        title: "プライバシーに関するお知らせ",
        // bodyHtml: "<p>...</p>", // 本文を丸ごと差し替えたい場合のみ指定(指定すると下のserviceNameは使われなくなる)
        serviceName: "プシューサービス", // 省略時は特定サービス名を出さず一般論の文章になる
        fundingNote:
          '広告についての方針は<a href="./rules" ' +
          'target="_blank">ルールズ</a>をご覧ください。',
        closeButtonText: "同意する",
        hostsIntroText: "このページ読み込み時に検出した外部の通信先：",
        hostsNoneText: "このページ読み込み時には、外部への通信は検出されませんでした。",
        checkDelay: 1500,   // load後、検出確定までの待ち時間(ms)。既定1500
        maxHostDisplay: 10, // 検出したホスト名を一覧表示する最大件数。既定10
        storageKey: "pusyuu_privacy_notice_dismissed", // 表示済みフラグの保存キー名

        // ---- "js-dependency"プリセット(機能2)のカスタマイズ ----
        jsDependency: {
          level: "low", // このページの実情に合わせて "none" | "low" | "medium" | "high"
          brokenFeatures: [
            "ハンバーガーメニューの閉じる機能",
            "ページ読み込み時の演出の閉じる機能",
            "ページ内の一部アニメーション"
          ],
          fallbackNote:
            '詳しい方針は<a href="./rules/javascript/" ' +
            'target="_blank">JavaScriptの取り扱いに関する方針</a>をご覧ください。'
        },

        triggerCloseButtonText: "閉じる" // 機能2のモーダルの閉じるボタン文言
      };
    </script>
    <script src="./pusyuusystem/scripts/privacy_notice.js"></script>
    <script src="./pusyuusystem/scripts/request-indicator.js" data-include-async="true"></script>
    <?php
      // プッシュ通知の2本(push-subscribe.js / main-push.js)は、ここではなく
      // </body>の直前に置いてあります。理由はそちらのコメントに書いてあります。
    ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-4960471482152873" crossorigin="anonymous"></script>
    <!--
        *------------------------------------
        |  ThisPageVersion: 1.4.1         |
        |  © ISAMI ABE                    |
        |  LastUpdate: 2026-09-15         |
        |  License: MIT License           |
        |  PussyuuGateway                 |
      ------------------------------------*
    -->
  </head>
  <body>
    <?php echo loadingDisp(); ?>
    <show>
      <div class="comp2">
        <iframe title="アクセスカウンター" src="./pusyuusystem/pages/counter/?name=main"></iframe>
      </div>
      <div class="comp1">
        <strong>Welcom to <span>Pusyuu service</span></strong>
        <p>Pusyuu quarty world.</p>
      </div>
      <!--<img src="./pusyuusystem/images/Show-img.jpg" alt="show back image" oncontextmenu="return false;" onselectstart="return false;" onmousedown="return false;" />-->
      <video class="show-video-desktop" data-variant="desktop" autoplay loop muted playsinline><source src="./pusyuusystem/videos/show-desktop.mp4" type="video/mp4"></source></video>
      <video class="show-video-mobile" data-variant="mobile" autoplay loop muted playsinline><source src="./pusyuusystem/videos/show-mobile.mp4" type="video/mp4"></source></video>
      <div id="event" style="color: #ff0000">申し訳なく存じますがこの領域はJavaScriptが必要な領域です。</div>
    </show>
    <header>
      <h1>P-s</h1>
      <input type="checkbox" id="menu-btn-check">
      <label for="menu-btn-check" class="menu-btn"><span></span><span></span><?php echo nowNotificationInfo("normal"); ?></label>
      <div class="menu-content">
        <div>
          <nav>
            <ul>
              <li><a href="#home">ホーム🏠</a></li>
              <li><a href="#products">製品💻</a></li>
              <li><a href="<?= $meikieeUrlHtml ?>" target="_blank">プシューメイキィ🐸</a></li>
              <li><a href="#notifications">お知らせ📢<?php echo nowNotificationInfo(); ?></a></li>
              <li><a href="#sougoLink">相互リンク🤝</a></li>
              <li><a href="#about">私について😊</a>
              <li><a href="#">一番上へ移動⇧</a></li>
            </ul>
          </nav>
        </div>
      </div>
    </header>
    <div class="modal" id="notifications">
      <div>
        <a href="#mc"></a>
        <div>
          <span>Notifications <?php echo nowNotificationInfo("newarrival"); ?></span>
          <div>
            <details>
            <!--
                // =================================================================
                // お知らせを受け取る手段。
                //
                // 【受け取り方は2つあり、どちらを使うかは見た人が選びます】
                // プッシュ通知(この端末のブラウザへ直接届く)と、RSS(お使いの
                // RSSリーダーで受け取る)です。両方使っても構いません。
                //
                // 【JSが要る物と要らない物で描き分けています】プッシュ通知は購読も
                // 許可もJS前提なので、見出し「🔔 プッシュ通知で受け取る」を含めて
                // #main-push-settings の中身は main-push.js が丸ごと組み立てます
                // (JSが無い環境ではそこは見出しごと現れません)。RSSは発行も表示も
                // ただのフォーム送信で成立するので、PHPが常に描画します。
                // JSで作れるものをJSで隠さない、という分け方です。
                //
                // 【押すまで何も記録しません】このサイトにはログインがないため、
                // 宛先は端末ごとの匿名IDになります。そのIDを発行するのは下のボタンを
                // 押した時だけで、見に来ただけの人には何も置きません。
                // =================================================================
              -->
              <summary>お知らせを受け取る</summary>
              <h3>お知らせを受け取る</h3>
              <p>新しいお知らせが追加された時の受け取り方を選べます。プッシュ通知とRSS、どちらか片方でも両方でも構いません。登録しなければ何も届きません。</p>
  <?php if (isset($_GET["notify_error"])): ?>
              <p style="color: #ff5555;">通知の設定を開けませんでした。しばらく待ってからもう一度お試しください。</p>
  <?php endif; ?>
              <div id="main-push-settings"></div>
              <h4>📰 RSSで受け取る</h4>
              <p>プッシュ通知に対応していない環境(ホーム画面に追加していないiPhoneや、アプリ内ブラウザなど)でも、お使いのRSSリーダーでお知らせを受け取れます。</p>
              <form method="post" action="./">
                <input type="hidden" name="notify_settings" value="1" />
                <button type="submit">RSSの設定を開く</button>
              </form>
            </details>
            <ul>
              <?php echo $getNotification; ?>
            </ul>
            <?php echo $getNotificationPagination; ?>
          </div>
        </div>
      </div>
    </div>
    <main>
      <section id="home">
        <h2>ようこそ！プシューサービスのホームへ</h2>
        <p>ホームではプシューサービスの大まかな概要などが見れます。</p>
        <h3>プシューサービスについて</h3>
        <p>プシューサービスでは主に世の中に確かにあるけどない機能を提供しています、例えば、プシューメモこれはブラウザ（ChromeやSafari等のことを言う）でメモを書けるのですが、この世の中にあるメモアプリはハッキリ言って最悪だと私は思います、例えばアカウントがないとデータの同期ができない、ブラウザがクラッシュ（急にアプリが落ちたり等を言う）したときにデータが飛ぶ長文だと悲しくなりますよね、ファイルとして保存できない等、私の開発したメモアプリそれらを解決しています、しかし殆どのメモアプリでこれを解決してる物をあまり見ません、私の開発してるメモアプリはこうした些細なことかもしれないけど大切な点を重要視し提供しています。</p>

        <androidNotification style="display: block; background: #aa4400; color: #ffffff; padding: 10px; border-radius: 5px;">
        <h3>Androidが危機的状況です（あなたの端末ではありません、全世界のデバイスがです）</h3>
        <p>現在androidは以前の様なapkによる外部アプリのインストールを規制しそのアプリを作る開発者は常に監視され柔軟なアプリケーションの開発を行いにくくなろうとしています、自由は規制と隣り合わせで匿名を広げれば問題は発生します、規制することはもっともだと思います、しかし問題はそれで終わりません。だ他規制するだけだと失うものがあります、そこを今一度他人事と考えず考え直してみてください。</p>
        <p>それどころか今後メモリの制限を設け、ユーザに負担を強いろうとしています、あなたがゲームをしていればあなたに決めさせず突然強制終了することになります</p>
        <i>私はこれらの問題を重く受け止めています、しかし一個人でできることは少ない物です、もしgoogleにこのことを抗議しようとも彼らは動じないでしょう、しかしそれでも私はこの変更を不満に思っているという事だけは言っておきます。</i>
        <i>皆いうんです、Googleがそうするにはセキュリティが理由だと、しかし敵は身内にありという言葉のように、本当は安全だと疑いもしない場所に危険って潜んでいるものです、今のアップルはサイドローディング不可能な典型的な囲い込みでありウィルスなどの侵入リスクは低いかもしれません、しかしよく考えてみてください、囲い込んで誰の目にもつかない場所で安全にデータが保管される保証ってどこにあると思いますか？</i>
        <div id="my-banner"></div>
        <script src="https://keepandroidopen.org/banner.js?lang=fr&size=mini&id=my-banner"></script>
        </androidNotification>
        <h4>現在の取り組み</h4>
        <p>現在プシューサービスでは新たな取り組みとして、太陽光エネルギーのみでの運営に挑戦しています、今現代においてとてもベストプラクティスなサービス提供になるように頑張りますので応援をよろしくお願いします。</p>
        <p>しかし、それは良い事だけではありません、太陽光発電システムによる電力供給は天候に左右されるため、蓄電分により夜のサービス提供がうまくいかない場合があります、そのためお客様にも蓄電残量がどれくらいで尽きるのかが確認できる機能を以下に設置しましたので気になる方はご覧ください！！</p>
        <div style="border-radius: 5px; background: rgba(0,200,255,0.5); padding: 10px;">
          <h4>プシューサービスのバッテリー状態</h4>
          <p>電源の供給が不安定なためバッテリー駆動してる際にいつバッテリー切れになるのかを閲覧することが可能です。</p>
          <iframe src="./pusyuusystem/pages/server_status/" style="border-width: 0; width: 100%;"></iframe>
        </div>

        <h3>プシューサービスを作った理由</h3>
        <p>私自身が「自分好みの自由なサービスを作りたい！」という気持ちから始まりました。</p>
        <p>他のサービスではできなかったこと（壁紙の自由な変更や追跡されない安心感）を実現したかったのです。</p>
        <p>見た目も、自分で細かく調整できるサービスが作りたかった。それが原動力でした。</p>

        <h3>これまでの歴史</h3>
        <p>以前は無料のドメインを使っていました：</p>
        <ul>
          <li><a href="http://web.archive.org/web/20220516220514/https://21emon.server-on.net" target="_blank">21emon.server-on.net</a></li>
          <li><a href="https://web.archive.org/web/20230121102509/http://21emon.wjg.jp" target="_blank">21emon.wjg.jp</a></li>
        </ul>
        <p>現在は独自ドメイン <a href="https://pusyuuwanko.com" target="_blank">pusyuuwanko.com</a> を取得して運用しています。</p>

        <h3>サービスの使い方</h3>
        <p>上のナビゲーションバーから「プロダクト」を選ぶと各サービスを利用できます。</p>
        <p>利用規約・プライバシーに同意いただく必要があるため、「通知」も時々チェックしてください。</p>

        <article class="saiyou">
          <a href="#about">
            <h3>プシューわんこ（阿部勇生）について</h3>
            <p>私の経歴、得意なプログラミング言語などがこちらにまとまっています。</p>
            <p>採用をお考えの方へ：ソースコードの完全公開はできませんが、成果物の一部やクローズドな方法でお見せできます。</p>
          </a>
        </article>

        <h3>運営の仕組み</h3>
        <p>このページは、プシューサービス専用のサーバーから配信しています。</p>
        <p>例えば本サービスの例でいきますと、あなたが当サイトにアクセスするとドメイン名を通してサーバーのIPアドレスが返され、そこでこのページのHTML・CSS・JSがあなたのブラウザがダウンロードと解析（それらのダウンロードしたファイルのコンピュータ言語等をブラウザが読み取る作業です）をし表示が行われます。</p>

        <p>使用しているサーバーの写真はこちら：</p>
        <a href="#zoom_img_プシューサービスの運営ってどうなってるの？_1">
          <img style="width: 65%; height: 300px; object-fit: cover; cursor: zoom-in; border-radius: 10px;" src="./pusyuusystem/images/pusyuuserver.jpeg" alt="pusyuuserver" />
        </a>

        <div class="modal" id="zoom_img_プシューサービスの運営ってどうなってるの？_1">
          <div>
            <a href="#mc"></a>
            <div>
              <span>拡大画像</span>
              <div>
                <img width="100%" src="./pusyuusystem/images/pusyuuserver.jpeg" />
              </div>
            </div>
          </div>
        </div>

        <p>プシューサービスの運営は、私（プシューわんこ）が1人で行っています。</p>
        <ul>
          <li>サーバー管理</li>
          <li>サイトの更新・修正</li>
          <li>新しい機能の開発</li>
          <li>バグ修正やトラブル対応</li>
        </ul>
        
        <p>プシューサービスに完成はありません。進化し続けます！（ちなみこれが「アジャイル開発」だったんだなと知りました😅）</p>
        <h3>映像で見るプシューサービス</h3>
        <p>プシューわんこが各サービスをかいつまんで紹介しています</p>
        <div class="showcase-video-wrap">
          <video class="showcase-video-desktop" data-variant="desktop" autoplay loop muted playsinline><source src="./pusyuusystem/videos/show-explainer-desktop.mp4" type="video/mp4"></source></video>
          <video class="showcase-video-mobile" data-variant="mobile" autoplay loop muted playsinline><source src="./pusyuusystem/videos/show-explainer-mobile.mp4" type="video/mp4"></source></video>
        </div>
        <p>これからも、より良いサービスを提供できるよう頑張っていきます😊</p>
      </section>
      <section id="products">
        <h2>プロダクト</h2>
        <p>これがプシューサービスのメインの部分です！！、今まで様々なこれはなかったなーというサービスや、私自身が欲しいと感じたサービスを開発管理をしながら公開してる部分です、これらのサービスは実際にに私自身が利用してるサービスです、作って放置はしないそれが私のサービスのモットーです！。どうぞお楽しみください！！、もし改善点やご不満な点があればフィードバックもお受けしてますので、このページを開くと出るフィードバックウィンドウまたは、私について（一番下のセクション）にある欄よりお問い合わせからも可能です。 </p>
        <div class="center">
          <?php echo($setProductData); ?>
        </div>
      </section>
      <section id="sougoLink">
        <h2>相互リンク＆プシューの好きなリンク集</h2>
        <div class="sougo_links">
          <div>
            <div class="top">
              <div>
                <h3>相互リンクやバナー集</h3>
                <p>ここには相互リンクやバナーをしたい人がいたら貼る場所です。</p>
                <h4>そもそもリンクについて</h4>
                <p>一方通行でよろしければ許可を取らずとも行って良いです。しかし相互リンクを行う場合は連絡をお願いします。</p>
                <h4>相互リンクの追加手順について</h4>
                <p>プロフィール内のSNSかお問い合わせフォーム（これらは<strong>”私について”</strong>を押していただけると出ます）により連絡したら以下の手順で追加します。</p>
                <ol>
                  <li>アドレスはこちら<span style="cursor: pointer; color: #aaff00" onclick="navigator.clipboard.writeText(document.getElementById('copyAddress1').textContent); alert('url is copyted!!');" id="copyAddress1"><?= $mainPublicAddressHtml ?></span>でお願いします。</li>
                  <li>バナーの画像はこちら<span style="cursor: pointer; color: #aaff00" onclick="navigator.clipboard.writeText(document.getElementById('copyAddress2').textContent); alert('bunner is copyted!!');" id="copyAddress2"><?= $mainPublicBaseHtml ?>/pusyuusystem/images/pw-banner.png</span>接続状態とバナー更新の際に自動更新が可能なため直リンクも歓迎します！！</li>
                </ol>
                <p>※JavaScriptが有効な方はクリックしてもコピーが可能です。</p>
                <h4>相互リンクの解消とリンク切れについて</h4>
                <p>長期にわたる<strong>相互リンクのリンク切れ</strong>や<strong>意図した告知なしによるリンクの解消</strong>または<strong>その他の原因による相互リンクの離脱状態が続いてる場合</strong>につきましては、HTMLのコメントとして残しておきます。</p>
                <p>HTMLコメントからの復活につきましては、再度ご一報と相互リンクとして状態復帰が行える状態になってることを確認できた場合コメント化を解除いたします。</p>
                <p>また、削除願いのご一報（コメント化と完全削除（抹消）どちらが良いかの旨もお送りくださるようお願いいたします）くださている場合、HTMLのコメント化ももちろん、完全に抹消する事も可能ですのでその点ご安心ください。</p>
                <p><strong>※注意点</strong>：あくまでも相互リンクの管理は私の判断によって行われます、あまりにもひどい場合例えば、法的、道徳的、これらに限らないの理由ににより、コメント化せずに完全抹消する場合もございます、また、完全抹消、コメント化に問わず発生した損害において私は一切の責任を負わないものとしますで予めご了承ください。</p>
              </div>
            </div>
            <?php echo $getSougoLink; ?>
          </div>
        </div>
        <hr/>
        <div class="sougo_links">
          <div>
            <div class="top">
              <div>
                <h3>プシューが個人的に好きなリンクやバナー集</h3>
                <p>ここには相互リンクやバナーでもないあくまでもプシューが好んでいるサイトのリンクなどが貼ってあります。</p>
              </div>
            </div>
            <?php echo $getLikeLink; ?>
          </div>
        </div>
      </section>
      <section id="about">
        <h2>私について</h2>
        <p>私、ニックネームでプシューわんこ/として活動しています。</p>
        <h3>どのような活動をしているのか？</h3>
        <p>プシュー（私）はYouTubeでプシューコントや雑談系の動画をやってたり、プシューサービス（この場）で作ったアプリ（サービス）の紹介やそれらを利用でいる場の開発をしていたり、提供するサービスを開発しています。好きなことは最近（二十歳現在）は勉強することがもっぱら好きです。</p>
        <p>更に詳しく<strong>プシュー（私）</strong>についてや活動を行っている<strong>SNS</strong>や<strong>ソフトウェア開発のプラットフォーム</strong>などの<strong>リンク</strong>を知りたい場合はこちらの<a href="./profile">プシューわんこ/のプロフィール</a></li>をご覧ください。
        <h3>プシューサービスへフィードバックまたは不明な点やクレームを入れたい</h3>
        <p>はい、お問い合わせ機能があるので可能です、できればフィードバックであると助かりますが、やはりお客様（あなた）が何を求めているのか、そして何が問題点なのかを知るにはやはり素を知るのが一番ですので、クレームも受け入れます。ですが注意点がいくつかあります。いくら問い合わせをしても解決できない事柄もあるためそのような場合があることを予めご了承ください。もう一つ、お問い合わせの際有効なメールアドレスが入力されていない場合、返信できませんのでその点予めご了承ください。</p>
        <p>お問い合わせフォームはどこにあるの？それはこちらのリンクに<a href="./pusyuusystem/pages/contact">お問い合わせフォーム</a>と書いてあるのでそこからどうぞです！！</p>
        <?php echo notationDisp(); ?>
      </section>
    </main>
    <footer>
      <?php echo notationDisp("copyright"); ?>
    </footer>
    <?php
      // =================================================================
      // プッシュ通知の設定UI(#main-push-settings の中身)を組み立てるスクリプト。
      //
      // 【必ず #main-push-settings より後ろで読み込むこと】main-push.js は
      // 読み込まれたその場で getElementById('main-push-settings') を引き、
      // 見つからなければ黙って終わります。<head>に置くとその時点では<body>が
      // まだ解析されていないため必ず見つからず、購読ボタンも失敗メッセージも
      // 一切出ません。画面には(PHPが常に描画している)RSSの案内だけが残り、
      // JSエラーも出ないので「プッシュ通知の選択肢が消えた」ように見えます。
      // 以前このファイルで実際に起きた症状です。p-chatも同じ理由で通知系の
      // スクリプトを</body>の直前にまとめています。
      // =================================================================
    ?>
    <script src="./pusyuusystem/scripts/push-subscribe.js"></script>
    <script src="./pusyuusystem/scripts/main-push.js"></script>
  </body>
</html>