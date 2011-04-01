-- Postgres specific SQL statements used for development
-- -----------------------------------------------------
-- Version: $Revision$
-- Date: $Date$
-- 
-- Copyright (C) 2010-2011 University Corporation for Advanced Internet Development, Inc.
--  
-- Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
-- the License. You may obtain a copy of the License at
--  
-- http://www.apache.org/licenses/LICENSE-2.0
-- 
-- Unless required by applicable law or agreed to in writing, software distributed under
-- the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
-- KIND, either express or implied. See the License for the specific language governing
-- permissions and limitations under the License.
   
-- cm_cos

CREATE TABLE cm_cos (
  id SERIAL PRIMARY KEY,
  name VARCHAR(128) UNIQUE,
  description VARCHAR(128),
  status VARCHAR(2),
  created TIMESTAMP,
  modified TIMESTAMP  
);

CREATE INDEX cm_cos_i1 ON cm_cos (name);

-- cm_organizations

CREATE TABLE cm_organizations (
  id SERIAL PRIMARY KEY,
  name VARCHAR(256),
  domain VARCHAR(256),
  directory VARCHAR(256),
  -- XXX searchbase probably doesn't belong here
  search_base VARCHAR(256),
  created TIMESTAMP,
  modified TIMESTAMP
);

-- cm_names
-- This isn't currently an MVPA, but could be by adding co_ and org_person_id columns

CREATE TABLE cm_names (
  id SERIAL PRIMARY KEY,
  honorific VARCHAR(32),
  given VARCHAR(128),
  middle VARCHAR(128),
  family VARCHAR(128),
  suffix VARCHAR(32),
  type VARCHAR(2),
  co_person_id INTEGER REFERENCES cm_co_people(id),
  org_person_id INTEGER REFERENCES cm_org_people(id),
  created TIMESTAMP,
  modified TIMESTAMP
);
-- XXX Add indices

-- cm_org_persons

CREATE TABLE cm_org_people (
  id SERIAL PRIMARY KEY,
  edu_person_affiliation VARCHAR(32),
  title VARCHAR(128),
  o VARCHAR(128),
  ou VARCHAR(128),
  organization_id INTEGER REFERENCES cm_organizations(id),  // XXX not implemented
  created TIMESTAMP,
  modified TIMESTAMP
);
-- XXX Add indices

-- cm_co_people

CREATE TABLE cm_co_people (
  id SERIAL PRIMARY KEY,
  edu_person_affiliation VARCHAR(32),
  title VARCHAR(128),
  o VARCHAR(128),
  ou VARCHAR(128),
  valid_from TIMESTAMP,
  valid_through TIMESTAMP,
  status VARCHAR(2),
  created TIMESTAMP,
  modified TIMESTAMP  
);
-- XXX Add indices

-- cm_co_extended_attributes

CREATE TABLE cm_co_extended_attributes (
  id SERIAL PRIMARY KEY,
  co_id INTEGER NOT NULL REFERENCES cm_cos(id),
  name VARCHAR(64),
  display_name VARCHAR(64),
  type VARCHAR(32),
  indx BOOLEAN,
  created TIMESTAMP,
  modified TIMESTAMP,
  UNIQUE(co_id,name)
);
-- XXX Add indices

-- cm_co_person_sources

CREATE TABLE cm_co_person_sources (
  id SERIAL PRIMARY KEY,
  co_id INTEGER NOT NULL REFERENCES cm_cos(id),
  co_person_id INTEGER NOT NULL REFERENCES cm_co_people(id),
  org_person_id INTEGER NOT NULL REFERENCES cm_org_people(id),
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_co_person_sources_i1 ON cm_co_person_sources(org_person_id);
CREATE INDEX cm_co_person_sources_i2 ON cm_co_person_sources(co_id);

-- cm_identifiers

CREATE TABLE cm_identifiers (
  id SERIAL PRIMARY KEY,
  identifier VARCHAR(256) UNIQUE,
  type VARCHAR(32),
  login BOOLEAN,
  co_person_id INTEGER REFERENCES cm_co_people(id),
  org_person_id INTEGER REFERENCES cm_org_people(id),  
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_identifiers_i1 ON cm_identifiers(identifier);
CREATE INDEX cm_identifiers_i2 ON cm_identifiers(identifier,type);

-- cm_co_invites

CREATE TABLE cm_co_invites (
  id SERIAL PRIMARY KEY,
  co_person_id INTEGER NOT NULL REFERENCES cm_co_people(id),
  invitation VARCHAR(48) NOT NULL,
  mail VARCHAR(256),
  expires TIMESTAMP,
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_co_invites_i1 ON cm_co_invites (co_person_id);
CREATE INDEX cm_co_invites_i2 ON cm_co_invites (invitation);

-- cm_addresses

CREATE TABLE cm_addresses (
  id SERIAL PRIMARY KEY,
  line_1 VARCHAR(128),
  line_2 VARCHAR(128),
  locality VARCHAR(128),
  state VARCHAR(128),
  postal_code VARCHAR(16),
  country VARCHAR(128),
  type VARCHAR(2),
  co_person_id INTEGER REFERENCES cm_co_people(id),
  org_person_id INTEGER REFERENCES cm_org_people(id),
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_addresses_i1 ON cm_addresses(co_person_id);
CREATE INDEX cm_addresses_i2 ON cm_addresses(org_person_id);

-- cm_email addresses

CREATE TABLE cm_email_addresses (
  id SERIAL PRIMARY KEY,
  mail VARCHAR(256),
  type VARCHAR(2),
  co_person_id INTEGER REFERENCES cm_co_people(id),
  org_person_id INTEGER REFERENCES cm_org_people(id),
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_email_addresses_i1 ON cm_email_addresses(co_person_id);
CREATE INDEX cm_email_addresses_i2 ON cm_email_addresses(org_person_id);

-- cm_telephone_numbers

CREATE TABLE cm_telephone_numbers (
  id SERIAL PRIMARY KEY,
  number VARCHAR(64),
  type VARCHAR(2),
  co_person_id INTEGER REFERENCES cm_co_people(id),
  org_person_id INTEGER REFERENCES cm_org_people(id),
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_telephone_numbers_i1 ON cm_telephone_numbers(co_person_id);
CREATE INDEX cm_telephone_numbers_i2 ON cm_telephone_numbers(org_person_id);

-- cm_api_users

CREATE TABLE cm_api_users (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50),
  password VARCHAR(40),
  created TIMESTAMP,
  modified TIMESTAMP
);

CREATE INDEX cm_api_users_i1 ON cm_api_users(username);

-- cm_users view

CREATE VIEW cm_users AS
SELECT a.username as username, a.password as password, a.id as api_user_id, null as org_person_id
FROM cm_api_users a
UNION SELECT i.identifier as username, '*' as password, null as api_user_id, i.org_person_id as org_person_id
FROM cm_identifiers i
WHERE i.login=true;

-- cm_co_groups

CREATE TABLE cm_co_groups (
  id SERIAL PRIMARY KEY,
  co_id INTEGER NOT NULL REFERENCES cm_cos(id),
  name VARCHAR(128),
  description VARCHAR(256),
  open BOOLEAN,
  status VARCHAR(2),
  created TIMESTAMP,
  modified TIMESTAMP,
  UNIQUE(co_id, name)
);

CREATE INDEX cm_co_groups_i1 ON cm_co_groups(co_id);
CREATE INDEX cm_co_groups_i2 ON cm_co_groups(name);

-- cm_co_group_members

CREATE TABLE cm_co_group_members (
  id SERIAL PRIMARY KEY,
  co_group_id INTEGER NOT NULL REFERENCES cm_co_groups(id),
  co_person_id INTEGER NOT NULL REFERENCES cm_co_people(id),
  member BOOLEAN,
  owner BOOLEAN,
  created TIMESTAMP,
  modified TIMESTAMP,
  UNIQUE(co_group_id,co_person_id)
);

CREATE INDEX cm_co_group_members_i1 ON cm_co_group_members(co_group_id);
CREATE INDEX cm_co_group_members_i2 ON cm_co_group_members(co_person_id);