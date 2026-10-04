<?php
$t19_20 = isset($_GET['hatatinaritakunai']);
$program4th = isset($_GET["programming4th"]);
if ($t19_20) {
  header("location: ./hatatinaritakunai");
  exit;
}
if ($program4th) {
  header("location: ./programming4th");
  exit;
}

// プロダクト一覧は台帳(pusyuu_registry)から受け取ります。
// 無い環境では、一覧だけが「取得できませんでした」になります。
$directry = __DIR__ . "/../pusyuusystem/scripts/php_scripts/pusyuu_registry_client.php";
if (file_exists($directry)) {
  require_once($directry);
}

function dispProducts() {
  // このプロフィールページは台帳のプロダクトではないので、外すものはありません。
  $links  = class_exists('PusyuuRegistryClient') ? PusyuuRegistryClient::links() : null;
  $result = "";

  if ($links === null) {
    $result = '<p class="error-msg">エラー：プロダクト一覧を取得できませんでした。</p>';
  } else {
    $result = '<div class="products-grid">';
    foreach ($links as $product) {
      $img = ($product["image"] !== '') ? $product["image"] : "../pusyuusystem/images/avater.jpg";
      $result .= '<a class="product-card" href="' . htmlspecialchars($product["url"], ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($product['title']) . '" target="_blank" rel="noopener">';
      $result .= '<div class="product-thumb"><img src="' . $img . '" alt="" width="50" height="50"></div>';
      $result .= '<p class="product-name">' . htmlspecialchars($product['title']) . '</p>';
      $result .= '</a>';
    }
    $result .= '</div>';
  }

  return $result;
}

function renderContent($profile) {
  $html = '';

  // contentは「1本の文章」か「部品の並び」のどちらかで来ます。
  // どちらであっても、組み立てた結果は下の1箇所から出ていきます。
  if (!is_array($profile["content"])) {
    $html = '<p class="item-desc">' . $profile["content"] . '</p>';
  } else {
    foreach ($profile["content"] as $item) {
      if (!is_array($item)) {
        $html .= '<p class="item-desc">' . $item . '</p>';
        continue;
      }
      if (isset($item["title"])) {
        $html .= '<h3 class="item-title">' . $item["title"] . '</h3>';
      }
      if (isset($item["description"])) {
        $html .= '<p class="item-desc">' . $item["description"] . '</p>';
      }
      if (isset($item["list"])) {
        $html .= '<ul class="profile-list">';
        foreach ($item["list"] as $li) {
          $strong = !empty($li["title"]) ? '<strong>' . $li["title"] . '</strong><span class="list-sep"> — </span>' : '';
          $html .= '<li>' . $strong . $li["content"] . '</li>';
        }
        $html .= '</ul>';
      }
      if (isset($item["table"])) {
        $html .= '<div class="table-scroll"><table class="profile-table"><thead><tr>';
        foreach ($item["table"]["headers"] as $h) {
          $html .= '<th>' . $h . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($item["table"]["rows"] as $row) {
          $html .= '<tr>';
          foreach ($row as $cell) {
            $html .= '<td>' . $cell . '</td>';
          }
          $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
      }
    }
  }

  return $html;
}

function loadProfileData($filename) {
  $path   = "./assets/documents/profile_data/" . $filename . ".json";
  $file   = file_get_contents($path);
  $result = [];

  if ($file === false) {
    $result = [];
  } else {
    $result = json_decode($file, true) ?: [];
  }

  return $result;
}

function buildSections($data, $prefix) {
  $nav = '';
  $sections = '';
  foreach ($data as $i => $profile) {
    $id = $prefix . $i;
    $nav .= '<li><a href="#' . $id . '" class="nav-link">' . $profile["title"] . '</a></li>';
    $sections .= '<section class="profile-section" id="' . $id . '">';
    $sections .= '<h2 class="section-title">' . $profile["title"] . '</h2>';
    $sections .= '<div class="section-body">' . renderContent($profile) . '</div>';
    $sections .= '</section>';
  }
  return ['nav' => $nav, 'sections' => $sections];
}

$detailsData = loadProfileData('details');
$usuallyData = loadProfileData('usually');

$summaryBuild = buildSections($detailsData, 'sum');
$fullBuild = buildSections($usuallyData, 'ful');

if (file_exists("../pusyuusystem/scripts/php_scripts/notation.php")) {
  require_once("../pusyuusystem/scripts/php_scripts/notation.php");
}

$title = "プシューの世界観 — プシューわんこ/ のプロフィール";
$description = "プシューわんこ/（阿部勇生）の世界観・考え方・趣味・経歴が詰まったプロフィールページです。プシューサービスの理念もここから読めます。";
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <meta name="description" content="<?php echo $description; ?>" />
    <meta name="viewport" content="width=device-width, user-scalable=yes, maximum-scale=6.0, minimum-scale=1.0" />
    <meta name="copyright" content="© 2020-2026 created by PusyuuWanko/" />
    <meta name="keywords" content="プロフィール,PusyuuWanko/ of Profile,プシュー,Pusyuu,好きなもの,like,世界観" />
    <meta property="og:title" content="<?php echo $title; ?>" />
    <meta property="og:site_name" content="<?php echo $title; ?>" />
    <meta property="og:description" content="<?php echo $description; ?>" />
    <meta property="og:image" content="https://pusyuuwanko.com/profile/assets/images/profile_meta.jpg" />
    <meta property="og:type" content="profile" />
    <link rel="canonical" href="https://isami.moe" />
    <link rel="shortcut icon" href="https://pusyuuwanko.com/pusyuusystem/images/favicon.ico" />
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="./assets/styles/style.css" />
    <!--
      *------------------------------------
      |  ThisPageVersion: 9.1.0         |
      |  © 2021-2026 By ISAMI ABE       |
      |  LastUpdate: 2026-06-21         |
      |  License: MIT License           |
      |  PusyuuProfile                  |
      ------------------------------------*
    -->
  </head>
  <body>

    <div class="page-wrapper">

      <nav class="sidebar" aria-label="ページナビゲーション">
        <div class="sidebar-inner">
          <div class="sidebar-brand">
            <div class="sidebar-avatar">
              <img src="./assets/images/profile1.jpg" alt="プシューわんこ" width="48" height="48" />
            </div>
            <div>
              <p class="sidebar-name">プシューわんこ/</p>
              <p class="sidebar-sub">阿部 勇生</p>
            </div>
          </div>

          <div class="sidebar-tab-labels">
            <label for="tab-majority" class="sidebar-tab-label">浅く知る</label>
            <label for="tab-worldview" class="sidebar-tab-label">深く知る</label>
          </div>

          <div class="sidebar-nav-wrap">
            <ul class="nav-list">
              <li><a href="#hero" class="nav-link">▲ トップへ</a></li>
            </ul>

            <div class="majority-nav">
              <p class="nav-group-label">浅読みプロフィール</p>
              <ul class="nav-list">
                <?php echo $summaryBuild['nav']; ?>
              </ul>
            </div>

            <div class="worldview-nav">
              <p class="nav-group-label">プシューの世界観</p>
              <ul class="nav-list">
                <?php echo $fullBuild['nav']; ?>
              </ul>
            </div>

            <p class="nav-group-label">その他</p>
            <ul class="nav-list">
              <li><a href="#contact" class="nav-link">SNS・連絡先</a></li>
              <li><a href="#products" class="nav-link">プロダクト</a></li>
              <li><a href="#about-service" class="nav-link">サービスについて</a></li>
            </ul>
          </div>
        </div>
      </nav>

      <div class="main-wrapper">

        <!-- タブ制御（非表示ラジオボタン） -->
        <input type="radio" name="profile-tab" id="tab-majority" class="tab-ctrl" checked>
        <input type="radio" name="profile-tab" id="tab-worldview" class="tab-ctrl">

        <details class="mobile-nav">
          <summary class="mobile-nav-toggle">
            <span>&#9776; メニュー — プシューわんこ/</span>
          </summary>
          <div class="mobile-nav-inner">
            <div class="mobile-tab-switch">
              <label for="tab-majority" class="mobile-tab-label">浅読みプロフィール</label>
              <label for="tab-worldview" class="mobile-tab-label">深読みプロフィール</label>
            </div>
            <div class="majority-nav">
              <ul class="nav-list">
                <li><a href="#hero" class="nav-link">▲ トップへ</a></li>
                <?php echo $summaryBuild['nav']; ?>
              </ul>
            </div>
            <div class="worldview-nav">
              <ul class="nav-list">
                <li><a href="#hero" class="nav-link">▲ トップへ</a></li>
                <?php echo $fullBuild['nav']; ?>
              </ul>
            </div>
            <p class="nav-group-label" style="margin-top:10px;">その他</p>
            <ul class="nav-list">
              <li><a href="#contact" class="nav-link">SNS・連絡先</a></li>
              <li><a href="#products" class="nav-link">プロダクト</a></li>
              <li><a href="#about-service" class="nav-link">サービスについて</a></li>
            </ul>
          </div>
        </details>

        <div class="tab-bar">
          <label for="tab-majority" class="tab-label">
            <span class="tab-icon" style="margin-rigth:15px;">◎</span>浅読みプロフィール
          </label>
          <label for="tab-worldview" class="tab-label">
            <span class="tab-icon" style="margin-rigth:15px;">◆</span>深読みプロフィール
          </label>
        </div>

        <header class="hero" id="hero">
          <div class="hero-inner">
            <div class="hero-avatar-wrap">
              <div class="hero-avatar">
                <img id="heroImg1" src="./assets/images/profile1.jpg" alt="プシューわんこ" oncontextmenu="return false;" onselectstart="return false;" onmousedown="return false;" />
                <img id="heroImg2" src="./assets/images/profile2.jpg" alt="プシューわんこ" oncontextmenu="return false;" onselectstart="return false;" onmousedown="return false;" />
              </div>
            </div>
            <div class="hero-text">
              <span class="hero-eyecatch">— プシューの世界観 —</span>
              <h1 class="hero-name">プシューわんこ/</h1>
              <p class="hero-realname">阿部 勇生（ABE ISAMI）</p>
              <blockquote class="hero-catch">『できるだけ依存せず、期待しすぎない』</blockquote>
              <p class="hero-desc">変わってることを好み、構造を考え、ないなら作る。プシューの世界観へようこそ。</p>
            </div>
          </div>
        </header>

        <main>
          <div class="section-group" id="summary-group">
            <?php echo $summaryBuild['sections']; ?>
          </div>
          <div class="section-group" id="worldview-group">
            <?php echo $fullBuild['sections']; ?>
          </div>
        </main>

        <footer>
          <section class="footer-section" id="about-service">
            <h3>プシューサービスについて</h3>
            <p><strong>『できるだけ依存せず、期待しすぎない』</strong>という考え方を大切にしながら、不便だけど便利、そんな絶妙なバランスのサービスを目指しています。<strong>『便利すぎるのはなんだか退屈』</strong>と感じている方にも、新しい発見や楽しさを感じてもらえるような体験を提供したいと思っています。ただ便利なだけではなく、少しの不便さを取り入れることで、自然と自分らしい使い方を見つけられるようなサービスを目指しています。そして、プシューサービスに<strong>『完成』</strong>という終着点はありません。進化し続けることで、より多くの方の暮らしに寄り添える存在になりたいと考えています。</p>
            <p class="service-notation">
              <span>届出番号：</span><code>A-05-21453</code>
              <span>届出年月日：</span><code>R6.1.22</code>
            </p>
          </section>

          <section class="footer-section" id="contact">
            <h3>プシューわんこ/ のSNS・お仕事依頼・連絡先</h3>
            <ul class="sns-list">
              <li><a href="https://www.nicovideo.jp/user/125051420" target="_blank" rel="noopener">NicoVideo</a></li>
              <li><a href="https://www.twitter.com/PusyuuWanko" target="_blank" rel="noopener">Twitter</a></li>
              <li><a href="https://www.github.com/PusyuuWanko" target="_blank" rel="noopener">GitHub</a></li>
              <li><a href="https://www.youtube.com/@PusyuuWanko" target="_blank" rel="noopener">YouTube</a></li>
              <li><a href="https://www.tiktok.com/@pusyuuwanko" target="_blank" rel="noopener">TikTok</a></li>
              <li><a href="https://line.me/ti/p/w5CUYK1VHt" target="_blank" rel="noopener">LINE</a></li>
              <li><a href="https://coconala.com/users/4751240" target="_blank" rel="noopener">COCONALA</a></li>
              <li><a href="https://discord.gg/SXdW3GpU4M" target="_blank" rel="noopener">Discord</a></li>
              <li><a href="https://uwuzunext.info/@PusyuuWanko" target="_blank" rel="noopener">uwuzu</a></li>
              <li><a href="https://www.instagram.com/pusyuuwanko" target="_blank" rel="noopener">Instagram</a></li>
              <li><a href="https://pusyuuwanko.com/works" target="_blank" rel="noopener">お仕事依頼</a></li>
            </ul>
          </section>

          <section class="footer-section" id="products">
            <h3>プロダクト一覧</h3>
            <?php echo dispProducts(); ?>
          </section>

          <div class="footer-bottom">
            <?php if (function_exists('notationDisp')) { echo notationDisp(); echo notationDisp("copyright"); } ?>
          </div>
        </footer>

      </div>
    </div>

    <script src="./assets/scripts/animation.js"></script>
  </body>
</html>