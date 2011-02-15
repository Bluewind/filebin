Shell:
  curl -n -F "file=@/home/user/foo" <?php echo site_url(); ?>   (binary safe)
  cat file | curl -n -F "file=@-;filename=stdin" <?php echo site_url(); ?>   (binary safe)

Client:
Development (git): http://git.server-speed.net/users/flo/fb
Latest release: <?php echo $client_link."\n"; ?>
GPG sigs, older versions: <?php echo $client_link_dir."\n"; ?>

If you want to use authentication (needed for deleting) add the following to your ~/.netrc:
  machine paste.xinu.at password my_secret_password

