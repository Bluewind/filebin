<?php echo form_open('user/create_invitation_key'); ?>
  <input type="submit" value="Create new key" name="process" />
</form>

<p>Unused invitation keys:</p>
<p>
<?php foreach($query as $key => $item): ?>
	<?php echo anchor("user/register/".$item["key"], $item["key"]); ?><br />
<?php endforeach; ?>
</p>
