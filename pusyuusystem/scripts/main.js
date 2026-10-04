/*****************************************
  *----------------------------------
  |  ThisStyleVersion: 1.3.2      |
  |  © ISAMI ABE                  |
  |  LastUpdate: 2026-07-26       |
  |  License: MIT License         |
  |  (^U^)PusyuuJsDesu            |
----------------------------------*
******************************************/

document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;//[...document.getElementsByTagName("body")][0];
  body.style.overflow = "unset";
  const hamNavBtn = document.getElementById("menu-btn-check");
  const hamNavHref = Array.prototype.slice.call(document.querySelectorAll(".menu-content > div > nav > ul > li > a"));
  hamNavBtn.addEventListener("click", function() {
    if (hamNavBtn.checked === true) {
      body.style.overflow = "hidden";
    } else {
      body.style.overflow = "unset";
    }
  })
  hamNavHref.forEach(function(item) {
    item.addEventListener("click", function() {
      hamNavBtn.checked = false;
      body.style.overflow = "unset";
    });
  });
  window.addEventListener("popstate", function() {
    if (window.location.hash !== "#google_vignette") {
      if (window.location.hash.startsWith("#zoom") || window.location.hash === "#notifications") {
        body.style.overflow = "hidden";
      } else {
        body.style.overflow = "unset";
      }
    }
  });

  const style = document.createElement('style');
  style.textContent = `
    .rect-flash {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #00ff00;
      opacity: 0;
      pointer-events: none;
      z-index: 100000;
      animation: flash-sequence 0.5s steps(1) forwards;
    }
    @keyframes flash-sequence {
      0% { opacity: 1; width: 80%; height: 1px; }
      16% { opacity: 0; }
      33% { opacity: 1; width: 2px; height: 80%; }
      50% { opacity: 0; }
      66% { opacity: 1; width: 80%; height: 1px; }
      82%, 100% { opacity: 0; }
    }
    @keyframes content-reveal {
      0% { opacity: 0; transform: scale(0); }
      100% { opacity: 1; transform: scale(1); }
    }
    
    @keyframes zoom-out {
      form { scale: 1; opacity: 1; }
      to { scale: 0; opacity: 0; }
    }
  `;
  let closeLoaded = false;
  document.head.appendChild(style);
  Array.prototype.slice.call(document.querySelectorAll("#loading > div > div > div > form")).forEach(function (loadNoJs) {
    Array.prototype.slice.call(document.querySelectorAll("#loading > div > div > div")).forEach(function (loadContent) {
      const loadingEle = document.getElementById("loading");
      loadNoJs.style.display = "none";
      body.style.overflow = "hidden";
      if (loadingEle) {
        const loadingAnime = document.createElement("div");
        loadingAnime.style.position = "fixed";
        loadingAnime.style.inset = "0";
        loadingAnime.style.background = "#000";
        const loadingAnimeContent = document.createElement("div");
        loadingAnimeContent.classList.add("rect-flash");
        loadingAnime.appendChild(loadingAnimeContent);
        loadingEle.appendChild(loadingAnime);
        setTimeout(()=> {
          setTimeout(()=> {
            loadingAnime.style.animation = "zoom-out 0.5s forwards";
            setTimeout(()=> {
              loadingAnime.remove();
            }, 500);
          }, 300);
        }, 550);
        setTimeout(function() {
          const content = document.createElement("div");
          content.style.padding = "10px";
          const contentText = document.createElement("p");
          contentText.textContent = '遅すぎるんじゃー、という方は以下のボタンを押しても進めます。';
          contentText.innerHTML += '<br />';
          contentText.innerHTML += '<span style="color: #f00;">※ただしローディング中に変わりはないため様々コンテンツがうまく表示されない可能性があります。</span>';
          const contentButton = document.createElement("button");
          contentButton.textContent = "ローディング画面を手動で閉じる";
          contentButton.addEventListener("click", function() {
            body.style.overflow = "unset";
            loadingEle.style.display = "none";
            loadNoJs.style.display = "block";
          });
          content.appendChild(contentText);
          content.appendChild(contentButton);
          loadContent.appendChild(content);
        }, 10000);
        window.addEventListener("load", function() {
          setTimeout(function() {
            loadingEle.style.opacity = "0";
            setTimeout(function() {
              body.style.overflow = "unset";
              loadingEle.style.display = "none";
              loadNoJs.style.display = "block";
              setTimeout(feedbackDisp, 20000);
            }, 500);
          }, 3000);
        });
      }
    });
  });

  function feedbackDisp() {
    body.style.overflow = "hidden";
    const feedbackWrap = document.createElement("article");
    const feedback = document.createElement("div");
    const feedbackCloseBtn = document.createElement("button");
    feedbackCloseBtn.style.position = "absolute";
    feedbackCloseBtn.style.top = "10px";
    feedbackCloseBtn.style.right = "10px";
    feedbackCloseBtn.textContent = "✕";
    feedbackCloseBtn.id = "feedbackClose";
    feedback.appendChild(feedbackCloseBtn);
    feedback.style.position = "fixed";
    feedback.style.inset = "20px";
    feedback.style.top = "80px";
    feedback.style.overflow = "auto";
    feedback.style.background = "rgba(255,255,255,0.5)";
    feedback.style.backdropFilter = "blur(10px)";
    feedback.style.webkitBackdropFilter = "blur(10px)",
    feedback.style.borderRadius = "20px";
    feedback.style.overflow = "hidden";
    feedback.style.zIndex = "1000";
    const feedbackContent = document.createElement("div");
    feedbackContent.style.width = "100%";
    feedbackContent.style.height = "100%";
    feedbackContent.style.padding = "20px";
    feedbackContent.style.overflowY = "auto";
    feedbackContent.innerHTML += '<h3>フィードバックにご協力をお願いします。</h3><p>プシューサービスではお客様の意見を尊重したいと考えております、しかしながらお客様のご意思は私自ら聞きにいかなければ聞くことはできないのです、そのためこうしてポップを表示させていただいています事をご理解いただけますと幸いです。</p><p style="color: #00ff00">例えば：この機能が使いにくい、このサービスのここが長文過ぎて嫌い、このサービスのここがいらない、こんなサービスがあったら良いと思う、など、できるだけ具体的に書いてくださると助かります。</p>';
    const feedbackFrame = document.createElement("iframe");
    // main 自身のページなので「/」から始めます(main はどの顔でもホストの一番上に居るため)。
    feedbackFrame.src = "./pusyuusystem/pages/contact";
    feedbackFrame.style.borderWidth = "0";
    feedbackFrame.style.width = "100%";
    feedbackFrame.style.height = "70vh";
    feedbackContent.appendChild(feedbackFrame);
    feedback.appendChild(feedbackContent);
    feedbackWrap.appendChild(feedback);
    body.appendChild(feedbackWrap);
    setTimeout(function () {
      document.getElementById("feedbackClose").addEventListener("click", function() {
        body.style.overflow = "unset";
        feedbackWrap.innerHTML = "";
      });
    }, 1000);
  }

  const draggableItems = document.querySelectorAll('.draggable-item');

  let draggedItem = null;

  // ドラッグ開始時の処理
  function dragStart(e) {
    draggedItem = this;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.innerHTML);
  }

  // ドラッグ中の処理
  function dragOver(e) {
    if (e.preventDefault) {
      e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    return false;
  }

  // ドロップ時の処理
  function drop(e) {
    if (e.stopPropagation) {
      e.stopPropagation();
    }

    if (draggedItem !== this) {
      draggedItem.innerHTML = this.innerHTML;
      this.innerHTML = e.dataTransfer.getData('text/html');
      updateLocalStorageOrder();
    }

    return false;
  }

  // ドラッグ要素にイベントリスナーを追加
  draggableItems.forEach(item => {
    item.addEventListener('dragstart', dragStart);
    item.addEventListener('dragover', dragOver);
    item.addEventListener('drop', drop);
  });

  // ローカルストレージの順番を更新
  function updateLocalStorageOrder() {
    const appLinkElements = document.querySelectorAll('.draggable-item');
    const order = Array.from(appLinkElements).map(item => item.outerHTML);
    localStorage.setItem('appOrder', JSON.stringify(order));
  }

  // ローカルストレージから順番を復元
  function restoreOrderFromLocalStorage() {
    const appOrder = JSON.parse(localStorage.getItem('appOrder'));
    if (appOrder) {
      const appContainer = document.querySelector('.yokoori');
      appContainer.innerHTML = appOrder.join('');
      addDragListeners(); // ドラッグアンドドロップのリスナーを追加
    }
  }

  // ドラッグ要素にイベントリスナーを再度追加
  function addDragListeners() {
    const draggableItems = document.querySelectorAll('.draggable-item');
    draggableItems.forEach(item => {
      item.addEventListener('dragstart', dragStart);
      item.addEventListener('dragover', dragOver);
      item.addEventListener('drop', drop);
    });
  }

  // ページ読み込み時にローカルストレージから順番を復元
  restoreOrderFromLocalStorage(); // ローカルストレージからの復元を行う

  const eventDispEle = document.getElementById("event");
  function eventDisplayLogic(inHtml = null) {
    if (inHtml === null) {
      eventDispEle.innerHTML = "<p style='color: #ff0000'>開発者の設定ミス：正月カウントダウン機能の値が設定されていません</p>";
    }
    eventDispEle.innerHTML = inHtml;
  }
  eventDisplayLogic("残念、現在プシューイベントはないです。( ﾉД`)ｼｸｼｸ…");
  eventDispEle.style.color = "#00ff00";
  eventDispEle.style.fontSize = "30px";

  const date = new Date();
  const month = date.getMonth() + 1;  // Month is 0-indexed, so add 1
  const day = date.getDate();  // Get the current day of the month
  function nextYearContent() {
    if (confirm((new Date().getFullYear() - 1) + "が終わりやがったわ。。。、どう思います？")) {
      if (confirm("せっかくですしPIPSに書き込みしませんか😆？")) {
        // PIPSの住所は main のページ(PHP)が台帳から決めて、script タグの data-pips-url で渡します。
        // 【ドメインを書き戻さないこと】分からないときは移動しません。
        var pipsTag = document.querySelector("script[data-pips-url]");
        var pipsUrl = pipsTag ? pipsTag.getAttribute("data-pips-url") : "";
        if (pipsUrl) {
          window.location.href = pipsUrl;
        }
      } else {
        alert("えー、書き込んでくれないのですか😭？、とても悲しい」です。\n\n気が向いたらアクセスしてみてくださいね！！");
      }
    } else {
      alert("そんな向きになってキャンセルしなくたって、いいじゃん。。。");
    }
    eventDisplayLogic(
      '<strong>明けましておめでとう！！<span style="color: #D81F35;">🎉</span><span style="color: #FFFFFF;">🎉</span><span style="color=: #D81F35;">🎉</span><span style="color: #FFFFFF;">🎉</span></strong><p style="color: #00aaff;">不束者の開発者、兼、管理者ですが、これからもプシューサービスをよろしくお願いいたします😊。</p>'  // Show "Happy New Year" when the countdown ends
    );
  }
  if (month === 12 && day === 31) {
    // Set the target time to midnight (start of next year)
    const targetDate = new Date(date.getFullYear() + 1, 0, 1, 0, 0, 0, 0);  // January 1st, 00:00:00

    function timeHandle() {
      const now = new Date();
      const timeDiff = targetDate - now;  // Calculate the difference between current time and target time
      const hours = Math.floor(timeDiff / (1000 * 60 * 60));  // Get hours
      const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));  // Get minutes
      const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);  // Get seconds
      if (timeDiff <= 0) {
        clearInterval(countdownInterval);
        nextYearContent();
        return;
      }
      let disp = hours.toString().padStart(2, "0") + " : " +
      minutes.toString().padStart(2, "0") + " : " +
      seconds.toString().padStart(2, "0");  // Format the time
      // Update the HTML with the current countdown
      eventDisplayLogic(
        '<p style="color: #00aaff;">皆様お疲れ様です、<span style="color: #aaff00; text-shadow: 2px 2px; 3px;">そして今年もお世話になりました🤝</span>、' + date.getFullYear() + ' 年が終わるまで残り：</p><strong>' +  disp + '</strong><p>です😭。</p>'
      );
    }
    eventDispEle.style.fontSize = "25px";

    const countdownInterval = setInterval(function () {
      timeHandle();
    }, 1000);
  } else if (month === 1 && day <= 7) {
    nextYearContent();
  } else {
    ;
  }
 
  const showEle = document.getElementsByTagName("show")[0];
  var showVideosEle = Array.prototype.slice.call(document.querySelectorAll("show > video"));
  var buttonColor = "rgba(200,255,200,0.3)";
  var soundEle = document.createElement("button");
  soundEle.innerText = "🔇";
  soundEle.style.position = "absolute";
  soundEle.style.bottom = "15px";
  soundEle.style.right = "15px";
  soundEle.style.padding = "8px";
  soundEle.style.fontSize = "23px";
  soundEle.style.background = buttonColor;
  soundEle.style.backdropFilter = "blur(5px)";
  soundEle.style.borderWidth = "0";
  soundEle.style.color = "#fff";
  soundEle.style.cursor = "pointer";
  soundEle.style.borderRadius = "10px";
  soundEle.addEventListener("click", function() {
    soundEle.style.background = "rgba(0,155,255,0.5)";
    setTimeout(function() {
      soundEle.style.background = buttonColor;
    }, 100);
    var nowMuted = !showVideosEle[0].muted;
    showVideosEle.forEach(function (v) { v.muted = nowMuted; });
    soundEle.innerText = nowMuted ? "🔇" : "🔊";
  });
  showEle.appendChild(soundEle);

  // ---- ヒーロー幾何学アンビエント演出 ----
  // 三角形・正方形・ひし形・円のアウトラインを背景動画の上に薄く重ね、
  // anime.jsで常時ゆっくり回転・移動・拡縮させるだけの装飾レイヤー。
  const heroGeoWrap = document.createElement("div");
  Object.assign(heroGeoWrap.style, {
    position: "absolute",
    inset: "0",
    zIndex: "-1",
    overflow: "hidden",
    pointerEvents: "none",
    mixBlendMode: "screen"
  });

  const heroGeoColors = ["#00ff00", "#00aaff", "#ffffff"];
  const heroGeoTypes = ["triangle", "square", "diamond", "circle"];

  function buildHeroGeoShape(type, size, color) {
    const shape = document.createElement("div");
    Object.assign(shape.style, {
      position: "absolute",
      width: size + "px",
      height: size + "px",
      left: Math.random() * 100 + "%",
      top: Math.random() * 100 + "%",
      opacity: String(0.18 + Math.random() * 0.22),
      willChange: "transform"
    });
    switch (type) {
      case "triangle":
        shape.style.clipPath = "polygon(50% 0%, 0% 100%, 100% 100%)";
        shape.style.background = color;
        break;
      case "diamond":
        shape.style.border = "2px solid " + color;
        shape.style.transform = "rotate(45deg)";
        break;
      case "circle":
        shape.style.border = "2px solid " + color;
        shape.style.borderRadius = "50%";
        break;
      default:
        shape.style.border = "2px solid " + color;
        break;
    }
    return shape;
  }

  const heroGeoShapes = [];
  for (let gi = 0; gi < 16; gi++) {
    const geoShape = buildHeroGeoShape(
      heroGeoTypes[gi % heroGeoTypes.length],
      18 + Math.round(Math.random() * 46),
      heroGeoColors[gi % heroGeoColors.length]
    );
    heroGeoWrap.appendChild(geoShape);
    heroGeoShapes.push(geoShape);
  }
  showEle.appendChild(heroGeoWrap);

  if (typeof anime === "function") {
    heroGeoShapes.forEach(function (geoShape, gi) {
      anime({
        targets: geoShape,
        translateX: function () { return anime.random(-60, 60); },
        translateY: function () { return anime.random(-60, 60); },
        rotate: function () { return anime.random(-180, 180); },
        scale: [1, 1 + Math.random() * 0.4],
        duration: 4000 + Math.random() * 4000,
        delay: gi * 120,
        direction: "alternate",
        loop: true,
        easing: "easeInOutSine"
      });
    });
  }

  const animeTagName = "animeBlock";
  const showWrap = document.createElement("div");
  showWrap.style.position = "absolute";
  showWrap.style.width = "100%";
  showWrap.style.height = "100%";
  showWrap.style.inset = "0";
  showWrap.style.zIndex = "-1";
  function objeHandle(char) {
    showWrap.innerHTML = "";
    for (let i = 0; i < 100; i++) {
      const obje = document.createElement("div");
      obje.classList.add(animeTagName);
      //obje.style.background = "#00ff00";
      //obje.style.width = "70px";
      //obje.style.height = "70px";
      obje.textContent = char;
      obje.style.fontSize = "50px";
      obje.style.position = "absolute";
      obje.style.top = "50%";
      obje.style.left = "50%";
      //obje.style.border = "1px solid #000";
      obje.style.transform = "translate(-50%, -50%)";
      showWrap.appendChild(obje);
    }
  }
  //objeHandle("🐸ようこそー、僕たちを押してね");
  showEle.appendChild(showWrap);
  
  let animeId = null;
  let animeState = true;

  function showAnimation(charctor = "🐸ようこそー、僕たちを押してね") {
    if (animeState) {
      objeHandle(charctor);
      setTimeout(function () {
        const animeEle2 = document.querySelectorAll("." + animeTagName);
        animeId = anime({
          targets: animeEle2,
          duration: 3000,
          loop: true,
          translateY: function () {
            return anime.random(-600, 600);
          },
          translateX: function () {
            return anime.random(-1000, 1000);
          },
          rotate: {
            value: 360,
            duration: 1000,
            easing: 'easeInOutSine',
            delay: 2000
          }
        });
      }, 10);
    } else {
      console.log("アニメ停止");
      return;
    }
  }

  const stopAnimeBtn = document.createElement("button");
  Object.assign(stopAnimeBtn.style, {
    position: "absolute",
    top: "15px",
    left: "15px",
    padding: "8px",
    fontSize: "23px",
    cursor: "pointer",
    borderRadius: "10px",
    borderWidth: "0",
    background: buttonColor,
    backdropFilter: "blur(5px)",
    webkitbackdropFilter: "blur(5px)"
  });
  stopAnimeBtn.title = "アニメーションの一時停止と再開";
  stopAnimeBtn.textContent = "🧍‍♂️";
  stopAnimeBtn.addEventListener("click", function() {
    stopAnimeBtn.style.background = "rgba(0,155,255,0.5)";
    setTimeout(function() {
      stopAnimeBtn.style.background = buttonColor;
    }, 100);

    console.log(animeId.paused);
    animeState = animeId.paused;

    if (animeState) {
      animeId.play();
      stopAnimeBtn.textContent = "🧍‍♂️";
    } else {
      animeId.pause();
      stopAnimeBtn.textContent = "🏃‍♂️";
    }
  });
  showEle.appendChild(stopAnimeBtn);

  showAnimation();
  let showEleCount = 0;
  let intervalId = null;
  const loopAnimationMum = 10000;
  showEle.addEventListener("click", function() {
    if (showEleCount > 0) return;
    showEleCount++;

    showAnimation("🤓");
    setTimeout(function () {
      showAnimation("🤔");
      if (!intervalId) {
        intervalId = setInterval(function () {
          showAnimation("?");
          setTimeout(function() {
            showAnimation("🐸押してくれてありがとう！！");
            setTimeout(function() {
              showAnimation("🐸");
            }, loopAnimationMum);
          }, loopAnimationMum);
        }, loopAnimationMum);
      }
    }, loopAnimationMum);

    setTimeout(function() {
      showEleCount = 0;
      clearInterval(intervalId);
      intervalId = null;
    }, 40000);
  });
});