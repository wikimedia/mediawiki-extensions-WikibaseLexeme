# Cypress tests

## Setup
To run Cypress browser tests against a local mediawiki install, set these environment variables. Depending on your local
setup, these might be different.
```bash
export MW_SERVER=http://default.mediawiki.mwdd.localhost:8080/
export MW_SCRIPT_PATH=w/
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
