<div class="wrap">
	<div class="icon32" id="icon-options-general">
		<br>
	</div>
	<h2>受付済みユーザ一覧</h2>
	<div class="entry-list">
		<table class="wp-list-table widefat fixed users">
			<tr>
				<th>ユーザ名</th>
				<th>氏名</th>
				<th>e-mail</th>
				<th>受付日時</th>
			</tr>
			<?php $n = 0; ?>
			<?php foreach($users as $user): ?>
			<tr <?php $n++; if ( $n % 2 == 1){ echo 'class="alternate"'; } ?> >
				<td><a href="<?php echo admin_url()."user-edit.php?user_id=".$user->ID; ?>"><?php echo $user->user_login; ?></a></td>
				<td><?php echo $user->last_name .  " " . $user->first_name; ?></td>
				<td><?php echo $user->user_email; ?>
				<td><?php echo $user->dateTime; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>
</div>
