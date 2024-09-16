# Cypress tests

## Setup
To run Cypress browser tests against a local mediawiki install, set these environment variables. Depending on your local
setup, these might be different.
```bash
export MW_SERVER=http://default.mediawiki.mwdd.localhost:8080/
export MW_SCRIPT_PATH=w/
export MEDIAWIKI_USER=an_admin_username
export MEDIAWIKI_PASSWORD=the_password_for_that_user

# (Optional) Set the ID of an existing property with `string` as its datatype.
# This avoids creating new properties on every test run.
export WIKIBASE_PROPERTY_STRING=P444
```

## Run the tests
Use this command to run the tests in a terminal:
```bash
npm run cypress:run
```

Or you can open Cypress's GUI with this command:
```bash
npm run cypress:open
```
