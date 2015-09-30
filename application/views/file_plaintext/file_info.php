<?php if($filedata): ?>
ID: <?php echo $id; ?>

Filename: <?php echo $filedata["filename"]; ?>

Date of upload: <?php echo date("r", $filedata["date"]); ?>

Date of removal: <?php echo $timeout; ?>

Size: <?php echo format_bytes($filedata["filesize"]); ?>

Mimetype: <?php echo $filedata["mimetype"]; ?>

Hash (MD5): <?php echo $filedata["hash"]; ?>

<?php endif; ?>
