/*
  CopyRight: © ISAMI ABE
	LastUpdate: 2025/04/19
*/

var validate = function() {

	var flag = true;

	removeElementsByClass("error");
	removeClass("error-form");

	// お名前の入力をチェック
	if (document.form.name.value == "") {
		errorElement(document.form.name, "お名前が入力されていません");
		flag = false;
	}

	// ふりがなの入力をチェック
	if (document.form.furigana.value != "") {
		// ふりがなの形式をチェック
		if(!validateKana(document.form.furigana.value)) {
			errorElement(document.form.furigana, "ひらがな以外の文字が入っています");
      flag = false;
		}
	}

	// メールアドレスの入力をチェック
	if (document.form.email.value == "") {
		errorElement(document.form.email, "メールアドレスが入力されていません");
		flag = false;
	} else {
		// メールアドレスの形式をチェック
		if (!validateMail(document.form.email.value)) {
			errorElement(document.form.email, "メールアドレスが正しくありません");
			flag = false;
		}
	}


	// お問い合わせ項目の選択をチェック
	if (document.form.item.value == "") {
		errorElement(document.form.item, "お問い合わせ項目が選択されていません");
		flag = false;
	}

	// お問い合わせ内容の入力をチェック
	if (document.form.content.value == "") {
		errorElement(document.form.content, "お問い合わせ内容が入力されていません");
		flag = false;
	}

	return flag;
}

var errorElement = function (form, msg) {
	var newElement = document.createElement("div");
	var inputEle = document.getElementsByTagName("input");
	for (var i = 0; i < inputEle.length; i++) {
	  inputEle[i].addEventListener("input", function() {
      newElement.innerHTML = "";
  	});
	}
	var textareaEle = document.getElementsByTagName("textarea");
  for (var i = 0; i < textareaEle.length; i++) {
	  textareaEle[i].addEventListener("input", function() {
      newElement.innerHTML = "";
    });
  }
	var selectEle = document.getElementsByTagName("select");
  for (var i = 0; i < selectEle.length; i++) {
	  selectEle[i].addEventListener("change", function() {
      newElement.innerHTML = "";
  	});
	}
	form.className = "error-form";
	newElement.className = "error";
	newElement.style.color = "#ff0000";
	var newText = document.createTextNode(msg);
  newElement.appendChild(newText);
	form.parentNode.insertBefore(newElement, form.nextSibling);
}

var removeElementsByClass = function (className) {
	var elements = document.getElementsByClassName(className);
	while(elements.length > 0) { 
		elements[0].parentNode.removeChild(elements[0]);
	}
}

var removeClass = function (className) {
	var elements = document.getElementsByClassName(className);
	while (elements.length > 0) {
		elements[0].className = "";
	}
}

var validateMail = function (val) {
	if (val.match(/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/)==null) {
		return false;
	} else {
		return true;
	}
}


var validateNumber = function (val) {
	if (val.match(/[^0-9]+/)) {
		return false;
	} else {
		return true;
	}
}


var validateTel = function (val) {
	if (val.match(/^[0-9-]{6,13}$/) == null) {
		return false;
	} else {
		return true;
	}
}


var validateKana = function (val) {
	if (val.match(/^[ぁ-ん]+$/) == null) {
		return false;
	} else {
		return true;
	}
}