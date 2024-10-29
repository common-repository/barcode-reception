<div class="wrap">
	<div class="icon32" id="icon-options-general">
		<br>
	</div>
	<h2>Barcode Reception 設定</h2>
	<h3>ユーザ・バーコード表示用ショートコード</h3>
	<div style="margin-left: 20px;">
		<p>ユーザのバーコードを表示するページに以下のコードをコピーして貼り付けてください。</p>
		<p>
			<input type="text"
				value=<?php echo "[" . BCR::SHORTCODE_BARCODE . "]";?> readonly></input>
		</p>
	</div>
	<h3>バーコード受付画面表示用ショートコード</h3>
	<div style="margin-left: 20px;">
		<p>受付画面を表示するページに以下のコードをコピーして貼り付けてください。</p>
		<p>
			<input type="text"
				value=<?php echo "[" . BCR::SHORTCODE_RECEPTION . "]";?> readonly></input>
		</p>
	</div>
	<form action="options.php" method="post">
		<?php settings_fields( $option_name ); ?>
		<?php do_settings_sections( $file ); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary"
				value="<?php esc_attr_e('Save Changes'); ?>" />
		</p>
	</form>
</div>
