<div class="table-responsive container-wide">
	<p>Non-previewable file(s):</p>
	<table class="table table-striped tablesorter">
		<thead>
			<tr>
				<th>ID</th>
				<th>Filename</th>
				<th>Mimetype</th>
				<th>Date</th>
				<th>Size</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach($items as $item): ?>
				<tr>
					<td><a href="<?php echo site_url("/".$item["id"]) ?>/"><?php echo $item["id"] ?></a></td>
					<td class="wrap"><?php echo htmlspecialchars($item["filename"]); ?></td>
					<td><?php echo $item["mimetype"] ?></td>
					<td class="nowrap" data-sort-value="<?=$item["date"]; ?>"><?php echo date("r", $item["date"]); ?></td>
					<td><?php echo format_bytes($item["filesize"]) ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
