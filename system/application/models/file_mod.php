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

  function new_id()
  {
    $id = $this->random_id(3,6);

    if ($this->id_exists($id)) {
      return $this->new_id();
    } else {
      return $id;
    }
  }

  function id_exists($id)
  {
    $sql = '
      SELECT id
      FROM `files`
      WHERE `id` = ?';
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
      SELECT hash,filename
      FROM `files`
      WHERE `id` = ?';
    $query = $this->db->query($sql, array($id));

    if ($query->num_rows() == 1) {
      $return = $query->result_array();
      return $return[0];
    } else {
      return false;
    }
  }

  function folder($hash) {
    return $this->config->item('upload_path').'/'.substr($hash, 0, 3);
  }

  function file($hash) {
    return $this->folder($hash).'/'.$hash;
  }

  function hash_password($password)
  {
    // TODO: move salt to config
    return sha1('w9yFMeU6ITrkrPBlRJfA'.$password);
  }

  private function unused_file($hash)
  {
    $sql = '
      SELECT id
      FROM `files`
      WHERE `hash` = ?';
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
    $password = $this->hash_password($password);

    $sql = '
      DELETE
      FROM `files`
      WHERE `id` = ?
      AND password = ?
      LIMIT 1';
    $query = $this->db->query($sql, array($id, $password));

    if($this->unused_file($filedata['hash'])) {
      unlink($this->file($filedata['hash']));
      // TODO: remove empty folders
    }
  }

  private function random_id($min_length, $max_length)
  {
    $random = '';
    $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $char_list .= "abcdefghijklmnopqrstuvwxyz";
    $char_list .= "1234567890";

    for($i = 0; $i < $max_length; $i++) {
      if (strlen($random) >= $min_length) {
        if (rand()%2 == 1) {
          break;
        }
      }
      $random .= substr($char_list,(rand()%(strlen($char_list))), 1);
    }
    return $random;
  }

  function mime2extension($type)
  {
    $typearray = array(
    'text/plain' => 'txt',
    'text/x-python' => 'py',
    'text/x-csrc' => 'c',
    'text/x-chdr' => 'h',
    'text/x-c++hdr' => 'h',
    'text/x-c++src' => 'cpp',
    'text/x-patch' => 'diff',
    'text/x-lua' => 'lua',
    'text/x-haskell' => 'hs',
    'text/x-literate-haskell' => 'hs',
    'text/x-subviewer' => 'sh',
    #'text/x-makefile' => 'make',
    #'text/x-log' => 'log',
    'text/html' => 'html',
    'text/css' => 'css',
    #'image/svg+xml' => 'xml',
    'application/x-perl' => 'pl',
    'application/xml' => 'xml',
    'application/javascript' => 'js',
    'application/x-desktop' => 'txt',
    'application/x-m4' => 'txt',
    'application/x-awk' => 'awk',
    'application/x-java' => 'java',
    'application/x-php' => 'php',
    'application/x-ruby' => 'rb',
    'application/x-shellscript' => 'sh',
    'application/x-x509-ca-cert' => 'txt',
    'application/mbox' => 'txt'
    );
    if (array_key_exists($type, $typearray)) return $typearray[$type];

    if (strpos($type, 'text/') === 0) return 'txt';

    # default
    return false;
  }

}

/* End of file file_mod.php */
/* Location: ./system/application/models/file_mod.php */
