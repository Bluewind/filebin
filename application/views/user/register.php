<?php if (!empty($error)) {
	echo "<p>";
	echo implode("<br />\n", $error);
	echo "</p>";
} ?>
<?php echo form_open('user/register/'.$key); ?>
	<table>
		<tr>
			<td>Username</td>
			<td> <input type="text" name="username" value="<?=$values["username"]; ?>" /></td>
		</tr><tr>
			<td>Email</td>
			<td> <input type="text" name="email" value="<?=$values["email"]; ?>" /></td>
		</tr><tr>
			<td>Password</td>
			<td> <input type="password" name="password" /></td>
		</tr><tr>
			<td>Confirm password</td>
			<td> <input type="password" name="password_confirm" /></td>
		</tr><tr>
			<td></td>
			<td><input type="submit" value="Register" name="process" /></td>
		</tr>
	</table>
</form>

