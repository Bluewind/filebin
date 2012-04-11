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
	<td><a href="<?php echo site_url("file/delete/".$item["id"]); ?>"><img src="<?php echo base_url(); ?>data/img/fuge-icons/cross.png" /></a></td>
	<td><a href="<?php echo site_url("/".$item["id"]); ?>/"><?php echo $item["id"]; ?></a></td>
	<td><?php echo htmlspecialchars($item["filename"]); ?></td>
	<td><?php echo $item["mimetype"]; ?></td>
	<td><?php echo $item["date"]; ?></td>
	<td><?php echo $item["hash"]; ?></td>
	<td><?php echo $item["filesize"]; ?></td>
</tr>
<?php endforeach; ?>
</table>
