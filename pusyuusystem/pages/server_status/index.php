<?php
$json = @file_get_contents('http://127.0.0.1/pusyuusystem/apis/server_status');
$data = json_decode($json, true);
if (isset($_GET["getOtinpo"])) {
  header('Content-Type: text/html; charset=UTF-8');
  if ($data) {
    $power = htmlspecialchars($data['power']);
    $battery = intval($data['battery']);
    $status = htmlspecialchars($data['status']);
    

    echo "<div class='battery-box'>";

    $currentPower = $power === "AC" ? "🔌" . $power : "🔋" . $battery . "% (" . $power . ")";
    echo "<strong>" . $currentPower . "</strong><br>";

    $hours = isset($status) && !empty($status) ? ($status === "Charging" || $status === "Full") ? "" : htmlspecialchars($data['remaining_hours']) : "";

    if (isset($status) && !empty($status)) {
      if ($status === "Full") {
        echo "状態: 充電完了で動作中<br />";
      } else if ($status === "Discharging") {
        echo "状態: バッテリーで動作中<br />";
      } else if ($status === "Charging") {
        echo "状態：充電しながら動作中<br />";
      } else if ($status === "Not charging") {
        echo "状態：充電完了後で動作中<br />";
      } else {
        echo "状態: {$status}<br />";
      }
    }

    if ($hours !== "") {
      echo "残り時間: {$hours} 時間<br />";
      if ($battery <= 50 && $battery >= 20) {
        echo '<i style="color: #fff000; text-shadow: 1px 1px 2px #000;">注意が必要です、電池残量が半分を切りました。</i>';
      } else if ($battery <= 20) {
        echo '<i style="color: #ff0000; text-shadow: 1px 1px 2px #000;">もうすぐ電池残量がなくなり自動シャットダウンする可能性があります。</i>';
      }
    }

    echo "</div>";
  } else if ($json === false) {
    echo "<div class='battery-error'>バッテリー情報の取得に問題が発生しました。</div>";
  } else {
    echo "<div class='battery-error'>バッテリー情報の解析エラー</div>";
  }
  exit;
} else if (isset($_GET["getStatus"])) {
  echo '<p style="font-size: 80px">さっきうんこしてきたんだけど？、もしかして：うんこたべる？</p><p style="font-size: 1px; color: #ff0000;">正常なアクセス、見えてないよね、怖い怖い</p>';
  exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, user-scalable=yes, minimum-scale=1.0, maximum-scale=6.0" />
    <title>Battery Widget</title>
    <style>
      * {
        box-sizing: border-box;
      }
      body {
        font-family: sans-serif;
        font-size: 14px;
        margin: 0;
        position: relative;
        min-height: 130vh;
      }
      .battery-box {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px;
        background: #f9f9f9;
      }
      .battery-error {
        color: red;
      }
    </style>
  </head>
  <body>
    <div id="battery-widget">JavaScriptが有効でないか、ブラウザが古いため表示できませんでした。</div>

    <script>
      document.getElementById("battery-widget").innerHTML = "";
      async function loadBattery() {
        document.getElementById("battery-widget").innerHTML = "電池の状況を更新中...";
        if (navigator.onLine) {
          try {
            const res = await fetch("./?getOtinpo", {cache: "no-cache"});
            const html = await res.text();
            document.getElementById("battery-widget").innerHTML = html;
          } catch (e) {
            console.error(e);
            document.getElementById("battery-widget").innerHTML = "<div class='battery-error'>サーバー側の電池残量不足や電力供給が止まったあるいはその他のサーバー側のトラブルによりサーバー情報の取得に失敗しました。</div>";
          }
        } else {
          document.getElementById("battery-widget").innerHTML = "<div class='battery-error'>あなたの端末がオフラインまたはその他の理由によりサーバー情報の取得に失敗しました。</div>";
          return;
        }
        console.log(navigator.onLine);
      }

      loadBattery();
      setInterval(loadBattery, 60000); // 1分ごとに更新
    </script>
  </body>
</html>