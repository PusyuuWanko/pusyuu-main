(() => {
  'use strict';

  // お知らせのプッシュ通知を、この端末で受け取るかどうかのボタン。
  // 実際の購読処理は共通ヘルパー(push-subscribe.js の window.PusyuuPush)に任せます。
  //
  // 【このファイルはmain専用です】pusyuusystem/scripts/ は他のプロダクトからも
  // 読まれる置き場ですが、これはpusyuuwanko.com(main)自身の画面のためのものです。
  // 他所から読み込んでも、購読先のオリジンが違うので意味がありません。
  //
  // 【中身をJSが丸ごと組み立てる理由】購読も通知の許可も、操作そのものがJS前提です。
  // no-JS環境向けに「非表示のプレースホルダーをJSが表示する」形にすると、JSが無い人に
  // 押せないボタンを見せることになります。JSが無ければ最初から何も無い、が正しい姿です。
  // (RSSでの受け取りはJS無しで成立するので、そちらはPHPが常に描画しています)
  //
  // 【見出しもここで作ります】隣にPHPが描く「📰 RSSで受け取る」があり、この2つは
  // 「どちらで受け取るか」を選ぶ対の選択肢です。こちらだけ見出しが無いと、状態文と
  // ボタンがRSSの説明にぶら下がっているように見え、選択肢に見えません。
  //
  // 【読み込む場所】index.phpの</body>直前から読んでいます。<head>から読むと
  // 下のgetElementById()が必ずnullになり、何も描かないまま終わります(画面には
  // RSSの案内だけが残り、エラーも出ません)。移動させないでください。
  //
  // 【許可を求めるのは必ずクリックの中から】ブラウザは、利用者の操作を伴わない
  // 通知許可の要求を自動的に拒否します。しかも拒否された記録だけが残り、以後は
  // 正しい手順を踏んでも求められなくなります。読み込み時に呼ぶ形へ変えないこと。

  const mount = document.getElementById('main-push-settings');
  if (!mount) { return; }

  function line(text, isError) {
    const p = document.createElement('p');
    p.textContent = text;
    if (isError) { p.style.color = '#ff5555'; }
    return p;
  }

  // 対応していない端末でも見出しと理由は出します。「何も無い」より
  // 「この端末では使えない、だからRSSがある」と分かる方が親切なためです。
  function heading() {
    const h = document.createElement('h4');
    h.textContent = '🔔 プッシュ通知で受け取る';
    mount.appendChild(h);
  }

  function fail(message) {
    mount.innerHTML = '';
    heading();
    mount.appendChild(line(message, true));
    console.error('[main push]', message);
  }

  heading();
  mount.appendChild(line('この端末のブラウザへ直接お知らせを届けます。ページを開いていなくても届き、端末ごとの設定です。'));

  if (!window.PusyuuPush) {
    fail('プッシュ通知の共通スクリプトを読み込めませんでした。ネットワークや広告ブロッカーの影響の可能性があります。RSSでの受け取りは下から設定できます。');
    return;
  }
  if (!window.PusyuuPush.isSupported()) {
    const d = window.PusyuuPush.supportDetails ? window.PusyuuPush.supportDetails() : {};
    const missing = [];
    if (d.secureContext === false) { missing.push('安全な接続(HTTPS)ではありません'); }
    if (d.serviceWorker === false) { missing.push('Service Worker 未対応'); }
    if (d.pushManager === false) { missing.push('Push API 未対応'); }
    if (d.notification === false) { missing.push('通知API 未対応'); }
    const detail = missing.length > 0 ? '(' + missing.join('・') + ')' : '(原因不明)';
    fail('このブラウザ/表示方法ではプッシュ通知に対応していません' + detail
      + '。LINEやX(旧Twitter)などのアプリ内ブラウザで開くと、見た目はChromeと同じでも使えないことがよくあります。'
      + 'この環境でも受け取りたい場合は、下の「RSSで受け取る」が使えます。');
    return;
  }

  const status = line('');
  const btn = document.createElement('button');
  btn.type = 'button';
  mount.appendChild(status);
  mount.appendChild(btn);

  function setStatus(text, isError) {
    status.textContent = text;
    status.style.color = isError ? '#ff5555' : '';
    if (text) { console.log('[main push]', text); }
  }

  function blockedMessage() {
    return 'この端末では通知がブロックされています。ブラウザのアドレスバー左の鍵アイコン'
      + '(またはサイト設定)から、このサイトの通知を「許可」または「確認する」に戻してください。';
  }

  let subscribed = false;

  function render() {
    if (typeof Notification !== 'undefined' && Notification.permission === 'denied') {
      setStatus(blockedMessage(), true);
      btn.hidden = true;
      return;
    }
    btn.hidden = false;
    btn.disabled = false;
    if (subscribed) {
      setStatus('この端末はお知らせを受け取れる状態です。');
      btn.textContent = 'この端末で受け取るのをやめる';
    } else {
      setStatus('この端末はまだ登録されていません。');
      btn.textContent = 'この端末で受け取る';
    }
  }

  // VAPID公開鍵は押された時にだけ取りに行きます(見ただけの人に往復を払わせない)。
  function vapidKey() {
    return fetch('./?push_key=1', { credentials: 'same-origin' })
      .then((r) => r.json())
      .then((d) => (d && d.key) ? d.key : '');
  }

  btn.addEventListener('click', () => {
    btn.disabled = true;
    const wasSubscribed = subscribed;
    setStatus('処理しています...');

    const action = wasSubscribed
      ? window.PusyuuPush.unsubscribe({
          unsubscribeUrl: './',
          extraFields: { push_unsubscribe: '1' },
        })
      : vapidKey().then((key) => {
          if (!key) { return false; }
          return window.PusyuuPush.subscribe({
            swUrl: './?push_sw=1',
            scope: './',
            publicKey: key,
            subscribeUrl: './',
            extraFields: { push_subscribe: '1' },
          });
        });

    Promise.resolve(action).then((ok) => {
      if (ok) {
        subscribed = !wasSubscribed;
        render();
        setStatus(subscribed
          ? 'この端末を登録しました。次にお知らせが追加された時に届きます。'
          : 'この端末の登録を解除しました。');
        return;
      }
      if (!wasSubscribed && typeof Notification !== 'undefined' && Notification.permission === 'denied') {
        setStatus(blockedMessage(), true);
        btn.hidden = true;
        return;
      }
      const detail = window.PusyuuPush.getLastError ? window.PusyuuPush.getLastError() : null;
      setStatus('処理に失敗しました' + (detail ? '(' + detail + ')' : '') + '。しばらくしてからもう一度お試しください。', true);
      btn.disabled = false;
    }).catch((e) => {
      setStatus('予期しないエラーが発生しました: ' + (e && e.message ? e.message : e), true);
      btn.disabled = false;
    });
  });

  window.PusyuuPush.isSubscribed().then((yes) => {
    subscribed = yes;
    render();
  }).catch(() => { render(); });
})();
