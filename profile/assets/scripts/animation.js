/*****************************************
  *----------------------------------
  |  ThisStyleVersion: 1.3.0      |
  |  © 2021-2026 ISAMI ABE        |
  |  LastUpdate: 2026-06-21       |
  |  License: MIT License         |
  |  PusyuuWanko/ of profile      |
----------------------------------*
******************************************/

(function () {
  'use strict';

/*
  やっぱりいまいち、しかし的確に指示を投げればある程度補正は効く、コパイロットの方が2026年現在geminiに毛が生えた程度でクロードとは劣る感じ、けど高いよね年間でやってるけど。。。。
  やってみて感じたことは生成してもらったはいいけどコードの把握をしないとブラックボックス過ぎて何が原因かわからなくなるため一長一短なのとテセウスの船的なのをやっぱ感じてしまっていいんだか悪いんだかなと、けどアジャイルで変更してく観点ではAI一色になりにくいことはたしかね
  まぁ何を食べたいかだけ決めてオーダーメイドで作ってもらうというのは他人に任せようがAIに任せようが大して変わらないともいえるが、やっぱためらいはあるよねｗ、大して書けないのにさｗ
*/

  /*
   * animation.js — プシューの世界観 プログレッシブエンハンスメント
   *
   * セクション表示の設計（大セクション問題への対応）:
   *
   *   _psyFirstSeen: false → セクションをまだ一度も見ていない（非表示状態）
   *   _psyFirstSeen: true  → 一度でも表示した（以降は絶対に非表示にしない）
   *   _psyEntering:  false → 現在ビューポート外（次の入場でアニメーション再生可能）
   *   _psyEntering:  true  → 現在ビューポート内
   *
   *   初回入場（_psyFirstSeen: false）
   *     → visibility:hidden から clip-path ワイプ + li スタガー（フルリビール）
   *
   *   再入場（_psyFirstSeen: true かつ _psyEntering: false）
   *     → セクションを隠さずスキャンビーム + タイトル輝度フラッシュのみ
   *     → 大セクション問題「上部に戻らないと再表示されない」を解消
   *
   *   退場（isIntersecting: false）
   *     → _psyEntering = false にするだけ（セクションは非表示にしない）
   *     → 例外: _psyFirstSeen: false のまま退場した場合は visibility:hidden を維持
   */

  var styleEl = document.createElement('style');
  styleEl.innerHTML = '@keyframes _psy_barAppear{from{opacity:0}to{opacity:1}}';
  document.head.appendChild(styleEl);

  function init() {
    heroEntrance();
    sectionReveal();
    scrollProgress();
    navHighlight();
    tabTransition();
    cursorGlow();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* ─────────────────────────────────────────────
   * ヒーローセクション 入場アニメーション
   * ───────────────────────────────────────────── */
  function heroEntrance() {
    var avatar = document.querySelector('.hero-avatar');
    if (avatar && avatar.animate) {
      avatar.animate([
        { clipPath: 'inset(0 0 100% 0 round 50%)', opacity: '0.3' },
        { clipPath: 'inset(0 0 0% 0 round 50%)',   opacity: '1'   },
      ], { duration: 720, delay: 80, fill: 'backwards', easing: 'cubic-bezier(0.16,1,0.3,1)' });
    }

    var eyecatch = document.querySelector('.hero-eyecatch');
    if (eyecatch && eyecatch.animate) {
      eyecatch.animate([
        { opacity: '0', letterSpacing: '0.6em' },
        { opacity: '0.65', letterSpacing: '0.3em' },
      ], { duration: 900, delay: 200, fill: 'backwards', easing: 'ease-out' });
    }

    /* タイプライター */
    var nameEl = document.querySelector('.hero-name');
    var charInterval = 85;
    var typeDelay = 480;
    if (nameEl) {
      var nameText = nameEl.textContent;
      nameEl.textContent = '';
      nameEl.style.opacity = '1';
      nameEl.style.minHeight = '1.4em';
      var cursor = document.createElement('span');
      cursor.setAttribute('style', 'border-right:2px solid #00ff00;display:inline-block;width:0;overflow:visible;animation:_psy_barAppear 0.5s step-end infinite alternate;');
      nameEl.appendChild(cursor);
      nameText.split('').forEach(function (char, i) {
        setTimeout(function () {
          nameEl.insertBefore(document.createTextNode(char), cursor);
          if (i === nameText.length - 1) {
            setTimeout(function () {
              if (cursor.animate) {
                cursor.animate([{ opacity: '1' }, { opacity: '0' }], { duration: 400, fill: 'forwards' });
              }
              setTimeout(function () { if (cursor.parentNode) cursor.parentNode.removeChild(cursor); }, 400);
            }, 700);
          }
        }, typeDelay + i * charInterval);
      });
    }

    [
      ['.hero-realname', typeDelay + 300],
      ['.hero-catch',    typeDelay + 480],
      ['.hero-desc',     typeDelay + 640],
    ].forEach(function (item) {
      var el = document.querySelector(item[0]);
      if (!el || !el.animate) return;
      el.animate([
        { opacity: '0', transform: 'translateY(18px)' },
        { opacity: '1', transform: 'translateY(0)' },
      ], { duration: 500, delay: item[1], fill: 'backwards', easing: 'cubic-bezier(0.22,1,0.36,1)' });
    });
  }

  /* ─────────────────────────────────────────────
   * 初回リビール（visibility:hidden から表示）
   *
   * 一度だけ呼ばれる。_psyFirstSeen が true になった後は
   * このセクションを再び非表示にすることはない。
   * ───────────────────────────────────────────── */
  var sectionObserver = null;

  function revealSection(sec) {
    if (sec._psyAnim) {
      try { sec._psyAnim.cancel(); } catch (e) {}
      sec._psyAnim = null;
    }
    if (sec._psyTimers) {
      sec._psyTimers.forEach(function (t) { clearTimeout(t); });
    }
    sec._psyTimers = [];

    /* visibility:visible に戻す（opacity:0 のままなのでフラッシュなし） */
    sec.style.visibility = 'visible';

    /* clip-path ワイプ + フェードイン */
    if (sec.animate) {
      var anim = sec.animate([
        { clipPath: 'inset(0 100% 0 0)', opacity: '0.1' },
        { clipPath: 'inset(0 0% 0 0)',   opacity: '1'   },
      ], { duration: 520, easing: 'cubic-bezier(0.16,1,0.3,1)', fill: 'forwards' });
      sec._psyAnim = anim;
      anim.onfinish = function () {
        sec.style.opacity = '1';
        sec.style.clipPath = '';
        try { anim.cancel(); } catch (e) {}
        sec._psyAnim = null;
      };
    } else {
      sec.style.opacity = '1';
    }

    /* スキャンビーム */
    appendScanBeam(sec, 440);

    /* li スタガー */
    var timers = [];
    sec.querySelectorAll('.profile-list > li').forEach(function (li, i) {
      var t = setTimeout(function () {
        li.style.opacity = '1';
        if (li.animate) {
          li.animate([
            { opacity: '0', transform: 'translateX(-18px)' },
            { opacity: '1', transform: 'translateX(0)' },
          ], { duration: 340, easing: 'cubic-bezier(0.22,1,0.36,1)' });
        }
      }, 260 + i * 58);
      timers.push(t);
    });
    sec._psyTimers = timers;
  }

  /* ─────────────────────────────────────────────
   * 再入場エフェクト（セクションを隠さない）
   *
   * 大セクション問題の解決策。
   * セクションはすでに visible なので opacity は触らず
   * スキャンビーム + タイトル輝度フラッシュだけ流す。
   * → スクロール位置によらず即座に視覚フィードバックが得られる
   * ───────────────────────────────────────────── */
  function reenterSection(sec) {
    appendScanBeam(sec, 500);

    /* セクション背景の短いハイライト */
    if (sec.animate) {
      sec.animate([
        { background: 'rgba(0,150,255,0.07)' },
        { background: 'transparent' },
      ], { duration: 700, easing: 'ease-out' });
    }

    /* タイトル輝度フラッシュ（gradient text にも filter は効く） */
    var title = sec.querySelector('.section-title');
    if (title && title.animate) {
      title.animate([
        { filter: 'brightness(1)' },
        { filter: 'brightness(2.2)' },
        { filter: 'brightness(1)' },
      ], { duration: 500, easing: 'ease-in-out' });
    }
  }

  /* ─────────────────────────────────────────────
   * スキャンビーム（共通ユーティリティ）
   * ───────────────────────────────────────────── */
  function appendScanBeam(sec, duration) {
    var old = sec.querySelector('._psy_beam');
    if (old && old.parentNode) old.parentNode.removeChild(old);
    var beam = document.createElement('div');
    beam.className = '_psy_beam';
    beam.setAttribute('style', [
      'position:absolute', 'left:0', 'right:0', 'top:0',
      'height:2px',
      'background:linear-gradient(to right,transparent,rgba(0,170,255,0.55),rgba(0,255,170,0.4),transparent)',
      'pointer-events:none', 'z-index:10',
    ].join(';'));
    sec.appendChild(beam);
    if (beam.animate) {
      beam.animate(
        [{ top: '0%', opacity: '0.9' }, { top: '98%', opacity: '0.2' }],
        { duration: duration, easing: 'ease-in' }
      ).onfinish = function () { if (beam.parentNode) beam.parentNode.removeChild(beam); };
    } else {
      if (beam.parentNode) beam.parentNode.removeChild(beam);
    }
  }

  /* ─────────────────────────────────────────────
   * IntersectionObserver によるセクション管理
   * ───────────────────────────────────────────── */
  function sectionReveal() {
    if (!window.IntersectionObserver) return;

    var sections = document.querySelectorAll('.profile-section');

    /* 初期化 */
    sections.forEach(function (sec) {
      sec._psyTimers = [];
      sec._psyAnim   = null;
      var rect   = sec.getBoundingClientRect();
      var inView = rect.bottom > 0 && rect.top < window.innerHeight;
      if (inView) {
        /* すでに画面内 → 表示済み扱い、アニメーションなし */
        sec._psyFirstSeen = true;
        sec._psyEntering  = true;
      } else {
        /* 画面外 → 非表示にして初回リビールを待つ */
        sec._psyFirstSeen = false;
        sec._psyEntering  = false;
        sec.style.visibility = 'hidden';
        sec.style.opacity    = '0';
        sec.querySelectorAll('.profile-list > li').forEach(function (li) {
          li.style.opacity = '0';
        });
      }
    });

    sectionObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        var sec = entry.target;
        if (entry.isIntersecting) {
          if (!sec._psyFirstSeen) {
            /* 初回入場 */
            sec._psyFirstSeen = true;
            sec._psyEntering  = true;
            revealSection(sec);
          } else if (!sec._psyEntering) {
            /* 再入場（セクションは visible のまま） */
            sec._psyEntering = true;
            reenterSection(sec);
          }
        } else {
          /* 退場: _psyEntering を false にするだけ
           * セクションを非表示にしないことで大セクション問題を回避 */
          sec._psyEntering = false;
        }
      });
    }, {
      threshold: 0,
      rootMargin: '0px',
    });

    sections.forEach(function (sec) { sectionObserver.observe(sec); });
  }

  /* ─────────────────────────────────────────────
   * スクロール進捗バー
   * ───────────────────────────────────────────── */
  function scrollProgress() {
    var bar = document.createElement('div');
    bar.setAttribute('style', [
      'position:fixed', 'top:0', 'left:0', 'height:2px', 'width:0%',
      'background:linear-gradient(to right,#00aaff,#00ff00,#aaff00)',
      'z-index:9999', 'pointer-events:none',
      'will-change:width', 'transition:width 0.08s linear',
      'animation:_psy_barAppear 0.6s ease',
    ].join(';'));
    document.body.appendChild(bar);

    var ticking = false;
    window.addEventListener('scroll', function () {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(function () {
        var top = document.documentElement.scrollTop || document.body.scrollTop;
        var h   = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        bar.style.width = (h > 0 ? (top / h * 100) : 0).toFixed(2) + '%';
        ticking = false;
      });
    }, { passive: true });
  }

  /* ─────────────────────────────────────────────
   * サイドバーナビ アクティブリンクハイライト
   * ───────────────────────────────────────────── */
  function navHighlight() {
    if (!window.IntersectionObserver) return;
    var current = null;
    function activate(link) {
      if (current) {
        current.style.color = '';
        current.style.borderLeftColor = '';
        current.style.background = '';
        current.style.fontWeight = '';
      }
      if (link) {
        link.style.color = '#00ffaa';
        link.style.borderLeftColor = '#00ffaa';
        link.style.background = 'rgba(0,255,170,0.08)';
        link.style.fontWeight = 'bold';
      }
      current = link;
    }
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var id   = entry.target.id;
        if (!id) return;
        var link = document.querySelector('.sidebar a[href="#' + id + '"]');
        if (link) activate(link);
      });
    }, { rootMargin: '-5% 0px -55% 0px', threshold: 0 });
    document.querySelectorAll('.profile-section, .footer-section').forEach(function (el) {
      observer.observe(el);
    });
  }

  /* ─────────────────────────────────────────────
   * タブ切り替え グリッチフェード
   *
   * タブ切替後にビューポート内のセクションを即時処理
   * （Observer の非同期コールバックを待たない）。
   * ───────────────────────────────────────────── */
  function tabTransition() {
    document.querySelectorAll('.tab-ctrl').forEach(function (radio) {
      radio.addEventListener('change', function () {
        var targetId = this.id === 'tab-majority' ? 'summary-group' : 'worldview-group';
        var group    = document.getElementById(targetId);
        if (!group) return;

        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            if (group.animate) {
              group.animate([
                { opacity: 0,   filter: 'brightness(3) hue-rotate(90deg)' },
                { opacity: 0.4, filter: 'brightness(1.5)' },
                { opacity: 1,   filter: 'brightness(1)'   },
              ], { duration: 320, easing: 'ease-out' });
            }

            group.querySelectorAll('.profile-section').forEach(function (sec) {
              var rect   = sec.getBoundingClientRect();
              var inView = rect.top < window.innerHeight && rect.bottom > 0;
              if (!inView) return;
              if (!sec._psyFirstSeen) {
                sec._psyFirstSeen = true;
                sec._psyEntering  = true;
                revealSection(sec);
              } else if (!sec._psyEntering) {
                sec._psyEntering = true;
                reenterSection(sec);
              }
            });
          });
        });
      });
    });
  }

  /* ─────────────────────────────────────────────
   * カーソル追随グロー（タッチデバイスは除外）
   * ───────────────────────────────────────────── */
  function cursorGlow() {
    if (window.matchMedia('(hover: none)').matches) return;
    var glow = document.createElement('div');
    glow.setAttribute('style', [
      'position:fixed', 'pointer-events:none', 'z-index:2',
      'width:360px', 'height:360px', 'border-radius:50%',
      'background:radial-gradient(circle,rgba(0,170,255,0.06) 0%,transparent 68%)',
      'transform:translate(-50%,-50%)',
      'transition:left 0.14s ease,top 0.14s ease,opacity 0.35s',
      'opacity:0', 'will-change:left,top',
    ].join(';'));
    document.body.appendChild(glow);
    var visible = false;
    document.addEventListener('mousemove', function (e) {
      glow.style.left = e.clientX + 'px';
      glow.style.top  = e.clientY + 'px';
      if (!visible) { glow.style.opacity = '1'; visible = true; }
    }, { passive: true });
    document.addEventListener('mouseleave', function () {
      glow.style.opacity = '0';
      visible = false;
    });
  }

})();
