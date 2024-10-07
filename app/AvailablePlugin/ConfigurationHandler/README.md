# Configuration Handler

Command line tool (Cake console command), implemented as a standalone experimental plugin, supporting two modes:
- Mode 1: Export configuration for the specified CO to a JSON file
  - Configuration only, no data
  - CO Groups are configuration, CO Group Members are data
  - COUs are configuration
  - Departments and Organizations are data, not configuration

- Mode 2: Import configuration from a JSON file to a specified CO

### Supported Models

```php
const  SUPPORTED_MODELS = array(
  "ApiUser",
  "AttributeEnumeration",
  "CoDashboard" => array("CoDashboardWidget"),
  "CoEnrollmentFlow" => array(
    "CoPipeline",
    "CoEnrollmentFlowWedge",
    "CoEnrollmentSource",
    "CoEnrollmentAttribute" => array("CoEnrollmentAttributeDefault")
  ),
  "CoExpirationPolicy",
  "CoExtendedType",
  "CoGroup" => array(
    "CoGroupNesting",
    "CoGroupOisMapping",
  ),
  "CoIdentifierAssignment",
  "CoIdentifierValidator",
  "CoLocalization",
  "CoMessageTemplate",
  "CoNavigationLink",
  "CoPipeline",
  "CoProvisioningTarget",
  "DataFilter" => array(
    "OrgIdentitySourceFilter",
    "CoProvisioningTargetFilter",
  ),
  "CoSelfServicePermission",
  "CoSetting",
  "CoTermsAndConditions",
  "CoTheme",
  "Cou" => array(
    "CoTermsAndConditions"
  ),
  "Dictionary" => array(
    "DictionaryEntry",
  ),
  "OrgIdentitySource" => array(
    "CoGroupOisMapping",
    "OrgIdentitySourceFilter",
  ),
  "Server" => array(
    "SqlServer",
    "Oauth2Server",
    "HttpServer",
    "KafkaServer",
    "MatchServer" => array("MatchServerAttribute"),
  ),
  "VettingStep"
);
```

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

We allow callbacks to run everytime we save or update a record. There are occasions where we disable them.
- Instantiate a child Model or plugin configuration.
  - CoDashboardWidget
  - CoEnrollmentFlowWedge
  - OrgIdentitySource


### Run Export
```bash
cd /path/to/comanage/app
./Console/cake job ConfigurationHandler.Export --coid 2 -l All -s -e 1235477978
```
- --coid, Numeric CO ID to run the export configuration for
- -l, list of Models to export the configuration for
- -e, salt for encryption used for sensitive fields, e.g. passwords

### Run import
```bash
cd /path/to/comanage/app
./Console/cake job ConfigurationHandler.Import --coid 72 --filename configuration_co2_1694967754.json -s -d  -e 1235477978
```

- -d, dry run
- --config-file, the name of the JSON Configuration file. The path lives under the `/path/to/comanage/local/Config` directory
- --coid, the number of CO to import the configuration to
- -e, salt string used to decrypt sensitive fields, e.g., passwords. It matches the one we provided on encryption