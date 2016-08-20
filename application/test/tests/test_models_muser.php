<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_models_muser extends \test\Test {

	public function __construct()
	{
		parent::__construct();
	}

	public function init()
	{
	}

	public function cleanup()
	{
	}

	public function test_valid_username()
	{
		$CI =& get_instance();

		$this->t->is($CI->muser->valid_username("thisisbob42"), true, "valid username");
		$this->t->is($CI->muser->valid_username("31337"), true, "valid username");
		$this->t->is($CI->muser->valid_username("thisisjoe"), true, "valid username");
		$this->t->is($CI->muser->valid_username("1234567890123456789012345678901"), true, "31 chars");
		$this->t->is($CI->muser->valid_username("12345678901234567890123456789012"), true, "32 chars");

		$this->t->is($CI->muser->valid_username("Joe"), false, "contains uppercase");
		$this->t->is($CI->muser->valid_username("joe_bob"), false, "contains underscore");
		$this->t->is($CI->muser->valid_username("joe-bob"), false, "contains dash");
		$this->t->is($CI->muser->valid_username("123456789012345678901234567890123"), false, "33 chars");
		$this->t->is($CI->muser->valid_username("1234567890123456789012345678901234"), false, "34 chars");
	}

	public function test_valid_email()
	{
		$CI =& get_instance();

		$this->t->is($CI->muser->valid_email("joe@bob.com"), true, "valid email");
		$this->t->is($CI->muser->valid_email("joe+mailbox@bob.com"), true, "valid email");
		$this->t->is($CI->muser->valid_email("bob@fancyaddress.net"), true, "valid email");

		$this->t->is($CI->muser->valid_email("joebob.com"), false, "missing @");
	}

	public function test_delete_user()
	{
		$CI =& get_instance();
		$CI->muser->add_user("userdeltest1", "supersecret", "tester@localhost.lan", null);
		$this->t->is($CI->muser->username_exists("userdeltest1"), true, "User should exist after creation");

		$ret = $CI->muser->delete_user("userdeltest1", "wrongpassword");
		$this->t->is($ret, false, "Deletion should fail with incorrect password");

		$ret = $CI->muser->delete_user("userdeltest1", "");
		$this->t->is($ret, false, "Deletion should fail with empty password");

		$this->t->is($CI->muser->username_exists("userdeltest1"), true, "User should exist after failed deletions");

		$ret = $CI->muser->delete_user("userdeltest1", "supersecret");
		$this->t->is($ret, true, "Deletion should succeed with correct data");
		$this->t->is($CI->muser->username_exists("userdeltest1"), false, "User should not exist after deletion");
	}

	public function test_delete_user_verifyFilesDeleted()
	{
		$CI =& get_instance();

		$id = "testid1";
		$id2 = "testid2";
		$content = "test content";
		$filename = "some cool name";
		$username = "testuser1";
		$password = "testpass";

		$CI->muser->add_user($username, $password, "tester@localhost.lan", null);
		$userid = $CI->muser->get_userid_by_name($username);

		$CI->muser->add_user("joe", "joeisawesome", "tester2@localhost.lan", null);
		$userid2 = $CI->muser->get_userid_by_name("joe");

		\service\files::add_file_data($userid, $id, $content, $filename);
		\service\files::add_file_data($userid2, $id2, $content, $filename);

		$mid = \service\files::create_multipaste([$id], $userid, [3,6])['url_id'];
		$mid2 = \service\files::create_multipaste([$id2], $userid2, [3,6])['url_id'];

		$this->t->is($CI->mfile->id_exists($id), true, "File exists after being added");
		$this->t->is($CI->mmultipaste->id_exists($mid), true, "Multipaste exists after creation");
		$this->t->is($CI->mfile->id_exists($id2), true, "File2 exists after being added");
		$this->t->is($CI->mmultipaste->id_exists($mid2), true, "Multipaste2 exists after creation");

		$ret = $CI->muser->delete_user($username, $password);
		$this->t->is($ret, true, "Delete user");

		$this->t->is($CI->mfile->id_exists($id), false, "File should be gone after deletion of user");
		$this->t->is($CI->mmultipaste->id_exists($mid), false, "Multipaste should be gone after deletion of user");
		$this->t->is($CI->mfile->id_exists($id2), true, "File2 owned by different user should still exist after deletion from other user");
		$this->t->is($CI->mmultipaste->id_exists($mid2), true, "Multipaste2 owned by different user should still exist after deletion from other user");
	}


}

