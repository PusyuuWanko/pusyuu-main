
<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, user-scalable=yes, minimum-scale=1.0, maximum-scale=6.0"/>
    <title>プシューサービス - プログラミング４周年記念サイト</title>
    <meta name="description" content="プシューわんこ/（阿部勇生）がプログラミングを始めてから４周年立ったことからどのような経緯でプログラミングを学び始めたなどが分かる記念サイトを公開してみました！！" />
    <meta property="og:title" content="プシューサービス - プログラミング4周年記念サイト" />
    <meta property="og:site_name" content="プシューサービス - プログラミング4周年記念サイト" />
    <meta property="og:description" content="プシューわんこ/（阿部勇生）がプログラミングを始めてから４周年立ったことからどのような経緯でプログラミングを学び始めたなどが分かる記念サイトを公開してみました！！" />
    <meta property="og:image" content="https://pusyuuwanko.com/profile/assets/images/profile_meta.jpg" />
    <meta property="og:type" content="profile" />
    <link rel="shortcut icon" href="https://pusyuuwanko.com/pusyuusystem/images/favicon.ico"/>
    <style>
      * {
        box-sizing: border-box;
      }
      body {
        margin: 0;
        padding: 0;
      }
      show {
        display: block;
        position: relative;
        width: 100%;
        height: 100vh;
        height: 100dvh;
        overflow: hidden;
      }
      show > img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      show > div {
        position: absolute;
        bottom: 10%;
        left: 10%;
        margin-right: 10%;
        background: rgba(0,0,0,0.5);
        color: #FFF;
        border-radius: 10px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        padding: 15px;
      }
      show > div > h3 {
        color: #00ff00;
      }
      header {
        position: sticky;
        top: 0;
        background: linear-gradient(to left bottom, rgba(0,255,0,0.2), rgba(0,150,255,0.2));
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        color: #00ff00;
        height: 75px;
        padding: 10px;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 10;
      }
      main {
        padding: 15px;
        background: #333;
        color: #aaffaa;
      }
      main img {
        width: 30%;
        height: auto;
        border: 2px solid #00ff00;
      }
      main h2 {
        color: #00aaff;
        position: relative;
        padding: 3px 10px 3px;
      }
      main h2:before {
        content: "";
        position: absolute;
        left: 0;
        bottom: -3px;
        background: linear-gradient(to left, #00aaff, #00ff00);
        height: 1px;
        width: 100%;
      }
      main h2:after {
        content: "";
        position: absolute;
        left: 0px;
        background: #aaff00;
        width: 5px;
        bottom: 0;
        top: 0;
      }
      main h3 {
        color: #00ff00;
      }
      main h4 {
        color: #00ffaa;
      }
      main strong {
        color: #ffaa00;
      }
      main strong:before {
        content: "”";
      }
      main strong:after {
        content: "”";
      }
      footer {
        background: #000;
        color: #fff;
      }
      footer > small {
        padding: 10px;
        text-align: center;
        display: inline-block;
        width: 100%;
      }

      .modal {
        display: none;
        z-index: 100;
      }
      .modal:target {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        display: flex;
        justify-content: center;
        align-items: center;
      }
      .modal > div {
        animation: modalShow 1s linear 0s;
        --modal_width: 70vw;
        --modal_position: relative;
      }
      @keyframes modalShow {
        from {
          opacity: 0;
          transform: scale(0);
        }
        to {
          opacity: 1;
          transform: scale(1);
        }
      }
      .modal > div > a {
        position: var(--modal_position);
        display: block;
      }
      .modal > div > a::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        background-color: rgba(0,0,0,0.5);
        width: 100%;
        height: 100%;
        z-index: -1;
      }
      .modal > div > a::after {
        content: "✕";
        color: #FFF;
        background-color: #FF0000;
        position: absolute;
        top: -13px;
        right: -13px;
        width: 25px;
        height: 25px;
        z-index: 1;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
      }
      .modal > div > div {
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        overflow: hidden;
        border-radius: 5px;
        border: 1px solid #FFF;;
      }
      .modal > div > div > span {
        position: var(--modal_position);
        width: var(--modal_position);
        background: #00ff00;
        min-height: 20px;
        font-size: 20px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        border-bottom: #FFF;
        color: #FFF;
        word-break: break-all;
      }
      .modal > div > div > div {
        position: var(--modal_position);
        width: var(--modal_width);
        height: 60vh;
        padding: 15px;
        overflow: auto;
      }

      @media only screen and (max-width: 600px) {
        header {
          font-size: 13px;
        }
        main img {
          witdh: 100%;
        }
        show > div {
          right: 10%;
        }
      }
    </style>
    <script src="../assets/scripts/scripts.js"></script>
    <!--
        *----------------------------------
        |  ThisStyleVersion: 1.0.1      |
        |  © 2021-2025 ISAMI ABE        |
        |  LastUpdate: 2025-05-22       |
        |  License: MIT License         |
        |  programming4h                |
      ----------------------------------*
    -->
  </head>
  <body>
    <show>
      <div>
        <p>プシューわんこ/（阿部勇生）がプログラミングを始めてから４周年立ったことから立ち上がった記念サイトです🤔</p>
        <h3>プログラミング４周年記念サイト</h3>
      </div>
      <img src="https://user0514.cdnw.net/shared/img/thumb/TSU413024_TP_V.jpg"/>
    </show>
    <header>
      <h1>プログラミング４周年記念サイト</h1>
    </header>
    <main>
      <h2>ときが経つのはあっという間</h2>
      <a href="#zoom_img-1"><img src="../assets/images/begin_programming.jpeg"/></a>
      <div class="modal" id="zoom_img-1">
        <div>
          <a href="#MC"></a>
          <div>
            <span>拡大画像</span>
            <div>
              <img style="width: 100%;" src="../assets/images/begin_programming.jpeg"/>
            </div>
          </div>  
        </div>
      </div>
      <p>私がプログラミングを始めたのは<strong>高校の最後の頃</strong>。しかしそれ以前の頃にきっかけを作る出来事がありました、そしてそれはプシューサービスの始まりの地といっても過言でもないと思っています、私が中学生のころ彼（I,H）は作曲家を目指していてホームページがあり私はホームページにとても強い関心を持ちました、そのため彼にどうやって作ったか聞くとGoogleサイトを使うと本当はサーバーやHTMLっていうプログラミングみたいなものを勉強しないとできないけど簡単に作れることを知りました、そして作ったのがシャークページこのサイトはインターネットから拾った画像や函館の旅行に行った時の画像とかを貼っただけのサイトでした。当時も中学から特別支援学校に通ってましたが、高校でも特別支援学校に通学してました、そしてそこでは目を疑うことにプログラミングはおろか理科という科目すらありませんし、社会に出ることはとても辛いものだと教えられながら作業という名の仕事訓練的なのを毎週やらされてました、言うてしまえば軍隊の学校のような感じです。</p>
      <p>高校3年になり、「<strong>私の学校生活はこのままで良いのだろうか</strong>」と思い、理科の得意な先生を探し、小学6年までしかない理科の知識を高校レベルににすべく休み時間のすべてを費やしたり、数字（算数です）で掛け算のひっ算の手順や割り算のひっ算の手順を覚えることに専念しました。とにかく<strong>掛け算から割り算</strong>を勉強しまくりました。</p>

      <h3>高校2年後半（2021年後半）</h3>
      <p>アプリを作りたいと思い、父が持っていたHTMLの本を使いながらプログラミングを開始。ローカル環境でサーバーを立て、HTMLにメディアを埋め込む試みをしました。</p>
      <p>学校内や外でアクセスしたかったので、<strong>動的グローバルIPを使用</strong>。しかしIPの変動が不便で、DO!.jpを試すが有料になるのが嫌で調べた結果、<strong>mydns.jsに出会い、IP更新が簡単に</strong>なりました。</p>

      <h3>2022年</h3>
      <a href="#zoom_img-2"><img src="../assets/images/202200.jpeg"/></a>
      <div class="modal" id="zoom_img-2">
        <div>
          <a href="#MC"></a>
          <div>
            <span>拡大画像</span>
            <div>
              <img style="width: 100%;" src="../assets/images/202200.jpeg"/>
            </div>
          </div>  
        </div>
      </div>
      <p>HTMLと簡単なCSS（背景色変更）を覚え、<strong>外部CSSを使用したスタイル付け</strong>を習得。OthelloJSで作られたHTMLアプリを埋め込んだりしながら、デザインを充実させました。</p>
      <p><strong>2022/03/09 高校卒業</strong>。</p>
      <p>就職後、農業の仕事で時間が取れずも、CSSの改良を続けました。</p>

      <h4>2022年5月</h4>
      <p>レスポンシブデザインに憧れ挑戦。ハンバーガーメニューの作成に苦戦。</p>

      <h4>2022年7月</h4>
      <a href="#zoom_img-3"><img src="../assets/images/202207.jpeg"/></a>
      <div class="modal" id="zoom_img-3">
        <div>
          <a href="#MC"></a>
          <div>
            <span>拡大画像</span>
            <div>
              <img style="width: 100%;" src="../assets/images/202207.jpeg"/>
            </div>
          </div>  
        </div>
      </div>
      <p><strong>初めてJSに触れ、プログラミングの難しさに圧倒</strong>される。同時期にPHPにも挑戦するが、「まるでちんぷんかんぷん」。</p>
      <p>AndroidStudioやVue.jsに挑戦。JSなしでハンバーガーメニューを作る方法を探し、CSSのみで作るレシピを発見し成功。</p>

      <h4>2022年9月〜10月</h4>
      <a href="#zoom_img-4"><img src="../assets/images/202212.jpeg"/></a>
      <div class="modal" id="zoom_img-4">
        <div>
          <a href="#MC"></a>
          <div>
            <span>拡大画像</span>
            <div>
              <img style="width: 100%;" src="../assets/images/202212.jpeg"/>
            </div>
          </div>  
        </div>
      </div>
      <p>テンプレート改造に苦戦しながらもCSSの理解を深め、<strong>テンプレなしでUIを作成できるように</strong>なった。</p>

      <h3>2023年</h3>
      <a href="#zoom_img-5"><img src="../assets/images/202300.jpeg"/></a>
      <div class="modal" id="zoom_img-5">
        <div>
          <a href="#MC"></a>
          <div>
            <span>拡大画像</span>
            <div>
              <img style="width: 100%;" src="../assets/images/202200.jpeg"/>
            </div>
          </div>  
        </div>
      </div>
      <h4>2023年3月</h4>

      <p>会社の愚痴をこぼすための<strong>BBSを開設</strong>するが、同僚が集まらず。その過程で<strong>CSSだけで作れるモーダルウィンドウの存在を知る</strong>。</p>

      <h4>2023年5月</h4>
      <p>会社を退職し、プログラミング言語の習得が加速。</p>

      <h4>2023年6月</h4>
      <p><strong>C言語をYouTubeで学び、JSの理解が飛躍的に向上</strong>。これによりプシューマルチアプリにJSを導入。</p>
      <p>ChatGPTを活用し、<strong>アイコン移動とデータ保持を実装</strong>。</p>

      <h4>2023年7月</h4>
      <p>PHPを本格的に習得し、テンプレBBSを改良。C言語を習得した流れで<strong>Javaにも挑戦し、コンストラクタやクラスの概念を理解</strong>。</p>

      <h4>2023年12月</h4>
      <p>PHPが得意になり、<strong>何も見ずに書けるように</strong>なる。</p>

      <h3>2024年</h3>
      <p>フレームワークを中心に学習し、特に<strong>Next.jsの習得が大きな進展</strong>。</p>

      <h4>現在</h4>
      <p>プログラミングを続け、さらなるスキル向上を目指す。</p>
    </main>
    <footer>
      <small>&copy; All made By ISAMI ABE</small>
    </footer>
  </body>
</html>