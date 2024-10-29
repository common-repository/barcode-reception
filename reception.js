jQuery(document).ready(function($) {
	createDialog();
	$('#barcode-btn').click(sendBarcode);
	$('#barcode-txt').focus().keypress(function(e) {
		if (e.which == 13) {
			sendBarcode();
			return false;
		}
	});

	var requestData = {
		action : BCR_Setting.action,
		request : "",
		barcode : "",
		nonce : ""
	}

	function sendBarcode() {
		requestData.request = "search_user";
		requestData.barcode = $('#barcode-txt').val();
		ajaxRequest(requestData, showSearchResult);
		$(this).attr('disabled', true);
		return false;
	}

	function createDialog() {
		$('#user-dialog').dialog({
			autoOpen : false,
			title : 'ユーザ情報',
			closeOnEscape : false,
			modal : true,
			buttons : {
				"登録する" : function() {
					requestData.request = "entry_user";
					ajaxRequest(requestData, showEntryResult);
					$(this).dialog('close');
				},
				"キャンセル" : function() {
					$(this).dialog('close');
				}
			}
		});
		$('#reception-message').dialog({
			autoOpen : false,
			closeOnEscape : false,
			modal : true,
			buttons : {
				"OK" : function() {
					$(this).dialog('close');
				}
			}
		});
	}

	function showSearchResult(res) {
		$('#barcode-txt').val('').focus();
		if (res.result == 'NG') {
			$('#reception-message #message').html(res.message);
			$('#reception-message').dialog('open');
			return;
		}
		$('#search-result').html( createHtml(res.user) );
		$('#user-dialog').dialog('open');
		requestData.nonce = res.nonce;
	}

	function createHtml( user ) {
		var rtn = "<div class='search-result'>"
			+ "<p>ユーザー名：" + user.login_name + "</p>"
			+ "<p>氏名：" + user.full_name + "</p>"
			+ "<p>email：" + user.email + "</p>"
			+ "</div>";
		return rtn;
	}

	function showEntryResult(res) {
		$('#reception-message #message').html(res.message);
		$('#reception-message').dialog('open');
	}

	function ajaxRequest(data, callback) {
		$.ajax({
			type : 'POST',
			url : BCR_Setting.ajaxurl,
			data : data,
			timeout : 8000,
			complete : function() {
				$('#barcode-btn').attr('disabled', false);
			},
			error : function() {
				alert("データを取得できません");
			},
			success : callback
		});
	}
});
