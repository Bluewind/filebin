<div class="center simple-container">
	<div class="table-responive">
		<table class="table" style="margin: auto">
			<tr>
				<td class="title">ID</td>
				<td class="text"><a href="<?=site_url($id); ?>/"><?=$id; ?></a></td>
			</tr>
			<tr>
				<td class="title">Number of files</td>
				<td class="text"><?=$file_count; ?></td>
			</tr>
			<tr>
				<td class="title">Date of upload</td>
				<td class="text"><?=date("r", $upload_date); ?></td>
			</tr>
			<tr>
				<td class="title">Date of removal</td>
				<td class="text"><?=$timeout_string; ?></td>
			</tr>
			<tr>
				<td class="title">Total size (including duplicates)</td>
				<td class="text"><?=format_bytes($size); ?></td>
			</tr>
		</table>
	</div>
</div>
