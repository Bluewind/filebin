<?php 
if (isset($login_error)) { ?>
	<div class="alert alert-error">The entered credentials are invalid.</div>
<?php } ?>

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
  <input type="submit" class="btn btn-primary" value="Login" name="process" />
</form>
