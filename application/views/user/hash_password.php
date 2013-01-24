<?php
if (!empty($error)) {
	echo "<p class='alert alert-error'>";
	echo implode("<br />\n", $error);
	echo "</p>";
}

if ($hash) {
	echo "<p>Result (this hash uses a random salt, so it will be different each time you submit this form):<br />$hash</p>\n";
}
?>
<?php echo form_open('user/hash_password'); ?>
	<table>
		<tr>
			<td>Password</td>
			<td> <input type="password" name="password" /></td>
		</tr><tr>
			<td>Confirm password</td>
			<td> <input type="password" name="password_confirm" /></td>
		</tr><tr>
			<td></td>
			<td><input type="submit" value="Hash it" name="process" /></td>
		</tr>
	</table>
</form>

