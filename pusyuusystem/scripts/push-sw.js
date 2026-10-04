// プシューサービス(main)のService Worker。お知らせのプッシュ通知の受け口です。
//
// 【なぜ中身がプッシュに載ってこないのか】通知APIは「中身の無い(empty payload)
// プッシュ」だけを送ります。本文をサーバ→ブラウザ間で暗号化して運ぶには
// openssl_pkey_derive()(PHP 8.1以降)が要り、このサーバのPHPが8.0の可能性が
// あるためです。そこでSWはpushイベントで起こされたら、自分自身のオリジンへ
// fetch()して中身を取りに行きます(同一オリジンなのでこの端末のCookieがそのまま
// 乗り、この端末宛ての分だけが返ります)。
// 詳しくは apis/pusyuu_push/PUSH_INTEGRATION_SPEC.md 2節。
//
// 【この置き場所について】ファイルはここ(pusyuusystem/scripts/)に置いたままで、
// 配信は /index.php?push_sw=1 が行います。そちらがService-Worker-Allowed: / を
// 付けるので、ルート直下へファイルを置かなくても scope: './' が成立します。
// 他のプロダクトとは共有できません(別オリジンのSWは登録できないため)。

self.addEventListener('push', (event) => {
  event.waitUntil(
    fetch('./?push_inbox=1', { credentials: 'same-origin' })
      .then((r) => r.json())
      .then((data) => Promise.all((data.items || []).map((n) =>
        self.registration.showNotification(n.title, {
          body: n.body,
          tag: n.tag || undefined,
          icon: n.icon || undefined,
          data: { url: n.url || '/' },
        })
      )))
      .catch(() => {})
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const url = (event.notification.data && event.notification.data.url) || '/';
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((list) => {
      // 既に開いているタブがあればそれを前に出します。同じサイトのタブを
      // 増やさないためで、開いていない時だけ新しく開きます。
      for (const c of list) { if ('focus' in c) return c.focus(); }
      return clients.openWindow(url);
    })
  );
});

// 購読はブラウザの都合で作り直されることがあります(端末の更新等)。その時に
// 黙って通知が止まらないよう、新しい購読を登録し直します。
self.addEventListener('pushsubscriptionchange', (event) => {
  event.waitUntil(
    self.registration.pushManager.getSubscription().then((sub) => {
      if (!sub) { return; }
      return fetch('./', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          push_subscribe: '1',
          subscription: JSON.stringify(sub.toJSON()),
        }).toString(),
      });
    }).catch(() => {})
  );
});
