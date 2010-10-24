<?php
/*
 * Copyright 2009-2010 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class File_mod extends Model {

  function __construct()
  {
    parent::Model();
  }

  // Returns an unused ID
  // TODO: make threadsafe
  function new_id()
  {
    $id = $this->random_id(3,6);

    if ($this->id_exists($id) || $id == 'file') {
      return $this->new_id();
    } else {
      return $id;
    }
  }

  function id_exists($id)
  {
    if(!$id) {
      return false;
    }

    $sql = '
      SELECT id
      FROM `files`
      WHERE `id` = ?
      LIMIT 1';
    $query = $this->db->query($sql, array($id));

    if ($query->num_rows() == 1) {
      return true;
    } else {
      return false;
    }
  }

  function get_filedata($id)
  {
    $sql = '
      SELECT hash,filename,mimetype
      FROM `files`
      WHERE `id` = ?
      LIMIT 1';
    $query = $this->db->query($sql, array($id));

    if ($query->num_rows() == 1) {
      $return = $query->result_array();
      return $return[0];
    } else {
      return false;
    }
  }

  // return the folder in which the file with $hash is stored
  function folder($hash) {
    return $this->config->item('upload_path').'/'.substr($hash, 0, 3);
  }

  // Returns the full path to the file with $hash
  function file($hash) {
    return $this->folder($hash).'/'.$hash;
  }

  function hash_password($password)
  {
    return sha1($this->config->item('passwordsalt').$password);
  }

  // Returns the password submitted by the user
  function get_password()
  {
    $password = $this->input->post('password');
    if ($password !== false) {
      return $this->hash_password($password);
    } elseif (isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW'] != '') {
      return $this->hash_password($_SERVER['PHP_AUTH_PW']);
    }
    return 'NULL';
  }

  // Add a hash to the DB
  // TODO: Should only update not insert; see new_id()
  function add_file($hash, $id, $filename)
  {
    $mimetype = exec(FCPATH.'scripts/mimetype -b --orig-name '.escapeshellarg($filename).' '.escapeshellarg($this->file($hash)));
    $query = $this->db->query('
      INSERT INTO `files` (`hash`, `id`, `filename`, `password`, `date`, `mimetype`)
      VALUES (?, ?, ?, ?, ?, ?)',
      array($hash, $id, $filename, $this->get_password(), time(), $mimetype));
  }

  function show_url($id, $mode)
  {
    $data = array();
    $redirect = false;

    if ($mode) {
      $data['url'] = site_url($id).'/'.$mode;
    } else {
      $data['url'] = site_url($id).'/';

      $filedata = $this->get_filedata($id);
      $file = $this->file($filedata['hash']);
      $type = $filedata['mimetype'] ? $filedata['mimetype'] : exec(FCPATH.'scripts/mimetype -b --orig-name '.escapeshellarg($filedata['filename']).' '.escapeshellarg($file));
      $mode = $this->mime2extension($type);
      $mode = $this->filename2extension($filedata['filename']) ? $this->filename2extension($filedata['filename']) : $mode;

      // If we detected a highlightable file redirect,
      // otherwise show the URL because browsers would just show a DL dialog
      if ($mode) {
        $redirect = true;
      }
    }

    if ($this->var->cli_client) {
      echo $data['url']."\n";
    } else {
      if ($redirect) {
        redirect($data['url']);
      } else {
        $this->load->view('file/header', $data);
        $this->load->view('file/show_url', $data);
        $this->load->view('file/footer', $data);
      }
    }
  }

  // download a given ID
  // TODO: make smaller
  function download()
  {
    $data = array();
    $id = $this->uri->segment(1);
    $mode = $this->uri->segment(2);

    $filedata = $this->get_filedata($id);
    $file = $this->file($filedata['hash']);
    
    if ($this->id_exists($id) && file_exists($file)) {
      // MODIFIED SINCE SUPPORT -- START
      // helps to keep traffic low when reloading an image
      $filedate = filectime($file);
      $etag = strtolower(md5_file($file));
      $modified = true;

      // No need to check because different files have different IDs/hashes
      if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
          $modified = false;
      }

      if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        $oldtag = trim(strtolower($_SERVER['HTTP_IF_NONE_MATCH']), '"');
        if($oldtag == $etag) {
          $modified = false;
        } else {
          $modified = true;
        }
      }
      // MODIFIED SINCE SUPPORT -- END

      if (!$modified) {
        header("HTTP/1.1 304 Not Modified");
        header('Etag: "'.$etag.'"');
      } else {
        $type = $filedata['mimetype'] ? $filedata['mimetype'] : exec(FCPATH.'scripts/mimetype -b --orig-name '.escapeshellarg($filedata['filename']).' '.escapeshellarg($file));

        // /$mode at the end of the URL overwrites autodetection
        if (!$mode && substr_count(ltrim($this->uri->uri_string(), "/"), '/') >= 1) {
          $mode = $this->mime2extension($type);
          $mode = $this->filename2extension($filedata['filename']) ? $this->filename2extension($filedata['filename']) : $mode;
        }

        // TODO: cleanup conditions
        if ($mode && $mode != 'plain' && $mode != 'qr'
        && $this->mime2extension($type)
        && filesize($file) <= $this->config->item('upload_max_text_size')
        ) {
          $data['title'] = $filedata['filename'];
          $data['raw_link'] = site_url($id);
          $data['new_link'] = site_url();
          $data['plain_link'] = site_url($id.'/plain');
          $data['auto_link'] = site_url($id).'/';
          $data['rmd_link'] = site_url($id.'/rmd');

          header("Content-Type: text/html\n");
          if ($mode) {
            $data['current_highlight'] = $mode;
          } else {
            $data['current_highlight'] = $this->mime2extension($type);
          }
          echo $this->load->view('file/html_header', $data, true);
          $this->load->library("MemcacheLibrary");
          if (! $cached = $this->memcachelibrary->get($filedata['hash'].'_'.$mode)) {
            ob_start();
            if ($mode == "rmd") {
                echo '<td class="markdownrender">'."\n";
                passthru('/usr/bin/perl /usr/bin/perlbin/vendor/Markdown.pl '.escapeshellarg($file));
            } else {
              echo '<td class="numbers"><pre>';
              // generate line numbers (links)
              passthru('/usr/bin/perl -ne \'print "<a href=\"#n$.\" class=\"no\" id=\"n$.\">$.</a>\n"\' '.escapeshellarg($file));
              echo '</pre></td><td class="code">'."\n";
              $this->load->library('geshi');
              $this->geshi->initialize(array('set_language' => $mode, 'set_source' => file_get_contents($file), 'enable_classes' => 'true'));
              echo $this->geshi->output();
            }
            $cached = ob_get_contents();
            ob_end_clean();
            $this->memcachelibrary->set($filedata['hash'].'_'.$mode, $cached, 100);
          }
          echo $cached;
          echo $this->load->view('file/html_footer', $data, true);
        } else {
          if ($mode == 'plain') {
            header("Content-Type: text/plain\n");
          } elseif ($mode == "qr") {
            header("Content-disposition: inline; filename=\"".$id."_qr.png\"\n");
            header("Content-Type: image/png\n");
            passthru('/usr/bin/qrencode -s 10 -o - '.escapeshellarg(site_url($id).'/'));
            exit();
          } else {
            header("Content-Type: ".$type."\n");
          }
          header("Content-disposition: inline; filename=\"".$filedata['filename']."\"\n");
          header("Content-Length: ".filesize($file)."\n");
          header("Last-Modified: ".date('D, d M Y H:i:s', $filedate)." GMT");
          header('Etag: "'.$etag.'"');
          $fp = fopen($file,"r");
          while (!feof($fp)) {
            echo fread($fp,4096);
          }
          fclose($fp);
        }
      }
      exit();
    } else {
      // TODO: remove -controller function has been removed
      $this->load->view('file/header');
      $this->load->view('file/non_existant');
      $this->load->view('file/footer');
    }
  }

  private function unused_file($hash)
  {
    $sql = '
      SELECT id
      FROM `files`
      WHERE `hash` = ?
      LIMIT 1';
    $query = $this->db->query($sql, array($hash));

    if ($query->num_rows() == 0) {
      return true;
    } else {
      return false;
    }
  }

  function delete_id($id, $password)
  {
    $filedata = $this->get_filedata($id);
    $password = $this->get_password();

    if ($password == "NULL") {
      return false;
    }

    if(!$this->id_exists($id)) {
      return false;
    }

    $sql = '
      DELETE
      FROM `files`
      WHERE `id` = ?
      AND password = ?
      LIMIT 1';
    $this->db->query($sql, array($id, $password));

    if($this->id_exists($id))  {
      return false;
    }

    if($this->unused_file($filedata['hash'])) {
      unlink($this->file($filedata['hash']));
      @rmdir($this->folder($filedata['hash']));
    }
    return true;
  }

  // Generate a random ID
  private function random_id($min_length, $max_length)
  {
    $random = '';
    $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $char_list .= "abcdefghijklmnopqrstuvwxyz";
    $char_list .= "1234567890";
    $length = rand()%($max_length-$min_length) + $min_length;

    for($i = 0; $i < $max_length; $i++) {
      if (strlen($random) == $length) break;
      $random .= substr($char_list,(rand()%(strlen($char_list))), 1);
    }
    return $random;
  }

  // Map MIME types to extensions needed for highlighting
  function mime2extension($type)
  {
    $typearray = array(
    'text/plain' => 'text',
    'text/x-python' => 'python',
    'text/x-csrc' => 'c',
    'text/x-chdr' => 'c',
    'text/x-c++hdr' => 'c',
    'text/x-c++src' => 'cpp',
    'text/x-patch' => 'diff',
    'text/x-lua' => 'lua',
    'text/x-java' => 'java',
    'text/x-haskell' => 'haskell',
    'text/x-literate-haskell' => 'haskell',
    'text/x-subviewer' => 'bash',
    'text/x-makefile' => 'make',
    #'text/x-log' => 'log',
    'text/html' => 'html',
    'text/css' => 'css',
    'message/rfc822' => 'email',
    #'image/svg+xml' => 'xml',
    'application/x-perl' => 'perl',
    'application/xml' => 'xml',
    'application/javascript' => 'javascript',
    'application/x-desktop' => 'text',
    'application/x-m4' => 'text',
    'application/x-awk' => 'text',
    'application/x-java' => 'java',
    'application/x-php' => 'php',
    'application/x-ruby' => 'ruby',
    'application/x-shellscript' => 'bash',
    'application/x-x509-ca-cert' => 'text',
    'application/mbox' => 'email',
    'application/x-genesis-rom' => 'text',
    'application/x-applix-spreadsheet' => 'actionscript'
    );
    if (array_key_exists($type, $typearray)) return $typearray[$type];

    if (strpos($type, 'text/') === 0) return 'text';

    # default
    return false;
  }

  // Map special filenames to extensions
  function filename2extension($name)
  {
    $namearray = array(
      'PKGBUILD' => 'bash',
      '.vimrc' => 'vim'
    );
    if (array_key_exists($name, $namearray)) return $namearray[$name];

    return false;
  }

}

# vim: set ts=2 sw=2 et:
/* End of file file_mod.php */
/* Location: ./system/application/models/file_mod.php */
