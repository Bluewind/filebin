<?php echo form_open("file/do_delete"); ?>
	<table class="results">
	<tr>
	  <th></th>
	  <th>ID</th>
	  <th>Filename</th>
	  <th>Mimetype
	  <th>Date</th>
	  <th>Hash</th>
	  <th>Size</th>
	</tr>

	<?php foreach($query as $key => $item): ?>
	<tr class="<?php echo even_odd(); ?>">
		<td><input type="checkbox" name="ids[<?php echo $item["id"]; ?>]" value="<?php echo $item["id"]; ?>" /></td>
		<td><a href="<?php echo site_url("/".$item["id"]); ?>/"><?php echo $item["id"]; ?></a></td>
		<td><?php echo htmlspecialchars($item["filename"]); ?></td>
		<td><?php echo $item["mimetype"]; ?></td>
		<td><?php echo $item["date"]; ?></td>
		<td><?php echo $item["hash"]; ?></td>
		<td><?php echo $item["filesize"]; ?></td>
	</tr>
	<?php endforeach; ?>
	</table>
	<input type="submit" value="Delete checked" name="process" />
</form>
