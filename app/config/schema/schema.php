<?php 
/* SVN FILE: $Id$ */
/* App schema generated on: 2010-09-14 17:09:45 : 1284500685*/
class AppSchema extends CakeSchema {
	var $name = 'App';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $cm_co_invites = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'cm_org_person_id' => array('type' => 'integer', 'null' => true),
		'invitation' => array('type' => 'string', 'null' => true, 'length' => 16),
		'expires' => array('type' => 'datetime', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id')),
		'tableParameters' => array()
	);
	var $cm_org_people = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'edu_person_targeted_id' => array('type' => 'string', 'null' => true, 'length' => 256),
		'cn' => array('type' => 'string', 'null' => true, 'length' => 128),
		'given_name' => array('type' => 'string', 'null' => true, 'length' => 128),
		'sn' => array('type' => 'string', 'null' => true, 'length' => 128),
		'edu_person_affiliation' => array('type' => 'string', 'null' => true, 'length' => 32),
		'title' => array('type' => 'string', 'null' => true, 'length' => 128),
		'o' => array('type' => 'string', 'null' => true, 'length' => 128),
		'ou' => array('type' => 'string', 'null' => true, 'length' => 128),
		'mail' => array('type' => 'string', 'null' => true, 'length' => 256),
		'telephone_number' => array('type' => 'string', 'null' => true, 'length' => 64),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_org_people_edu_person_targeted_id_key' => array('unique' => true, 'column' => 'edu_person_targeted_id')),
		'tableParameters' => array()
	);
	var $cm_org_person_identifiers = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'cm_org_person_id' => array('type' => 'integer', 'null' => true),
		'identifier_t' => array('type' => 'string', 'null' => true, 'length' => 32),
		'identifier' => array('type' => 'string', 'null' => true, 'length' => 256),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id')),
		'tableParameters' => array()
	);
}
?>