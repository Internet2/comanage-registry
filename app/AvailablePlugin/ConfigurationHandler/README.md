### Unfinished configuration import

There are models that on creation instantiate the configuration type Model. Like the Server Model that instantiate the XxxServer Models.
Importing such a configuration with an XxxModel configuration not being finalized will fail because we do not skip validations and there are
certain fields that are required.

### Duplicates

Having multiple records of the following Models with the same `name` or `description` field will end into importing only the last parsed record.
List: 
- Dashboard
- CoEnrollmentFlow
- CoEnrollmentFlowWedge

### Callbacks

We allow callbacks to run everytime we save or update a record. There are occassions where we disable them.
- Instantiate a child Model or plugin configuration.
  - CoDashboardWidget
  - CoEnrollmentFlowWedge
  - OrgIdentitySource


### Run import

./Console/cake job ConfigurationHandler.Import --coid 72 --filename configuration_co2_1694967754.json -s -d

- -d, dry run
- --filename, provide only the filename. The path is fixed under the local/Config directory
- --coid, the number of CO to import the configuration to

### Run Export

./Console/cake job ConfigurationHandler.Export --coid 2 -l All -s

- --coid, the number of CO to export the configuration from
- -l, list of Models to export the configuration from