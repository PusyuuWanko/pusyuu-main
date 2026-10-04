<?php 
require_once("../../pusyuusystem/scripts/php_scripts/notation.php");
?>
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=yes, maximun-scale=6.0, minimum-scale=1.0"/>
    <meta name="copyright" content="© 2020-2026 by PusyuuWanko/"/>
    <meta name="description" content="20にはなりたくない！！！、ほんと世の中思い通りには行きませんよねｗ"/>
    <meta property="og:title" content="プシューサービス - 二十歳にはなりたくないー、そんなプシューの気持ちのサイト" />
    <meta property="og:site_name" content="プシューサービス - 二十歳にはなりたくないー、そんなプシューの気持ちのサイト" />
    <meta property="og:description" content="20にはなりたくない！！！、ほんと世の中思い通りには行きませんよねｗ" />
    <meta property="og:image" content="https://pusyuuwanko.com/profile/assets/images/profile_meta.jpg" />
    <meta property="og:type" content="profile" />
    <link rel="manifest" href="../assets/documents/manifest.json"/>
    <link rel="shortcut icon" href="https://pusyuuwanko.com/pusyuusystem/images/favicon.ico"/>
    <script src="../assets/scripts/particles.min.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        const countDisp = document.getElementById("counter");
        let time = new Date();
        const year = time.getFullYear();
        const month = time.getMonth();
        const day = time.getDate();
        const finalYear = 2023;
        const finalMonth = 12;
        const finalDay = 24;
        let age = 0;
        if (finalDay <= day && finalMonth <= month+1) {
          age = time.getFullYear() - 2003;
        } else {
          age = time.getFullYear()-1 - 2003;
        }

        // 2024年12月25日（今日の日付）
        // 以下は以前のコード例です

        // 以前のコード
        /*
        let time = new Date();
        const year = time.getFullYear();
        const month = time.getMonth();
        const day = time.getDate();
        const finalYear = 2023;
        const finalMonth = 12;
        const finalDay = 24;
        const age = finalDay == day || finalDay < day && finalMonth == month || finalMonth < month ? time.getFullYear() - 2003 : time.getFullYear()-1 - 2003;
        */

        // 【学習内容】
        // 1. **条件演算子 (`?:`) の使い方**:
        //    - 条件式（`finalDay == day || finalDay < day && finalMonth == month || finalMonth < month`）を使い、条件が満たされれば`time.getFullYear() - 2003`を返し、そうでなければ`time.getFullYear() - 1 - 2003`を返しています。
        //    - `? :`を使うことで、`if`文のように条件分岐が一行で書けますが、長い条件式が可読性を低くすることがあります。

        // 2. **条件式の論理演算子 (`||` と `&&`) の組み合わせ**:
        //    - `finalDay == day || finalDay < day && finalMonth == month || finalMonth < month`という複雑な条件式があります。
        //    - 複数の論理演算子（`||`と`&&`）を使う際には、条件の優先順位が問題になることがあります。`&&`は`||`より優先されるため、意図しない挙動を引き起こす可能性があります。
        //    - 複雑な条件式は、優先順位を明確にするためにカッコを使うとよりわかりやすくなります。
        //    - 例えば、`(finalDay == day || finalDay < day) && (finalMonth == month || finalMonth < month)`のように括弧でグループ化することで、意図が明確になります。

        // 【改善ポイント】
        // 1. **コードの可読性を改善**:
        //    - 条件が複雑で一度に理解するのが難しいため、複数行に分けて条件式を整理する方が良いです。
        //    - 複雑な条件はコメントをつけて、後で見たときに理解しやすくすることが大切です。

        // 2. **論理演算子の整理**:
        //    - 条件式内で、`&&`と`||`を混在させるのは可読性が低いため、条件を見やすく整理することが重要です。
        //    - また、`&&`や`||`を使う際には、条件をグループ化するために括弧を使うことで意図を明確にすることができます。

        // 3. **`<=`を使った簡潔な条件式**:
        //    - 条件式に`<=`（小なりまたは等しい）を使うことで、`<`と`===`を組み合わせるよりも簡潔に書けます。
        //    - 例えば、`finalDay <= day`や`finalMonth <= month`のようにして、一度の条件式で「より小さいか、または同じか」をチェックできます。

        const daysInMonth = [0, 31, 28, 31, 30, 31, 30, 31, 30, 31, 30, 31];
        function isLeapYear(year) {
          return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
        }

        function convertMonthToDays(month, day, year) {
          const daysInThisMonth = [...daysInMonth]; // 一時的なコピーを作成
          daysInThisMonth[2] = isLeapYear(year) ? 29 : 28;
          return daysInThisMonth[month];
        }

        let formatedDay = convertMonthToDays(month, day, year);
        let nokoriDay;

        if (finalYear === year) {
          if ((month + 1) === finalMonth) {
            if (day <= finalDay) {
              nokoriDay = finalDay - day;
            } else {
              nokoriDay = "19終わっちゃったよ；；";
            }
          } else {
            nokoriDay = finalMonth + "月は、まだだそうです。";
          }
        } else if (year < finalYear) {
          nokoriDay = `プシューわんこ/さんは二十歳より若く${age}歳なので若すぎです。`;
        } else {
          nokoriDay = `もう過ぎてしまいましたね、プシューわんこ/は二十歳を迎え${age}歳なので、もう二十歳はきません。`;
        }

        const birthdayCount = document.getElementById("birthdayCount");
        if ((month + 1) === finalMonth) {
          if (0 < (finalDay - day)) {
            birthdayCount.innerHTML = "プシューわんこ/は残り<span>" + (finalDay - day) + "</span>日で誕生日を迎えます！";
          } else if ((finalDay - day) === 0) {
            birthdayCount.innerHTML = "プシューわんこ/は今日誕生日です！！ちなみに本日の<span>" + (year - finalYear) + "</span>年前、<span>5</span>時<span>31</span>分に出産されました。";
          }
        } else {
          birthdayCount.innerHTML = "残念。。。今日はまだお誕生日の月になっていません。。。。";
        } 

        countDisp.innerText = nokoriDay;

        const productList = document.getElementById("productList");
        productList.innerHTML = "";
        fetch("https://pusyuuwanko.com/pusyuusystem/documents/futures.json").then(res=>res.json()).then((data)=> {
          data.links.forEach((item)=> {
            const li = document.createElement("li");
            const a = document.createElement("a");
            a.textContent = item.title;
            a.href = item.url;
            li.appendChild(a);
            productList.appendChild(li);
          });
        });

        particlesJS("particles-js", {"particles":{"number":{"value":400,"density":{"enable":true,"value_area":800}},"color":{"value":"#fff"},"shape":{"type":"circle","stroke":{"width":0,"color":"#000000"},"polygon":{"nb_sides":5},"image":{"src":"img/github.svg","width":100,"height":100}},"opacity":{"value":0.5,"random":true,"anim":{"enable":false,"speed":1,"opacity_min":0.1,"sync":false}},"size":{"value":10,"random":true,"anim":{"enable":false,"speed":40,"size_min":0.1,"sync":false}},"line_linked":{"enable":false,"distance":500,"color":"#ffffff","opacity":0.4,"width":2},"move":{"enable":true,"speed":6,"direction":"bottom","random":false,"straight":false,"out_mode":"out","bounce":false,"attract":{"enable":false,"rotateX":600,"rotateY":1200}}},"interactivity":{"detect_on":"canvas","events":{"onhover":{"enable":true,"mode":"bubble"},"onclick":{"enable":true,"mode":"repulse"},"resize":true},"modes":{"grab":{"distance":400,"line_linked":{"opacity":0.5}},"bubble":{"distance":400,"size":4,"duration":0.3,"opacity":1,"speed":3},"repulse":{"distance":200,"duration":0.4},"push":{"particles_nb":4},"remove":{"particles_nb":2}}},"retina_detect":true});var count_particles, stats, update;
      });
    </script>
    <title>プシューサービス - 二十歳にはなりたくないー、そんなプシューの気持ちのサイト</title>
    <style>
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        padding: 0;
      }
      body::before {
        content: "";
        width: 100vw;
        height: 100vh;
        position: fixed;
        inset: 0;
        background-image: url("../assets/images/1.jpg");
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        z-index: -1;
      }
      .show {
        position: relative;
        width: auto;
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
      }
      .show > img {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-repeat: no-repeat;
        background-size: cover;
      }
      .show > canvas {
        position: absolute;
        top: 0;
      }
      .show > div {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%,-50%);
        display: flex;
        justify-content: center;
        align-items: center;
        width: 80%;
        height: 80%;
        padding: 20px;
        box-sizing: border-box;
        border-radius: 20px; 
        background: rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
      }
      .show > div > strong {
        font-size: 40px;
        color: #fff;
      }
      header {
        position: sticky;
        top: 0;
        left: 0;
        right: 0;
        border-bottom: 1px solid #000;
        background: linear-gradient(to bottom left, rgba(0,255,0,0.5),rgba(255,255,255,0.5));
        padding: 15px;
        z-index: 2;
      }
      header > h1 {
        padding: 0px 10px 0px;
        margin: 0;
        font-family: メイリオ;
      }
      main {
        padding: 20px;
      }
      footer {
        padding: 10px;
        background: rgba(243,255,243);
      }
      footer > div {
        display: flex;
        justify-self: center;
        align-items: center;
      }
      footer > div > div > h3 {
        border-bottom: 1px solid #0f0;
        padding: 5px;
        border-left: 3px solid #0af;
      }
      footer > div > div > li {
        text-align: center;
        list-style: none;
      }
      footer > div > small {
        text-align: center;
        width: 100%;
        padding: 10px;
      }
      a {
        color: #00ff00;
        text-shadow: 1px 1px 2px #000fff;
      }
      a:hover {
        color: #ff0000;
      }
      .text-center {
        text-align: center;
      }
      .flex {
        display: flex;
        flex-wrap: wrap;
      }
      .center {
        display: flex;
        justify-content: center;
        align-items: center;
      }
      .left {
        display: flex;
        justify-content: flex-start;
      }
      .right {
        display: flex;
        justify-content: flex-end;
      }
      .column {
        display: flex;
        flex-direction: column;
      }
      .gap15px {
        display: flex;
        gap: 15px;
      }
      #counter,
      #birthdayCount > span {
        color: #00ff00;
        font-size: 50px;
      }
      .tyuuCord {
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #000;
        border-radius: 20px;
        border: 1px solid #FFF;
        width: 350px;
        height: 500px;
        box-sizing: border-box;
        overflow: hidden;
      }
      .tyuuCord > div {
        width: 100%;
        height: 100%;
        overflow-x: hidden;
        overflow-y: auto;
        padding: 20px
      }
      .tyuuCord > div > .twitter {
        width: 100%;
        height: 80%;
        overflow: auto;
      }
      .tyuuCord > div > iframe {
        border-width: 0;
        border: 1px solid #000;
      }
      @media only screen and (max-width: 600px) {
        header > h1 {
          font-size: 20px;
        }
        .show > div > strong {
          font-size: 20px;
        }
        tyuuCord {
          width: 90vw;
          display: inline;
        }
      }
    </style>
    <!--
        *------------------------------------
        |  ThisPageVersion: 1.4.1         |
        |  © ISAMI ABE                    |
        |  LastUpdate: 2026-06-25         |
        |  License: MIT License           |
        |  PusyuuHatatiniNaritakunai      |
      ------------------------------------*
    -->
  </head>
  <body>
    <div class="show" id="particles-js">
      <img src="../assets/images/2.JPG" oncontextmenu="return false;" onselectstart="return false;" onmousedown="return false;">
      <div>
        <strong>時が進むのは早いよね〜</strong>
      </div>
    </div>
    <header>
      <h1>二十歳にはなりたくないー、そんなプシューの気持ちのサイト</h1>
    </header>
    <main class="center flex gap15px">
      <article class="center tyuuCord">
        <div>
          <h2>このページを作った理由↓</h2>
          <p>まぁこんなことをほざいてしまったので、作ってみましたｗ</p>
          <div class="twitter">
            <blockquote class="twitter-tweet" data-media-max-width="560"><p lang="ja" dir="ltr">い、いやだーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーーー、二十歳にはなりたくね〜、と言ってんだろ！！！！！！！<br><br>もうWEBでカウントダウンを作ってやる。</p>&mdash; プシューわんこ/ (阿部勇生) (@PusyuuWanko) <a href="https://twitter.com/PusyuuWanko/status/1735531747142733895?ref_src=twsrc%5Etfw">December 15, 2023</a></blockquote> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
          </div>
        </div> 
      </article>
      <article class="center tyuuCord">
        <div>
          <h2>肝心のカウンター</h2>
          <p>しっかり作りました。</p>
          <div class="text-center">
            <h3>19の私の二十歳までいくつカウンター</h3>
            <p>19歳が終了するまでのこり<span id="counter">分からない</span>日</p>
            <p>バースデイカウンター：<span id="birthdayCount">Err Count, sorry NoJavaScript or your browser is old...</span></p>
          </div>
        </div>
      </article>
      <article class="tyuuCord">
        <div>
          <h3>お知らせ↓</h3>
          <ol>
           <li>ページを開設しました！！</li>
           <li>バグがあったため修正しました。</li>
           <li>ちょっと修正</li>
           <li>コメント機能の追加</li>
           <li>干渉防止のためスクリプトの場所を移動</li>
           <li>プロダクト欄の追加</li>
           <li>作成当時のコードのまま状況を残す方向性になりました。</li>
           <li>フォルダーなどの場所が変わったため、パスの変更のみをしました。</li>
           <li>バグがあったため修正したのとせっかくの記念サイトなのでコメントで以前のコードとの変化を記録しました。</li>
           <li>このサイトを開発した日を忘れてしまわないようにメモ”2023年12月15日”</li>
           <li>規約情報を追加しました。</li>
           <li>せっかくのロジックが残ってたのでつなぎ合わせて誕生日カウンターを追加してみました</li>
           <li>2026/06/25誕生日カウンターロジックに問題があったため修正</li>
          </ol>
        </div>
      </article>
      <article class="center tyuuCord">
        <div>
          <h3>コメントはこちら</h3>
          <p>感想やフィードバックなどどうぞ。</p>
          <iframe style="box-sizing: border-box; width: 100%; height: 90%;" src="https://pips.pusyuuwanko.com/?id=669a20299088179e7bb928ae5075d"></iframe>
        </div>
      </article>
      <article class="tyuuCord">
        <div>
          <h3>その他のプロダクト↓</h3>
          <ol id="productList">
            <li style="color: #ff0000;">Jsが有効ではないかブラウザが古いです。</li>
          </ol>
        </div>
      </article>
    </main>
    <footer>
      <div>
        <div>
          <?php echo notationDisp(); ?>
        </div>
      </div>
      <div>
        <?php echo notationDisp("copyright"); ?>
      </div>
    </footer>
  </body>
</html>