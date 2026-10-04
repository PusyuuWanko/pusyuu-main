<?php
function notationDisp($whyInfo = "notation") {
  $getNotationFile = file_get_contents(__DIR__ . "/../../documents/pusyuuNotation.json");
  $result = "";

  if ($getNotationFile === false) {
    $result = "<p>規約等のデータ取得に失敗たため、表示できませんでした。</p>";
  } else {
    $notationData = json_decode($getNotationFile, true);

    if (!isset($notationData)) {
      $result = "<p>何らかの理由で規約等のデータを解釈に失敗したため、表示できませんでした。</p>";
    } else if ($whyInfo == "notation") {
      $notation = $notationData["notationText"][0];
      // このリンクは他のプロダクトの画面にも出るので、"linkRegistry" に台帳(pusyuu_registry)の
      // IDがあれば、link をそのプロダクトの中のパスとして、今の入口に合う顔の住所を付けます。
      // 【pusyuuNotation.json にドメインを書き戻さないこと】台帳が読めないときは link のまま。
      $linkRegistry = (string)($notation["linkRegistry"] ?? "");
      $linkBase = ($linkRegistry !== "" && class_exists('PusyuuRegistryClient')) ? PusyuuRegistryClient::url($linkRegistry) : null;
      $link = ($linkBase === null) ? (string)$notation["link"] : $linkBase . (string)$notation["link"];
      $result = '<h3>' . $notation["title"] . '</h3>' . '<p>' . $notation["content"] . '</p>' . '<ul><li><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" target="_blank">' . $notation["linkTitle"] . '</a></li></ul>';
    } else if ($whyInfo == "copyright") {
      $copyright = $notationData["notationText"][1];
      $result = '<small>' . $copyright["copyright"] . '</small>';
    } else {
      $result = "<p>開発者の設定ミスのより、表示できませんでした。</p>";
    }
  }

  return $result;
}
?>
