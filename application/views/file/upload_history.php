<?php echo form_open('file/upload_history'); ?>
	<p>
		Password:<input type="password" name="password" size="10" />
		<input type="submit" value="Display" />
	</p>
</form>

<table class="results">
<tr>
  <th>ID</th>
  <th>Filename</th>
  <th>Mimetype
  <th>Date</th>
  <th>Hash</th>
</tr>

<?php foreach($query as $key => $item): ?>
<tr class="<?php echo even_odd(); ?>">
	<td><a href="<?php echo site_url("/".$item["id"]); ?>/"><?php echo $item["id"]; ?></a></td>
	<td><?php echo $item["filename"]; ?></td>
	<td><?php echo $item["mimetype"]; ?></td>
	<td><?php echo $item["date"]; ?></td>
	<td><?php echo $item["hash"]; ?></td>
</tr>
<?php endforeach; ?>
</table>
