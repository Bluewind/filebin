<?php register_js_include("/data/js/jquery.tablesorter.min.js"); ?>
<?php echo form_open("file/do_delete") ?>
    <table id="upload_history" class="table table-striped tablesorter">
        <thead>
            <tr>
                <th><input type="checkbox" name="all-ids" id="history-all"></th>
                <th>ID</th>
                <th>Filename</th>
                <th>Mimetype
                <th>Date</th>
                <th>Hash</th>
                <th>Size</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($query as $key => $item): ?>
                <tr>
                    <td><input type="checkbox" name="ids[<?php echo $item["id"] ?>]" value="<?php echo $item["id"] ?>" class="delete-history"></td>
                    <td><a href="<?php echo site_url("/".$item["id"]) ?>/"><?php echo $item["id"] ?></a></td>
                    <td><?php echo htmlspecialchars($item["filename"]); ?></td>
                    <td><?php echo $item["mimetype"] ?></td>
                    <td class="nowrap"><?php echo date("r", $item["date"]); ?><span class="hidden">t=<?php echo $item["date"]; ?></span></td>
                    <td><?php echo $item["hash"] ?></td>
                    <td><?php echo $item["filesize"] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <input class="btn btn-danger" type="submit" value="Delete checked" name="process">
</form>

<p>Total sum of your distinct uploads: <?php echo $total_size; ?>.</p>
