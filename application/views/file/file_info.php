<div class="center">
	<?php if($filedata): ?>
		<table style="margin: auto">
			<tr>
				<td class="title">ID</td>
				<td class="text"><a href="<?php echo site_url($id); ?>/"><?php echo $id; ?></a></td>
			</tr>
			<tr>
				<td class="title">Filename</td>
				<td class="text"><?php echo htmlspecialchars($filedata["filename"]); ?></td>
			</tr>
			<tr>
				<td class="title">Date of upload</td>
				<td class="text"><?php echo date("r", $filedata["date"]); ?></td>
			</tr>
			<tr>
				<td class="title">Date of removal</td>
				<td class="text"><?php echo $timeout; ?></td>
			</tr>
			<tr>
				<td class="title">Size</td>
				<td class="text"><?php echo format_bytes($filedata["filesize"]); ?></td>
			</tr>
			<tr>
				<td class="title">Mimetype</td>
				<td class="text"><?php echo $filedata["mimetype"]; ?></td>
			</tr>
		</table>
	<?php endif; ?>
</div>
