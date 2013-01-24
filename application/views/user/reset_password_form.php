<?php if (!empty($error)) {
	echo "<p>";
	echo implode("<br />\n", $error);
	echo "</p>";
} ?>
<?php echo form_open('user/reset_password/'.$key); ?>
	<table>
		<tr>
			<td>Password</td>
			<td> <input type="password" name="password" /></td>
		</tr><tr>
			<td>Confirm password</td>
			<td> <input type="password" name="password_confirm" /></td>
		</tr><tr>
			<td></td>
			<td><input type="submit" value="Change Password" name="process" /></td>
		</tr>
	</table>
</form>

