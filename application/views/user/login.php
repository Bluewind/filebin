<?php 
if (isset($login_error)) {
	echo '<font style="color: rgb(238, 51, 51);">The entered credentials are invalid.</font>';
} ?>

<?php echo form_open('user/login'); ?>
	<table>
    <tr>
      <td>Username:</td>
      <td><input type="text" name="username" /></td>
    </tr>
    <tr>
      <td>Password:</td>
      <td><input type="password" name="password" /></td>
    </tr>
  </table>
  <input type="submit" value="Login" name="process" />
</form>
