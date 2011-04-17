<?php 
/* App schema generated on: 2011-04-17 16:10:46 : 1303071046*/
class AppSchema extends CakeSchema {
	var $name = 'App';

	var $file = 'schema_2.php';

	function before($event = array()) {
		return true;
	}

	function after($event = array()) {
	}

	var $addresses = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'line1' => array('type' => 'string', 'null' => true, 'length' => 128),
		'line2' => array('type' => 'string', 'null' => true, 'length' => 128),
		'locality' => array('type' => 'string', 'null' => true, 'length' => 128),
		'state' => array('type' => 'string', 'null' => true, 'length' => 128),
		'postal_code' => array('type' => 'string', 'null' => true, 'length' => 16),
		'country' => array('type' => 'string', 'null' => true, 'length' => 128),
		'type' => array('type' => 'string', 'null' => true, 'length' => 2),
		'co_person_id' => array('type' => 'integer', 'null' => true),
		'org_person_id' => array('type' => 'integer', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_addresses_i1' => array('unique' => false, 'column' => 'co_person_id'), 'cm_addresses_i2' => array('unique' => false, 'column' => 'org_person_id')),
		'tableParameters' => array()
	);
	var $api_users = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'username' => array('type' => 'string', 'null' => true, 'length' => 50),
		'password' => array('type' => 'string', 'null' => true, 'length' => 40),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_api_users_i1' => array('unique' => false, 'column' => 'username')),
		'tableParameters' => array()
	);
	var $co_extended_attributes = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'co_id' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'null' => true, 'length' => 64),
		'type' => array('type' => 'string', 'null' => true, 'length' => 32),
		'indx' => array('type' => 'boolean', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'display_name' => array('type' => 'string', 'null' => true, 'length' => 64),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_co_extended_attributes_co_id_key' => array('unique' => true, 'column' => array('co_id', 'name'))),
		'tableParameters' => array()
	);
	var $co_group_members = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'co_group_id' => array('type' => 'integer', 'null' => false),
		'co_person_id' => array('type' => 'integer', 'null' => false),
		'member' => array('type' => 'boolean', 'null' => true),
		'owner' => array('type' => 'boolean', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_co_group_members_co_group_id_key' => array('unique' => true, 'column' => array('co_group_id', 'co_person_id')), 'cm_co_group_members_i1' => array('unique' => false, 'column' => 'co_group_id'), 'cm_co_group_members_i2' => array('unique' => false, 'column' => 'co_person_id')),
		'tableParameters' => array()
	);
	var $co_groups = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'co_id' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'null' => true, 'length' => 128),
		'description' => array('type' => 'string', 'null' => true, 'length' => 256),
		'open' => array('type' => 'boolean', 'null' => true),
		'status' => array('type' => 'string', 'null' => true, 'length' => 2),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_co_groups_co_id_key' => array('unique' => true, 'column' => array('co_id', 'name')), 'cm_co_groups_i1' => array('unique' => false, 'column' => 'co_id'), 'cm_co_groups_i2' => array('unique' => false, 'column' => 'name')),
		'tableParameters' => array()
	);
	var $co_invites = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'co_person_id' => array('type' => 'integer', 'null' => false),
		'invitation' => array('type' => 'string', 'null' => false, 'length' => 48),
		'expires' => array('type' => 'datetime', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'mail' => array('type' => 'string', 'null' => true, 'length' => 256),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_co_invites_i1' => array('unique' => false, 'column' => 'co_person_id'), 'cm_co_invites_i2' => array('unique' => false, 'column' => 'invitation')),
		'tableParameters' => array()
	);
	var $co_people = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'edu_person_affiliation' => array('type' => 'string', 'null' => true, 'length' => 32),
		'title' => array('type' => 'string', 'null' => true, 'length' => 128),
		'o' => array('type' => 'string', 'null' => true, 'length' => 128),
		'ou' => array('type' => 'string', 'null' => true, 'length' => 128),
		'valid_from' => array('type' => 'datetime', 'null' => true),
		'valid_through' => array('type' => 'datetime', 'null' => true),
		'status' => array('type' => 'string', 'null' => true, 'length' => 2),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id')),
		'tableParameters' => array()
	);
	var $co_person_sources = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'co_id' => array('type' => 'integer', 'null' => false),
		'co_person_id' => array('type' => 'integer', 'null' => false),
		'org_person_id' => array('type' => 'integer', 'null' => false),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'cou_id' => array('type' => 'integer', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_co_person_sources_i1' => array('unique' => false, 'column' => 'org_person_id'), 'cm_co_person_sources_i2' => array('unique' => false, 'column' => 'co_id'), 'cm_co_person_sources_i3' => array('unique' => false, 'column' => 'cou_id'), 'cm_co_person_sources_i4' => array('unique' => false, 'column' => 'co_person_id')),
		'tableParameters' => array()
	);
	var $cos = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => true, 'length' => 128),
		'description' => array('type' => 'string', 'null' => true, 'length' => 128),
		'status' => array('type' => 'string', 'null' => true, 'length' => 2),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_cos_name_key' => array('unique' => true, 'column' => 'name'), 'cm_cos_i1' => array('unique' => false, 'column' => 'name')),
		'tableParameters' => array()
	);
	var $cous = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'co_id' => array('type' => 'integer', 'null' => false),
		'name' => array('type' => 'string', 'null' => true, 'length' => 128),
		'description' => array('type' => 'string', 'null' => true, 'length' => 128),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_cous_co_id_key' => array('unique' => true, 'column' => array('co_id', 'name')), 'cm_cous_i1' => array('unique' => false, 'column' => 'co_id'), 'cm_cous_i2' => array('unique' => false, 'column' => 'name')),
		'tableParameters' => array()
	);
	var $email_addresses = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'mail' => array('type' => 'string', 'null' => true, 'length' => 256),
		'type' => array('type' => 'string', 'null' => true, 'length' => 2),
		'co_person_id' => array('type' => 'integer', 'null' => true),
		'org_person_id' => array('type' => 'integer', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_email_addresses_i1' => array('unique' => false, 'column' => 'co_person_id'), 'cm_email_addresses_i2' => array('unique' => false, 'column' => 'org_person_id')),
		'tableParameters' => array()
	);
	var $identifiers = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'identifier' => array('type' => 'string', 'null' => true, 'length' => 256),
		'type' => array('type' => 'string', 'null' => true, 'length' => 32),
		'login' => array('type' => 'boolean', 'null' => true),
		'co_person_id' => array('type' => 'integer', 'null' => true),
		'org_person_id' => array('type' => 'integer', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_identifiers_identifier_key' => array('unique' => true, 'column' => 'identifier'), 'cm_identifiers_i1' => array('unique' => false, 'column' => 'identifier'), 'cm_identifiers_i2' => array('unique' => false, 'column' => array('identifier', 'type'))),
		'tableParameters' => array()
	);
	var $names = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'honorific' => array('type' => 'string', 'null' => true, 'length' => 32),
		'given' => array('type' => 'string', 'null' => true, 'length' => 128),
		'middle' => array('type' => 'string', 'null' => true, 'length' => 128),
		'family' => array('type' => 'string', 'null' => true, 'length' => 128),
		'suffix' => array('type' => 'string', 'null' => true, 'length' => 32),
		'type' => array('type' => 'string', 'null' => true, 'length' => 2),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'co_person_id' => array('type' => 'integer', 'null' => true),
		'org_person_id' => array('type' => 'integer', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id')),
		'tableParameters' => array()
	);
	var $org_people = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'edu_person_affiliation' => array('type' => 'string', 'null' => true, 'length' => 32),
		'title' => array('type' => 'string', 'null' => true, 'length' => 128),
		'o' => array('type' => 'string', 'null' => true, 'length' => 128),
		'ou' => array('type' => 'string', 'null' => true, 'length' => 128),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'organization_id' => array('type' => 'integer', 'null' => true),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id')),
		'tableParameters' => array()
	);
	var $organizations = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => true, 'length' => 256),
		'domain' => array('type' => 'string', 'null' => true, 'length' => 256),
		'directory' => array('type' => 'string', 'null' => true, 'length' => 256),
		'search_base' => array('type' => 'string', 'null' => true, 'length' => 256),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id')),
		'tableParameters' => array()
	);
	var $telephone_numbers = array(
		'id' => array('type' => 'integer', 'null' => false, 'default' => NULL, 'length' => 11, 'key' => 'primary'),
		'number' => array('type' => 'string', 'null' => true, 'length' => 64),
		'co_person_id' => array('type' => 'integer', 'null' => true),
		'org_person_id' => array('type' => 'integer', 'null' => true),
		'created' => array('type' => 'datetime', 'null' => true),
		'modified' => array('type' => 'datetime', 'null' => true),
		'type' => array('type' => 'string', 'null' => true, 'length' => 2),
		'indexes' => array('PRIMARY' => array('unique' => true, 'column' => 'id'), 'cm_telephone_numbers_i1' => array('unique' => false, 'column' => 'co_person_id')),
		'tableParameters' => array()
	);
	var $users = array(
		'username' => array('type' => 'text', 'null' => true),
		'password' => array('type' => 'text', 'null' => true),
		'api_user_id' => array('type' => 'integer', 'null' => true),
		'org_person_id' => array('type' => 'integer', 'null' => true),
		'indexes' => array(),
		'tableParameters' => array()
	);
}
?>