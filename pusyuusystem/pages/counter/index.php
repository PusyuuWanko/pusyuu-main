<?php
/*****************************************
  *----------------------------------
  |  ThisScriptVersion: 2.3.0     |
  |  © 2026 By PusyuuWanko/       |
  |  License: MIT License         |
  |  プシューカウント(アクセスカウンター) |
----------------------------------*
  来訪者数を数えて表示します。入口は3つだけです。
    ・?name=〇〇   … そのカウンターを数えて表示します。HTML(iframe用)か
                     SVG画像(img用)か、どの壁紙かは、カウンターごとの設定で決まります。
    ・?api=〇〇    … 管理画面(oppai)から呼ばれるサーバー間API。合言葉が要ります。
    ・何も無い     … APIとして 404 を返します。

  【カウンターの登録・設定・壁紙は、必ず ?api= を通すこと】count.dat は「名前 →
  カウンター1つぶん」の対応表で、登録・設定の変更・削除、壁紙の追加・削除は
  管理画面からこのAPIで行います。APIの書き込みも来訪の数え上げと同じ鍵
  (count.dat.lock)を通るので、互いに踏み潰しません。**手で count.dat を編集すると
  この鍵を通らない**ので、編集中の来訪が消えます(逆もあります)。
    ・**count.dat に既にある名前だけ**数えます。無い名前は数えず、書き込みもしません
      (URLで受けた名前でそのまま作ると、誰でも好きなだけカウンターを増やせて
      控えが育ち続けるため)。
    ・「今日もう数えたか」の控えはカウンターごとに別です。

  【壁紙】このフォルダの wallpapers/ に置きます(フォルダの外の画像を指さないこと。
  持ち出した時に壁紙だけ消えます)。HTMLでは背景として読ませ、SVGでは画像の中に
  埋め込みます(<img> として読まれたSVGは外の画像を読めないため)。**SVGは壁紙の
  大きさぶん毎回重くなります**。壁紙が見つからない時は、既定のグラデーションで
  表示します(表示を止めるほどの事ではないため)。

  【書き方はメイキーと同じです(壊さないため)】
    ・本体(count.dat)を直接書き換えません。隣に一時ファイルを書いてから rename で
      差し替えます。rename は一瞬で切り替わるので、途中で落ちても本体は前の完成品の
      まま残り、読む側が書きかけを掴むこともありません。壁紙も同じ形で置きます。
    ・同時に来た要求同士の踏み潰しは、鍵専用のファイル(count.dat.lock)で防ぎます。
      **本体そのものに flock しないこと。** 本体は rename で別のファイルに入れ替わる
      ので、本体に掛けた鍵は差し替えの瞬間に意味を失います。
    ・停電やOSごと落ちた場合までは守りません(fsync していません)。メイキーと同じ水準です。
    ・**JSONとして読めない・形が違う時は、表示もAPIも止めて絶対に書き込みません**。
      以前は読めない時に「空」とみなして上書きしていたので、カンマ1つの書き損じで
      全カウンターが0に戻る作りでした。ただし**中身が空(0バイト・空白だけ・{}・[])**
      の時だけは、守るべき数字が1つも無いので空の表として続けます(止めると、
      管理画面から登録し直すこともできなくなるため)。

  【速さ】本体が常に完成品なので、**数えないとき(機械、今日2回目以降の人)は
  鍵を取らずに読んで表示するだけ**です。鍵を取るのは数える必要がある時だけで、
  その場合も鍵を取った後に読み直してから判定します(待っている間に他の人が
  書いているかもしれないため)。共有スクリプトも、使う入口でしか読み込みません
  (数えるだけの要求に、管理用の大きなスクリプトを読ませないため)。

  【パスは素の相対です】このファイルは常に入口として直接呼ばれるので、PHPは
  このフォルダを作業場所にして動きます。だから './count.dat' がそのまま隣を指します。
  **他のファイルから include しないこと。** 作業場所が呼んだ側になり、全部外れます。
  外れた時(読めない・書けない・作れない)は、どこでも「数えない・書かない・理由を
  ログに残す」へ倒しています。黙って別の場所へ書くことはありません。

  【受け取る値は必ず pusyuuCounterParam() を通すこと】?name[]=x のように配列で
  送られると、(string) で文字列にした時に警告が出て、応答(JSONやSVG)の前に
  警告の文が混ざります。

  【生IPを持ちません】以前は count.dat の5つ目に、直前の来訪者のIPをそのまま
  書いていました。count.dat は公開フォルダにあるので、**URLを叩けば誰でも
  読める場所に他人のIPが置かれていた**ことになります。今は1バイトも書きません。

  【秘密は非公開フォルダ、それ以外はこのフォルダ(方針)】このフォルダ(公開フォルダ)を
  どれだけ漁られても、取れるのは数字・設定・壁紙と、鍵が無ければ意味をなさない
  ハッシュだけ、という形にしています。IPを伏せる鍵だけは非公開フォルダ
  (.pusyuuHiddenFiles)に置きます。
    ・以前は「非公開フォルダなしで完結させたい」として鍵もこのフォルダに置いて
      いましたが、それは「PHPが正しく実行されている」間しか守られない形でした。
      持ち主の本当の優先順位は「できれば非公開フォルダなし、無理なら非公開フォルダに
      鍵を置き、公開側からは機密が取れないこと」です(2026-09-26 に確認)。
    ・**非公開フォルダが無い・鍵が壊れている時は「設定ミス」と表示して数えません。**
      以前は「開いた回数を数える」に落としていましたが、リロードで進むカウンターは
      壊れているのと同じなので、やめました(メイキーの公開版も非公開フォルダを
      前提にし、無ければはっきり止まる作りです)。
    ・フォルダはあるが鍵ファイルだけが無い時は、自動で作ります。失っても、その日の
      「もう数えたか」がやり直しになるだけで済むためです(メイキーのストレージ鍵と同じ扱い)。
  管理用APIは全プロダクト共通の合言葉を使うので、共有スクリプトと鍵が
  無い環境では管理用APIが止まります。

  【では、どうやって「今日もう数えたか」を見分けるか】
  IPを鍵つきハッシュ(HMAC)にして、その値だけを控えます。ここで大事なのは
  **鍵を日替わりにしている**ことです。
    ・日をまたぐと同じ人が別の値になるので、長期の追跡ができません
    ・万一この鍵が漏れても、分かるのは**その日の控え**だけです
  逆に、この形でも避けられないことも書いておきます。**鍵が漏れた日に限り、
  「このIPは今日来たか」を問えるようになります。** 一覧を持つ設計では原理的に
  避けられません(避けるなら、来訪者自身に「もう数えた」を持たせる形=クッキー、
  または人数の見積りだけを持つ形にするしかありません)。日替わりは、その穴を
  1日ぶんに閉じるための仕掛けです。

  【混ぜてはいけない材料】__DIR__ と fileinode(__FILE__) を混ぜる案がありましたが、
  入れていません。理由は2つあります。
    ①黙って壊れる: ファイルを置き換えると inode が変わります(エディタ・rsync・
      デプロイ・バックアップ復元は、たいてい一時ファイルを作って置き換えます)。
      変わった時点でその日の控えが全部一致しなくなり、全員もう一度数えられます。
      フォルダを動かせば __DIR__ も同じことが起きます。どちらも警告が出ません。
    ②守りとして効かない: 現実的な攻撃は「このIPは来たか」という1件の照合です。
      inode は同じフォルダ内で連番(実測: index.php=13502476, count.dat=13502478)で、
      範囲も限られるので候補は百万通り程度=一瞬です。__DIR__ も数百通りです。
      correctness を確実に壊して、1秒ぶんの時間しか買えません。
  強さは下の鍵(32バイトの乱数)が持ちます。それで足ります。

  【鍵を公開フォルダへ戻さないこと】公開フォルダの .php に置くと、PHPが実行される
  間は中身が配信されませんが、サーバ設定がずれてPHPがそのまま配信された日に鍵ごと
  出ます。鍵が出ると、その日の控え(count.dat の seen)が「このIPは来たか」を
  問える一覧に変わります。鍵が見つからない時に公開フォルダへ作ることもしません。
******************************************/

// 表示の桁数(合計を0詰めで揃えます)
const PUSYUU_COUNTER_DIGITS = 5;
// 1日に控えられる識別子の上限(カウンター1つあたり)。これを超えた分は
// 「数えるが控えない」扱いです(控えを捨てて入れ替えると、最初に来た人が
// 二重に数えられるため)。16文字×この数だけ育ちます。2000件でおよそ40KBです。
const PUSYUU_COUNTER_SEEN_MAX = 2000;
// 表示の形式。管理画面はこの一覧を admin_list で受け取って選択肢にします。
// 【形式を足すときはここと pusyuuCounterRender() だけ】管理画面側に形式の名前を
// 書き足さないこと(書くと、ここと管理画面の2箇所になり、片方だけ直って食い違います)。
const PUSYUU_COUNTER_FORMATS = [
  'html' => 'HTML(iframeで貼る)',
  'svg' => 'SVG画像(imgで貼る)',
];
// 新しく登録できる名前。URLに載せるので、記号や日本語は受け付けません。
const PUSYUU_COUNTER_NAME_PATTERN = '/\A[A-Za-z0-9_-]{1,32}\z/';
// 壁紙の置き場(このフォルダの中)と、置ける名前・種類。
// 名前を縛るのは、URLとCSSの url() にそのまま載せるためです(引用符や空白を通さない)。
// 先頭に . を許さないのは、書きかけの一時ファイル(.〇〇.tmp)を一覧に出さないためです。
const PUSYUU_COUNTER_WALLPAPER_DIR = './wallpapers';
const PUSYUU_COUNTER_WALLPAPER_PATTERN = '/\A[A-Za-z0-9_-]{1,64}\.(jpg|jpeg|png|webp|gif)\z/';
const PUSYUU_COUNTER_WALLPAPER_TYPES = [
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'webp' => 'image/webp',
  'gif' => 'image/gif',
];

// 外(合言葉を持たない人)へ返す失敗は、これ1つだけです。
// 【失敗の種類ごとに状態や文言を変えないこと】変えると「どこが悪いか」「何があるか」を
// 外から当てられます(登録されていない・控えが読めない・API・合言葉違い、を区別させない)。
// 理由はログと、合言葉を通った管理画面にだけ出します。
// 文言は「気づけるが、読まれても悪用できない」程度(普通の404ページと同じ)に留めます。
const PUSYUU_COUNTER_NOT_FOUND = [404, ['ok' => false, 'error' => 'not_found', 'message' => '見つかりません。']];

// IPを伏せる鍵の置き場(非公開フォルダ)。規約どおり、固定の相対パス1つだけを見ます
// (上へ辿って探すと、別の場所へ置かれた時に無関係な同名フォルダを掴むため)。
// このフォルダを別の深さへ移した時は、ここを直すまで「設定ミス」と表示されます。
const PUSYUU_COUNTER_HIDDEN_DIR = '../../../../.pusyuuHiddenFiles';
const PUSYUU_COUNTER_KEY_FILE = PUSYUU_COUNTER_HIDDEN_DIR . '/pusyuu_counter_key.php';

/** 自分の隣のファイルの場所(素の相対。上の【パスは素の相対です】を参照)。 */
function pusyuuCounterFile(string $name): string {
  return './' . $name;
}

/**
 * 受け取った値を文字列で取り出します。無い・文字列でない(配列など)時は空文字。
 * 【(string)$_GET[..] と直接書かないこと】理由は上の【受け取る値は…】。
 */
function pusyuuCounterParam(array $source, string $key): string {
  $value = $source[$key] ?? '';

  return is_string($value) ? $value : '';
}

/**
 * 共有スクリプトの場所。見つからなければ空文字。
 *
 * 【固定の相対パス1つだけを見ます。上へ辿って探さないこと】探し回ると、別の場所へ
 * 置かれた時に無関係な同名のスクリプトを掴みます。無ければ諦めれば済むためです。
 *   ・機械の判定(pusyuu_session.php)が無い → 機械も数えるだけで、表示は止まりません
 *   ・合言葉(meikiee_client.php)が無い → 管理用APIだけが止まります(外へは「見つかりません」)
 * このフォルダを持ち出した時は、上のとおり数えて表示する部分だけで動きます。
 */
function pusyuuCounterSharedScript(string $name): string {
  $path = '../../scripts/php_scripts/' . $name;

  return is_file($path) ? $path : '';
}

/**
 * このカウンター専用の秘密(32バイト)を非公開フォルダから読みます。
 * 戻り値: ['key' => 鍵(問題がある時は空文字), 'problem' => 問題(無ければ空文字)]
 * 問題: no_hidden_dir(非公開フォルダが無い) / key_broken(鍵が読めない・壊れている) /
 *       key_create_failed(鍵を作れない)。どれも「設定ミス」として表示し、数えません。
 *
 * 【問題がある時に、鍵無しで数え続けないこと】以前は「開いた回数を数える」に
 * 落としていましたが、リロードで進むカウンターは壊れているのと同じです。
 * **IPを代わりに書いたり、秘密無しのハッシュを書いたりもしません**(秘密無しの
 * ハッシュは、43億通りしかないIPを総当たりで逆に引けるので、IPを置いているのと同じです)。
 *
 * 【あるのに読めない・壊れている時は作り直さないこと】作り直すとその日の控えが
 * 全部一致しなくなり、全員がもう一度数えられます。それに、壊れた理由(権限・
 * 書き損じ)を人が確かめる前に上書きすることになります。
 *
 * 【管理用APIの合言葉とは別物です】こちらはIPを伏せるためだけの値で、
 * 管理の認証には使いません。混ぜると、片方を作り直した時にもう片方も壊れます。
 */
function pusyuuCounterSecret(): array {
  static $answer = null;

  // 1回の要求の中で何度呼ばれても、ディスクを見るのは最初の1回だけです。
  if ($answer === null) {
    $path = PUSYUU_COUNTER_KEY_FILE;
    $hasDir = is_dir(PUSYUU_COUNTER_HIDDEN_DIR);
    $hasFile = ($hasDir && file_exists($path));
    $value = $hasFile ? @include $path : null;
    $raw = is_string($value) ? base64_decode($value, true) : false;

    if (!$hasDir) {
      error_log('[プシューカウント] 設定ミス: 非公開フォルダが見つかりません。数えません。dir=' . PUSYUU_COUNTER_HIDDEN_DIR);
      $answer = ['key' => '', 'problem' => 'no_hidden_dir'];

    } else if ($hasFile && ($raw === false || strlen($raw) < 32)) {
      error_log('[プシューカウント] 設定ミス: 鍵が読めないか壊れています。作り直さず、数えません。file=' . $path);
      $answer = ['key' => '', 'problem' => 'key_broken'];

    } else if ($hasFile) {
      $answer = ['key' => $raw, 'problem' => ''];

    } else {
      // フォルダはあるが鍵だけ無い(新しく設置した時)。ここで作ります。
      // 【配るコードに秘密を含めないため、自動生成にしています】設置した環境ごとに別の値になります。
      // 【中身をPHPとして書くこと】万一このフォルダが配信されても、中身が素で出ないようにするためです。
      $fresh = random_bytes(32);
      $php = "<?php\n// プシューカウントの秘密(自動生成)。**公開しないこと。**\n"
        . "// IPを鍵つきハッシュにするためだけの値です。消すと新しく作られ、その日の\n"
        . "// 「もう数えたか」の控えだけがやり直しになります。\n"
        . 'return ' . var_export(base64_encode($fresh), true) . ";\n";
      // x は「無ければ作る・あれば失敗」。同時に2人が作ろうとしても、片方の鍵で上書きしません。
      $fp = @fopen($path, 'x');
      $written = ($fp !== false && fwrite($fp, $php) === strlen($php));
      if ($fp !== false) {
        fclose($fp);
      }
      // 書けなかった時は、同時に作った別の要求が先に書き終えている可能性があるので読み直します。
      $otherValue = $written ? null : @include $path;
      $other = is_string($otherValue) ? base64_decode($otherValue, true) : false;

      if ($written) {
        $answer = ['key' => $fresh, 'problem' => ''];
      } else if ($other !== false && strlen($other) >= 32) {
        $answer = ['key' => $other, 'problem' => ''];
      } else {
        error_log('[プシューカウント] 設定ミス: 鍵を作れませんでした(非公開フォルダに書けません)。数えません。file=' . $path);
        $answer = ['key' => '', 'problem' => 'key_create_failed'];
      }
    }
  }

  return $answer;
}

/**
 * 今日の識別子。**IPそのものは返しません。**
 * 材料はIPと利用者の名乗り(UA)で、鍵はその日ぶんに派生させます。
 *
 * 【UAも混ぜる理由】同じルーターの中にいる家族が1人に潰れるのを少し緩めます。
 * それに、狙い撃ちで「このIPは来たか」を問うには、相手のUAの文字列まで
 * 正確に知っていないといけなくなります(当てにくくなる方向です)。
 *
 * 【カウンターの名前は混ぜません】控えはカウンターごとに別の一覧なので、
 * 名前を混ぜなくても混ざりません。
 */
function pusyuuCounterIdentifier(string $secret, string $day): string {
  if ($secret === '') {
    return '';
  }
  $dayKey = hash_hkdf('sha256', $secret, 32, 'pusyuu-counter-' . $day);
  $material = (string)($_SERVER['REMOTE_ADDR'] ?? '') . "\x00" . (string)($_SERVER['HTTP_USER_AGENT'] ?? '');

  // 16文字(64bit)に切ります。1日の来訪者の数なら取り違えは起きず、
  // 鍵が無ければ元へは戻せません。
  return substr(hash_hmac('sha256', $material, $dayKey), 0, 16);
}

/**
 * 控え(全カウンターの対応表)を、鍵を取らずに読みます。
 * 戻り値: 対応表 / null=読めない・形が違う(**書き込んではいけない**状態)
 *
 * 【null の時に空の表で代用しないこと】空の表で続けると、次の書き込みで
 * 全カウンターが消えます。読めない時は「止める・書かない」へ倒してください。
 * 空の表にしてよいのは、ファイルが**無い**時(新しく設置した時)と、中身が**空**の
 * 時(0バイト・空白だけ・{}・[])だけです。どちらも守るべき数字がありません。
 *
 * 【鍵無しで読んでよい理由】書く側は必ず一時ファイル→rename で差し替えるので、
 * ここで開くのは常に完成品です。書く側を直接書き換える形に戻すと、ここが
 * 書きかけを読むようになります。
 */
function pusyuuCounterLoad(string $path): ?array {
  clearstatcache(true, $path);
  $exists = file_exists($path);
  $text = ($exists && is_file($path)) ? @file_get_contents($path) : false;
  $trimmed = ($text === false) ? '' : trim($text);
  $decoded = ($trimmed === '') ? null : json_decode($trimmed, true);

  if (!$exists) {
    $counters = [];

  } else if ($text === false) {
    // あるのに読めない(権限・フォルダになっている等)。空とみなすと上書きしてしまいます。
    error_log('[プシューカウント] 控えがあるのに読めません。止めて書き込みません。file=' . $path);
    $counters = null;

  } else if ($trimmed === '' || $decoded === []) {
    error_log('[プシューカウント] 控えの中身が空です。空の表として続けます。file=' . $path);
    $counters = [];

  } else {
    $valid = (is_array($decoded) && $trimmed[0] === '{');

    // 値が全部オブジェクトであること。2.0.0 の形({"day":..,"total":..})や
    // 書き損じをここで弾きます(弾かないと "total" という名前のカウンターとして
    // 扱われ、上書きされます)。
    foreach (($valid ? $decoded : []) as $value) {
      if (!is_array($value)) {
        $valid = false;
      }
    }

    if ($valid) {
      $counters = $decoded;
    } else {
      error_log('[プシューカウント] 控えを読めないか形が違うため、止めて書き込みません。file=' . $path);
      $counters = null;
    }
  }

  return $counters;
}

/**
 * カウンター1つぶんを、今日の時点の形に揃えます(日替わりもここで行います)。
 * 足りない項目は既定値で埋めます。
 *
 * 【日替わりは読むたびにここで行います】書き込むのは数えた時だけなので、
 * ファイルの中は前の日のままのことがあります。表示の側も、書く側も、APIも、
 * 必ずここを通してから使ってください。
 *
 * 【設定(format / wallpaper)もここで引き継ぐこと】ここで組み立て直す時に落とすと、
 * 数えた瞬間に設定が消えて既定に戻ります。設定を足したら、ここにも足します。
 * 壁紙のファイルが実際にあるかはここでは見ません(数えるたびにディスクを
 * 見に行かないため)。表示の時に確かめます。
 */
function pusyuuCounterEntry(array $raw, string $day, string $prevDay): array {
  $storedDay = (string)($raw['day'] ?? '');
  $total = (int)($raw['total'] ?? 0);
  $today = (int)($raw['today'] ?? 0);
  $seenRaw = $raw['seen'] ?? [];
  $seen = is_array($seenRaw) ? array_values(array_filter($seenRaw, 'is_string')) : [];
  $formatRaw = $raw['format'] ?? '';
  $format = (is_string($formatRaw) && array_key_exists($formatRaw, PUSYUU_COUNTER_FORMATS)) ? $formatRaw : 'html';
  $wallpaperRaw = $raw['wallpaper'] ?? '';
  $wallpaper = (is_string($wallpaperRaw) && preg_match(PUSYUU_COUNTER_WALLPAPER_PATTERN, $wallpaperRaw) === 1) ? $wallpaperRaw : '';
  $settings = ['format' => $format, 'wallpaper' => $wallpaper];

  if ($storedDay === $day) {
    $state = $settings + ['day' => $day, 'total' => $total, 'today' => $today, 'yesterday' => (int)($raw['yesterday'] ?? 0), 'seen' => $seen];
  } else if ($storedDay === $prevDay) {
    // 【控えを日替わりで捨てること】鍵も日替わりなので、残しても二度と一致しません。
    $state = $settings + ['day' => $day, 'total' => $total, 'today' => 0, 'yesterday' => $today, 'seen' => []];
  } else {
    // 昨日は誰も来なかった(または登録したて)。以前は、何日空いても
    // 最後に来た日の数を「昨日」として出していました。
    $state = $settings + ['day' => $day, 'total' => $total, 'today' => 0, 'yesterday' => 0, 'seen' => []];
  }

  return $state;
}

/**
 * この来訪を数えるかどうか。
 *
 * 【判定はここが唯一の合流点です】鍵無しの下見と、鍵を取った後の本番の
 * 両方がここを呼びます。どちらかにだけ条件を書き足すと、下見は「数えない」、
 * 本番は「数える」のように食い違い、数えるべき人を取りこぼします。
 */
function pusyuuCounterShouldCount(array $state, string $id, bool $isRobot): bool {
  if ($isRobot) {
    $count = false;
  } else if ($id === '') {
    // 識別子が無い=鍵が無い。見分けられないので数えません(開いた回数を数える形には戻さないこと)。
    $count = false;
  } else {
    $count = !in_array($id, $state['seen'], true);
  }

  return $count;
}

/**
 * 中身を一時ファイルに書いてから rename で差し替えます(メイキーと同じ形)。
 * 控えにも壁紙にも使います。**pusyuuCounterTransact() の中からだけ呼んでください**
 * (鍵を握っていないと、同時に来た別の書き込みを踏み潰します)。
 */
function pusyuuCounterWriteFile(string $path, string $content): bool {
  // 同じフォルダに置くこと。別のディスクにある一時フォルダからの rename は、
  // コピー→削除になり、一瞬で切り替わらなくなります。
  $tmp = dirname($path) . '/.' . uniqid('count_', true) . '.tmp';

  if (@file_put_contents($tmp, $content) === false) {
    error_log('[プシューカウント] 一時ファイルを書けませんでした(フォルダに書けない・容量不足)。file=' . $tmp);
    @unlink($tmp);
    $ok = false;
  } else if (!@rename($tmp, $path)) {
    // 失敗したら、この1回を諦めます。元のファイルは前の完成品のままです。
    error_log('[プシューカウント] 差し替えに失敗しました。tmp=' . $tmp . ' target=' . $path);
    @unlink($tmp);
    $ok = false;
  } else {
    $ok = true;
  }

  return $ok;
}

/** 対応表を保存します(pusyuuCounterTransact() の中からだけ)。 */
function pusyuuCounterSave(string $path, array $counters): bool {
  // (object) にするのは、名前が "0" だけの時などに配列([..])として
  // 書き出されるのを防ぐためです。配列で書かれると、次の読み込みで弾かれます。
  $json = json_encode((object)$counters, JSON_UNESCAPED_UNICODE);

  if ($json === false) {
    error_log('[プシューカウント] 控えをJSONにできませんでした。' . json_last_error_msg());
    $ok = false;
  } else {
    $ok = pusyuuCounterWriteFile($path, $json);
  }

  return $ok;
}

/**
 * 鍵を取り、読み直した対応表を $mutator に渡して、返された表を保存します。
 * 数え上げもAPIも、書き込みは全部ここを通ります(壁紙の追加・削除も、
 * 「使っているカウンターがあるか」の判定と食い違わないよう、ここを通ります)。
 *
 * $mutator は対応表を受け取り、[保存する表(保存しないなら null), 呼び出し側へ返す値] を返します。
 * 戻り値: ['problem' => '' | 'lock'(鍵が取れない) | 'broken'(読めない) | 'save'(保存失敗), 'result' => 返す値]
 * problem が '' 以外の時、result は null です。
 *
 * 【鍵を取った後に必ず読み直すこと】$mutator に渡すのは鍵を取ってから読んだ表です。
 * 鍵を取る前に読んだ表(下見)で書くと、待っている間に書かれた分が消えます。
 */
function pusyuuCounterTransact(string $path, callable $mutator): array {
  $lock = @fopen($path . '.lock', 'c');

  if ($lock === false) {
    error_log('[プシューカウント] 鍵を開けませんでした(フォルダに書けない等)。数えず・書きません。file=' . $path . '.lock');
    $outcome = ['problem' => 'lock', 'result' => null];
  } else {
    if (flock($lock, LOCK_EX)) {
      $counters = pusyuuCounterLoad($path);

      if ($counters === null) {
        $outcome = ['problem' => 'broken', 'result' => null];
      } else {
        [$next, $result] = $mutator($counters);

        if ($next === null) {
          $outcome = ['problem' => '', 'result' => $result];
        } else if (pusyuuCounterSave($path, $next)) {
          $outcome = ['problem' => '', 'result' => $result];
        } else {
          $outcome = ['problem' => 'save', 'result' => null];
        }
      }
      flock($lock, LOCK_UN);
    } else {
      error_log('[プシューカウント] 鍵を取れませんでした。数えず・書きません。');
      $outcome = ['problem' => 'lock', 'result' => null];
    }
    fclose($lock);
  }

  return $outcome;
}

/** 置いてある壁紙の一覧(名前 → バイト数)。フォルダが無い・読めない時は空。 */
function pusyuuCounterWallpapers(): array {
  $list = [];
  $names = is_dir(PUSYUU_COUNTER_WALLPAPER_DIR) ? @scandir(PUSYUU_COUNTER_WALLPAPER_DIR) : [];

  if ($names === false) {
    error_log('[プシューカウント] 壁紙のフォルダを読めません。dir=' . PUSYUU_COUNTER_WALLPAPER_DIR);
    $names = [];
  }
  foreach ($names as $file) {
    $path = PUSYUU_COUNTER_WALLPAPER_DIR . '/' . $file;
    if (preg_match(PUSYUU_COUNTER_WALLPAPER_PATTERN, $file) === 1 && is_file($path)) {
      $list[$file] = (int)@filesize($path);
    }
  }

  return $list;
}

/**
 * 壁紙として受け取れる大きさの上限(バイト)。null=上限なし。
 *
 * 【固定値で書かないこと】届く大きさは、このサーバの post_max_size(base64で約4/3倍に
 * 膨らむ分を戻す)と、展開に使うメモリ(memory_limit)で決まります。実際の設定から出します。
 */
function pusyuuCounterWallpaperMax(): ?int {
  $toBytes = static function (string $val): int {
    $val = trim($val);
    $num = (int)$val;
    $unit = strtolower(substr($val, -1));

    if ($unit === 'g') {
      $bytes = $num * 1024 * 1024 * 1024;
    } else if ($unit === 'm') {
      $bytes = $num * 1024 * 1024;
    } else if ($unit === 'k') {
      $bytes = $num * 1024;
    } else {
      $bytes = $num;
    }
    return $bytes;
  };
  $post = $toBytes((string)ini_get('post_max_size'));
  $memory = $toBytes((string)ini_get('memory_limit'));
  $limits = [];

  // 0 や -1 は「上限なし」の意味なので、候補に入れません。
  if ($post > 0) {
    // 合言葉などほかの項目の分として 4KB を引きます。
    $limits[] = (int)floor(max(0, $post - 4096) * 3 / 4);
  }
  if ($memory > 0) {
    // 受け取った文字列・展開後・確かめる時の写しが同時に載るので、余裕を見て1/4。
    $limits[] = (int)floor($memory / 4);
  }

  return empty($limits) ? null : min($limits);
}

/**
 * 壁紙を SVG に埋め込める形(data: URL)で返します。見つからない・読めない時は空文字。
 */
function pusyuuCounterWallpaperDataUrl(string $wallpaper): string {
  $path = PUSYUU_COUNTER_WALLPAPER_DIR . '/' . $wallpaper;
  $extension = strtolower(pathinfo($wallpaper, PATHINFO_EXTENSION));
  $bytes = ($wallpaper !== '' && is_file($path)) ? @file_get_contents($path) : false;

  if ($wallpaper === '') {
    $url = '';
  } else if ($bytes === false) {
    error_log('[プシューカウント] 設定された壁紙が見つからないか読めません。既定の背景で表示します。file=' . $path);
    $url = '';
  } else {
    $url = 'data:' . PUSYUU_COUNTER_WALLPAPER_TYPES[$extension] . ';base64,' . base64_encode($bytes);
  }

  return $url;
}

/** 自分の公開URL(末尾は /)。管理画面に貼り付け用のコードを出すために使います。 */
function pusyuuCounterPublicUrl(): string {
  // 【ドメインを書かないこと】名乗られたホスト名から組み立てます。管理画面からの
  // サーバー間の呼び出しでも、台帳が公開のホスト名を名乗らせるので同じ値になります。
  // 貼る先は外のサイトなので https 固定です(http だけで配信する環境では直すこと)。
  $dir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

  return 'https://' . (string)($_SERVER['HTTP_HOST'] ?? '') . $dir . '/';
}

/** カウンター1つぶんを、管理画面へ渡す形にします(控えの一覧 seen は渡しません)。 */
function pusyuuCounterSummary(string $name, array $state): array {
  $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  $url = pusyuuCounterPublicUrl() . '?name=' . rawurlencode($name);

  // 貼り付け用のコードはここで作ります。大きさや貼り方は形式ごとの事情なので、
  // 形式を知っているこちら側が持ちます(管理画面は受け取った文字列を見せるだけ)。
  if ($state['format'] === 'svg') {
    $embed = '<img src="' . $e($url) . '" alt="アクセスカウンター" width="240" height="120">';
  } else {
    $embed = '<iframe src="' . $e($url) . '" title="アクセスカウンター"></iframe>';
  }

  return [
    'name' => $name,
    'format' => $state['format'],
    'wallpaper' => $state['wallpaper'],
    'total' => $state['total'],
    'today' => $state['today'],
    'yesterday' => $state['yesterday'],
    'url' => $url,
    'embed' => $embed,
  ];
}

/**
 * 管理画面(oppai)から呼ばれるサーバー間API。戻り値は [HTTPの状態, 応答の中身]。
 *
 * 【認証】全プロダクト共通の合言葉(api_secret)の一致だけで判定します(p-chat 等と同じ)。
 * 呼び出せるのは、同じサーバで鍵ファイルを読めるものだけです。
 *
 * 【ここを唯一の入口にすること】管理画面が count.dat や wallpapers/ を直接触ると、
 * 鍵(count.dat.lock)も、読めない時は書かないという判定も通らなくなります。
 */
function pusyuuCounterApi(string $action): array {
  $day = date('Y/m/d');
  $prevDay = date('Y/m/d', strtotime('-1 day'));
  $path = pusyuuCounterFile('count.dat');
  $name = pusyuuCounterParam($_POST, 'name');
  $format = pusyuuCounterParam($_POST, 'format');
  $wallpaper = pusyuuCounterParam($_POST, 'wallpaper');
  // 共通の合言葉は共有スクリプトが導出します。管理用APIでだけ読み込みます。
  $gateway = pusyuuCounterSharedScript('meikiee_client.php');
  if ($gateway !== '') {
    require_once($gateway);
  }
  $expected = class_exists('PusyuuMeikieeClient') ? PusyuuMeikieeClient::apiSecret() : null;
  $given = pusyuuCounterParam($_POST, 'api_secret');
  // 書き込みの結果を、応答へ直す対応です(書き込む操作で共通)。
  $failed = [
    'lock' => [500, ['ok' => false, 'error' => 'lock_failed', 'message' => '控えの鍵を取れませんでした(カウンターのフォルダに書けない可能性があります)。']],
    'broken' => [503, ['ok' => false, 'error' => 'storage_broken', 'message' => '控え(count.dat)を読めないため、書き込みを止めています。']],
    'save' => [500, ['ok' => false, 'error' => 'save_failed', 'message' => '保存できませんでした(カウンターのフォルダに書けない・容量不足の可能性があります)。']],
  ];
  // 壁紙を選んだ時の確かめ。空=壁紙なし。**鍵の中で呼ぶこと**(削除と食い違わないため)。
  $wallpaperUsable = static function (string $wallpaper): bool {
    return ($wallpaper === '' || (preg_match(PUSYUU_COUNTER_WALLPAPER_PATTERN, $wallpaper) === 1 && is_file(PUSYUU_COUNTER_WALLPAPER_DIR . '/' . $wallpaper)));
  };
  $noWallpaper = [400, ['ok' => false, 'error' => 'invalid_wallpaper', 'message' => 'その壁紙はありません。']];
  $keyProblems = [
    '' => '',
    'no_hidden_dir' => '非公開フォルダ(カウンターから見て ' . PUSYUU_COUNTER_HIDDEN_DIR . ')が見つかりません。全カウンターが「設定ミス」と表示され、数えていません。',
    'key_broken' => '鍵(' . PUSYUU_COUNTER_KEY_FILE . ')が読めないか壊れています。全カウンターが「設定ミス」と表示され、数えていません。権限か中身を確認してください(消せば新しく作られます)。',
    'key_create_failed' => '鍵(' . PUSYUU_COUNTER_KEY_FILE . ')を作れません(非公開フォルダに書けません)。全カウンターが「設定ミス」と表示され、数えていません。',
  ];

  // 【合言葉が合うまでは、何があっても PUSYUU_COUNTER_NOT_FOUND だけを返すこと】
  // 「POSTで呼んで」「合言葉が要る」のように理由を返すと、管理用APIがあることも、
  // 合言葉の項目名も外から分かります(パスワードを間違えた時に「そのユーザーは存在する」
  // と教えるのと同じです)。理由はログにだけ残します。合言葉が合った後は管理者なので、
  // 詳しく返して構いません。
  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $response = PUSYUU_COUNTER_NOT_FOUND;

  } else if (!class_exists('PusyuuMeikieeClient')) {
    error_log('[プシューカウント API] 共有スクリプト(meikiee_client.php)が無いため、管理用APIを使えません。');
    $response = PUSYUU_COUNTER_NOT_FOUND;

  } else if ($expected === null || !hash_equals($expected, $given)) {
    error_log('[プシューカウント API] 合言葉が一致しない要求を拒否しました。addr=' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $response = PUSYUU_COUNTER_NOT_FOUND;

  } else if ($action === 'admin_list') {
    // 一覧は読むだけなので鍵を取りません(表示と同じ理由で、読むのは常に完成品です)。
    $counters = pusyuuCounterLoad($path);

    if ($counters === null) {
      $response = $failed['broken'];
    } else {
      $list = [];
      foreach ($counters as $key => $raw) {
        $list[] = pusyuuCounterSummary((string)$key, pusyuuCounterEntry($raw, $day, $prevDay));
      }
      $wallpapers = [];
      foreach (pusyuuCounterWallpapers() as $file => $bytes) {
        $wallpapers[] = ['name' => $file, 'bytes' => $bytes, 'url' => pusyuuCounterPublicUrl() . 'wallpapers/' . rawurlencode($file)];
      }
      $response = [200, [
        'ok' => true,
        'counters' => $list,
        'formats' => PUSYUU_COUNTER_FORMATS,
        'name_rule' => '半角英数字と _ - で32文字まで',
        'wallpapers' => $wallpapers,
        'wallpaper_rule' => '半角英数字と _ - で64文字まで + 拡張子(' . implode(' / ', array_keys(PUSYUU_COUNTER_WALLPAPER_TYPES)) . ')',
        'wallpaper_max_bytes' => pusyuuCounterWallpaperMax(),
        // 鍵の設定ミス(空=問題なし)。管理画面はこれが空でなければ警告を出します。
        // ここは合言葉を確かめた後の管理者向けなので、表示の側と違って詳しく書きます。
        'key_problem' => $keyProblems[pusyuuCounterSecret()['problem']],
      ]];
    }

  } else if ($action === 'admin_create') {
    if (preg_match(PUSYUU_COUNTER_NAME_PATTERN, $name) !== 1) {
      $response = [400, ['ok' => false, 'error' => 'invalid_name', 'message' => '名前は半角英数字と _ - で32文字までです。']];
    } else if (!array_key_exists($format, PUSYUU_COUNTER_FORMATS)) {
      $response = [400, ['ok' => false, 'error' => 'invalid_format', 'message' => '表示の形式が正しくありません。']];
    } else {
      $outcome = pusyuuCounterTransact($path, static function (array $counters) use ($name, $format, $wallpaper, $wallpaperUsable, $day, $prevDay) {
        if (array_key_exists($name, $counters)) {
          $answer = [null, 'exists'];
        } else if (!$wallpaperUsable($wallpaper)) {
          $answer = [null, 'no_wallpaper'];
        } else {
          $counters[$name] = pusyuuCounterEntry(['format' => $format, 'wallpaper' => $wallpaper], $day, $prevDay);
          $answer = [$counters, 'created'];
        }
        return $answer;
      });

      if ($outcome['problem'] !== '') {
        $response = $failed[$outcome['problem']];
      } else if ($outcome['result'] === 'exists') {
        $response = [409, ['ok' => false, 'error' => 'already_exists', 'message' => 'その名前のカウンターは既にあります。']];
      } else if ($outcome['result'] === 'no_wallpaper') {
        $response = $noWallpaper;
      } else {
        $response = [200, ['ok' => true]];
      }
    }

  } else if ($action === 'admin_update') {
    if (!array_key_exists($format, PUSYUU_COUNTER_FORMATS)) {
      $response = [400, ['ok' => false, 'error' => 'invalid_format', 'message' => '表示の形式が正しくありません。']];
    } else {
      $outcome = pusyuuCounterTransact($path, static function (array $counters) use ($name, $format, $wallpaper, $wallpaperUsable, $day, $prevDay) {
        if (!array_key_exists($name, $counters)) {
          $answer = [null, 'missing'];
        } else if (!$wallpaperUsable($wallpaper)) {
          $answer = [null, 'no_wallpaper'];
        } else {
          $entry = pusyuuCounterEntry($counters[$name], $day, $prevDay);
          $entry['format'] = $format;
          $entry['wallpaper'] = $wallpaper;
          $counters[$name] = $entry;
          $answer = [$counters, 'updated'];
        }
        return $answer;
      });

      if ($outcome['problem'] !== '') {
        $response = $failed[$outcome['problem']];
      } else if ($outcome['result'] === 'missing') {
        $response = [404, ['ok' => false, 'error' => 'not_found', 'message' => 'そのカウンターはありません。']];
      } else if ($outcome['result'] === 'no_wallpaper') {
        $response = $noWallpaper;
      } else {
        $response = [200, ['ok' => true]];
      }
    }

  } else if ($action === 'admin_delete') {
    $outcome = pusyuuCounterTransact($path, static function (array $counters) use ($name) {
      if (!array_key_exists($name, $counters)) {
        $answer = [null, 'missing'];
      } else {
        unset($counters[$name]);
        $answer = [$counters, 'deleted'];
      }
      return $answer;
    });

    if ($outcome['problem'] !== '') {
      $response = $failed[$outcome['problem']];
    } else if ($outcome['result'] === 'missing') {
      $response = [404, ['ok' => false, 'error' => 'not_found', 'message' => 'そのカウンターはありません。']];
    } else {
      $response = [200, ['ok' => true]];
    }

  } else if ($action === 'admin_wallpaper_add') {
    // 画像は base64 の文字列で受け取ります(管理画面からはフォームの1項目として届くため)。
    $bytes = base64_decode(pusyuuCounterParam($_POST, 'data'), true);
    $max = pusyuuCounterWallpaperMax();
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    // 中身が本当にその種類の画像かを、拡張子ではなく中身で確かめます。
    $info = ($bytes === false || $bytes === '') ? false : @getimagesizefromstring($bytes);

    if (preg_match(PUSYUU_COUNTER_WALLPAPER_PATTERN, $name) !== 1) {
      $response = [400, ['ok' => false, 'error' => 'invalid_name', 'message' => '壁紙の名前は、半角英数字と _ - で64文字まで + 拡張子(' . implode(' / ', array_keys(PUSYUU_COUNTER_WALLPAPER_TYPES)) . ')です。']];
    } else if ($bytes === false || $bytes === '') {
      $response = [400, ['ok' => false, 'error' => 'invalid_data', 'message' => '画像のデータを受け取れませんでした。']];
    } else if ($max !== null && strlen($bytes) > $max) {
      $response = [413, ['ok' => false, 'error' => 'too_large', 'message' => '画像が大きすぎます(上限 ' . $max . ' バイト)。']];
    } else if ($info === false || ($info['mime'] ?? '') !== PUSYUU_COUNTER_WALLPAPER_TYPES[$extension]) {
      $response = [400, ['ok' => false, 'error' => 'invalid_image', 'message' => '画像として読めないか、拡張子と中身の種類が一致しません。']];
    } else {
      $outcome = pusyuuCounterTransact($path, static function (array $counters) use ($name, $bytes) {
        $dir = PUSYUU_COUNTER_WALLPAPER_DIR;
        // mkdir が失敗しても、他の要求が同時に作っていれば is_dir は真になります。
        $noDir = (!is_dir($dir) && !@mkdir($dir, 0775) && !is_dir($dir));

        if ($noDir) {
          error_log('[プシューカウント] 壁紙のフォルダを作れませんでした。dir=' . $dir);
          $answer = [null, 'no_dir'];
        } else if (file_exists($dir . '/' . $name)) {
          $answer = [null, 'exists'];
        } else if (!pusyuuCounterWriteFile($dir . '/' . $name, $bytes)) {
          $answer = [null, 'write_failed'];
        } else {
          $answer = [null, 'added'];
        }
        return $answer;
      });

      if ($outcome['problem'] !== '') {
        $response = $failed[$outcome['problem']];
      } else if ($outcome['result'] === 'exists') {
        $response = [409, ['ok' => false, 'error' => 'already_exists', 'message' => 'その名前の壁紙は既にあります。']];
      } else if ($outcome['result'] === 'no_dir' || $outcome['result'] === 'write_failed') {
        $response = [500, ['ok' => false, 'error' => 'save_failed', 'message' => '壁紙を保存できませんでした(wallpapers フォルダを作れない・書けない可能性があります)。']];
      } else {
        $response = [200, ['ok' => true]];
      }
    }

  } else if ($action === 'admin_wallpaper_delete') {
    $outcome = pusyuuCounterTransact($path, static function (array $counters) use ($name, $day, $prevDay) {
      $file = PUSYUU_COUNTER_WALLPAPER_DIR . '/' . $name;
      $users = [];
      foreach ($counters as $key => $raw) {
        if (pusyuuCounterEntry($raw, $day, $prevDay)['wallpaper'] === $name) {
          $users[] = (string)$key;
        }
      }

      // 【使っているカウンターがある壁紙は消さないこと】消すと表示は既定の背景に
      // 落ちますが、設定には名前が残り、同じ名前の画像を置いた瞬間に黙って復活します。
      if (preg_match(PUSYUU_COUNTER_WALLPAPER_PATTERN, $name) !== 1 || !is_file($file)) {
        $answer = [null, 'missing'];
      } else if (!empty($users)) {
        $answer = [null, $users];
      } else if (!@unlink($file)) {
        error_log('[プシューカウント] 壁紙を削除できませんでした。file=' . $file);
        $answer = [null, 'unlink_failed'];
      } else {
        $answer = [null, 'deleted'];
      }
      return $answer;
    });

    if ($outcome['problem'] !== '') {
      $response = $failed[$outcome['problem']];
    } else if ($outcome['result'] === 'missing') {
      $response = [404, ['ok' => false, 'error' => 'not_found', 'message' => 'その壁紙はありません。']];
    } else if (is_array($outcome['result'])) {
      $response = [409, ['ok' => false, 'error' => 'in_use', 'message' => 'この壁紙は次のカウンターで使われています: ' . implode(', ', $outcome['result']) . '。先に別の壁紙へ切り替えてください。']];
    } else if ($outcome['result'] === 'unlink_failed') {
      $response = [500, ['ok' => false, 'error' => 'delete_failed', 'message' => '壁紙を削除できませんでした(フォルダに書けない可能性があります)。']];
    } else {
      $response = [200, ['ok' => true]];
    }

  } else {
    $response = [404, ['ok' => false, 'error' => 'unknown_action', 'message' => '不明な操作です。']];
  }

  return $response;
}

/**
 * 数えて、表示に使う状態を返します。戻り値は [状態, 問題]。
 * 問題: '' / 'broken'(控えを読めない) / 'unregistered'(登録されていない名前) /
 *       'misconfigured'(鍵の設定ミス。状態は返すが数えていない)
 */
function pusyuuCounterVisit(string $name): array {
  $day = date('Y/m/d');
  $prevDay = date('Y/m/d', strtotime('-1 day'));
  $path = pusyuuCounterFile('count.dat');
  $counters = pusyuuCounterLoad($path);

  if ($counters === null) {
    $visit = [null, 'broken'];
  } else if ($name === '' || !array_key_exists($name, $counters)) {
    $visit = [null, 'unregistered'];
  } else {
    // 機械(クローラ・AIの取得・道具)は数えません。「人が来た数」を出す物なので。
    // 判定は共有スクリプトの中にあります(無い環境では素通りし、機械も数えます)。
    $shared = pusyuuCounterSharedScript('pusyuu_session.php');
    if ($shared !== '') {
      require_once($shared);
    }
    $isRobot = (class_exists('PusyuuSession') && PusyuuSession::looksLikeRobot());
    // 機械の来訪でも鍵の問題は見ます(設定ミスを、人が来るまで隠さないため)。
    $secret = pusyuuCounterSecret();
    $id = ($isRobot || $secret['problem'] !== '') ? '' : pusyuuCounterIdentifier($secret['key'], date('Ymd'));
    // 鍵を取らない下見です。数える必要が無ければ、これをそのまま表示して終わります。
    $state = pusyuuCounterEntry($counters[$name], $day, $prevDay);

    if ($secret['problem'] !== '') {
      // 設定ミス。数えずに、その旨を表示します(数字は下見のまま出します)。
    } else if (pusyuuCounterShouldCount($state, $id, $isRobot)) {
      $outcome = pusyuuCounterTransact($path, static function (array $fresh) use ($name, $id, $isRobot, $day, $prevDay) {
        if (!array_key_exists($name, $fresh)) {
          // 待っている間に登録が消された。作り直さない。
          $answer = [null, null];
        } else {
          $current = pusyuuCounterEntry($fresh[$name], $day, $prevDay);

          if (pusyuuCounterShouldCount($current, $id, $isRobot)) {
            $current['total']++;
            $current['today']++;
            if ($id !== '' && count($current['seen']) < PUSYUU_COUNTER_SEEN_MAX) {
              $current['seen'][] = $id;
            }
            $fresh[$name] = $current;
            $answer = [$fresh, $current];
          } else {
            // 待っている間に、同じ人の別の要求が数え終えていた。
            $answer = [null, $current];
          }
        }
        return $answer;
      });
      // 数えられなかった時(鍵・保存の失敗、登録が消えた)は、下見のまま表示します。
      $state = is_array($outcome['result']) ? $outcome['result'] : $state;
    }
    $visit = [$state, ($secret['problem'] !== '') ? 'misconfigured' : ''];
  }

  return $visit;
}

/** JSONで応答します(APIと、表示できない時の応答)。 */
function pusyuuCounterJson(int $status, array $body): string {
  http_response_code($status);
  header('Content-Type: application/json; charset=UTF-8');

  return (string)json_encode($body, JSON_UNESCAPED_UNICODE);
}

/**
 * カウンターを、そのカウンターに設定された形式・壁紙で組み立てます。
 * 【外のファイルを参照しないこと(SVG)】<img> として読まれた SVG は、外の画像や
 * フォントを読みに行けません。壁紙は data: URL にして中へ埋め込みます。
 *
 * $misconfigured が真の時は、数字の代わりに「開発者の設定ミスにより表示できません」を出します(状態 503)。
 * 貼られた場所で見た人にも、止まっていることが分かるようにするためです
 * (数字を出し続けると、止まっていることに誰も気づきません)。
 *
 * 【見る人向けの文言は「分類」まで。仕組みは書かないこと】見る人はこのカウンターの
 * 作り(鍵で来訪者を見分けている、非公開フォルダを使う、等)を知りませんし、知る必要も
 * ありません。「開発者の設定ミス」「お問い合わせください」のように、何が起きたかの
 * 分類と、見る人が取れる行動までは伝えます。「鍵」「非公開フォルダ」のような仕組みの
 * 言葉は出しません(どう作られていて、どこを突けば止まるかを教えることになるため)。
 * 詳しい理由はログと、管理画面(合言葉を通った admin_list の key_problem)にだけ出します。
 */
function pusyuuCounterRender(array $state, bool $misconfigured): string {
  $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  $total = sprintf('%0' . PUSYUU_COUNTER_DIGITS . 'd', $state['total']);
  http_response_code($misconfigured ? 503 : 200);

  if ($state['format'] === 'svg') {
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    $dataUrl = pusyuuCounterWallpaperDataUrl($state['wallpaper']);

    if ($misconfigured) {
      $label = 'プシューカウント';
      $linesSvg = '
    <text x="120" y="62" fill="#ffcc66" font-size="11">開発者の設定ミスにより表示できません</text>
    <text x="120" y="84" fill="#ffffff" font-size="11">時間がたっても解決しない場合は</text>
    <text x="120" y="102" fill="#ffffff" font-size="11">お問い合わせください</text>';
    } else {
      $label = 'プシューカウント 合計' . $total . ' 今日' . $state['today'] . ' 昨日' . $state['yesterday'];
      $linesSvg = '
    <text x="120" y="64" fill="#77ffff" font-size="12">合計<tspan fill="#00ff00" font-size="15">' . $e($total) . '</tspan>のアクセス</text>
    <text x="120" y="86" fill="#ffffff" font-size="12">今日は<tspan fill="#00ff00" font-size="15">' . $e($state['today']) . '</tspan>回のアクセス</text>
    <text x="120" y="106" fill="#ffffff" font-size="12">昨日は<tspan fill="#00ff00" font-size="15">' . $e($state['yesterday']) . '</tspan>回のアクセスでした。</text>';
    }

    // 壁紙は少しはみ出させてからぼかします(ちょうどの大きさだと、ぼかしで縁が透けるため)。
    if ($dataUrl === '') {
      $wallpaperSvg = '';
    } else {
      $wallpaperSvg = '
    <image href="' . $e($dataUrl) . '" x="-12" y="-12" width="264" height="144" preserveAspectRatio="xMidYMid slice" filter="url(#blur)"/>';
    }

    $out = '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="120" viewBox="0 0 240 120" role="img" aria-label="' . $e($label) . '">
  <title>' . $e($label) . '</title>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0b1e2d"/>
      <stop offset="1" stop-color="#16394d"/>
    </linearGradient>
    <clipPath id="frame"><rect width="240" height="120" rx="6"/></clipPath>
    <filter id="blur"><feGaussianBlur stdDeviation="4"/></filter>
  </defs>
  <g clip-path="url(#frame)">
    <rect width="240" height="120" fill="url(#bg)"/>' . $wallpaperSvg . '
  </g>
  <g font-family="sans-serif" text-anchor="middle">
    <text x="120" y="28" fill="#00ccff" font-size="18" font-weight="bold">プシューカウント</text>
    <line x1="12" y1="36" x2="228" y2="36" stroke="#00ccff" stroke-width="2" stroke-dasharray="2 2"/>' . $linesSvg . '
  </g>
</svg>
';
  } else {
    header('Content-Type: text/html; charset=UTF-8');
    $wallpaperPath = PUSYUU_COUNTER_WALLPAPER_DIR . '/' . $state['wallpaper'];

    // 背景はここで決めます(count.css には書きません。カウンターごとに違うため)。
    // 壁紙が無い・消えている時は、SVGと同じ既定のグラデーションにします。
    if ($state['wallpaper'] !== '' && is_file($wallpaperPath)) {
      $background = "background-image:url('wallpapers/" . rawurlencode($state['wallpaper']) . "')";
    } else {
      if ($state['wallpaper'] !== '') {
        error_log('[プシューカウント] 設定された壁紙が見つかりません。既定の背景で表示します。file=' . $wallpaperPath);
      }
      $background = 'background-image:linear-gradient(135deg,#0b1e2d,#16394d)';
    }

    if ($misconfigured) {
      // 問い合わせフォームは同じ階層(../contact/)にあります。隣に無い時(このフォルダだけを
      // 持ち出した時など)は、行き先の無いリンクにせず文字だけにします。
      // 新しいタブで開くのは、小さな iframe の中でフォームが開いても使えないためです。
      // (SVG は <img> として読まれるとリンクを押せないので、文字だけです)
      // 確かめるのはこの時だけです(普段の表示のたびにディスクを見に行かないため)。
      $contactHtml = is_file('../contact/index.php') ? '<a href="../contact/" target="_blank" rel="noopener">お問い合わせ</a>' : 'お問い合わせ';
      $linesHtml = '
      <div id="count">
        <p>開発者の設定ミスにより表示できません。</p>
        <p>時間がたっても解決しない場合は' . $contactHtml . 'ください。</p>
      </div>';
    } else {
      $linesHtml = '
      <div id="total">
        <p>合計<span class="count">' . $e($total) . '</span>のアクセス</p>
      </div>
      <div id="count">
        <p>今日は<span class="count">' . $e($state['today']) . '</span>回のアクセス</p>
        <p>昨日は<span class="count">' . $e($state['yesterday']) . '</span>回のアクセスでした。</p>
      </div>';
    }

    $out = '<!DOCTYPE html>
<html lang="ja">
  <head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex,nofollow">
    <title>プシューカウント</title>
    <link href="count.css" rel="stylesheet">
  </head>
  <body style="' . $e($background) . '">
    <div>
      <h3>プシューカウント</h3>' . $linesHtml . '
    </div>
  </body>
</html>
';
  }

  return $out;
}

/**
 * 入口の振り分け。?api → 管理用API / ?name → 数えて表示 / どちらも無い → APIの404。
 *
 * どの応答もキャッシュさせません。画像は特にブラウザや中継に溜められやすく、
 * 溜められると数字が止まって見えます。
 *
 * 【セッションを始めません】以前は $_SESSION に直前のIPを入れていました。
 * iframe で開くだけのこの小さなページが、全員にクッキーを1つ配っていたことになります。
 * 今は控えの側で見分けるので、要りません。
 */
function pusyuuCounterMain(): string {
  header('Cache-Control: no-store, max-age=0');

  if (isset($_GET['api'])) {
    [$status, $body] = pusyuuCounterApi(pusyuuCounterParam($_GET, 'api'));
    $out = pusyuuCounterJson($status, $body);
    // (合言葉が合わない間は、pusyuuCounterApi が PUSYUU_COUNTER_NOT_FOUND を返します)

  } else if (isset($_GET['name'])) {
    [$state, $problem] = pusyuuCounterVisit(pusyuuCounterParam($_GET, 'name'));

    // 控えが読めない・登録されていない時は、外へは区別せず同じ応答にします
    // (理由は pusyuuCounterLoad などがログに残しています)。
    if ($problem === 'broken' || $problem === 'unregistered') {
      $out = pusyuuCounterJson(PUSYUU_COUNTER_NOT_FOUND[0], PUSYUU_COUNTER_NOT_FOUND[1]);
    } else {
      // 鍵の設定ミスは形式が分かる(登録はある)ので、そのカウンターの形式で「表示できません」を出します。
      $out = pusyuuCounterRender($state, $problem === 'misconfigured');
    }

  } else {
    $out = pusyuuCounterJson(PUSYUU_COUNTER_NOT_FOUND[0], PUSYUU_COUNTER_NOT_FOUND[1]);
  }

  return $out;
}
?>
<?= pusyuuCounterMain() ?>
