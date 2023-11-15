## INTRODUCTION

The PINS KL Import module is an administrator-only module to facilitate import of the legacy data from the Horizon-based Knowledge Library.

The primary use case for this module is:

- Import "Reading Lists" CSV into local taxonomy. 
- Import "Series" CSV into local taxonomy.
- Import "Authors" CSV into local taxonomy.
- Importing library metadata.

## REQUIREMENTS

### Contrib Modules

This module depends upon contrib modules:

Feeds (feeds) - Implementing its importing functions and hooking its events.
Tamper (tamper) - Data adjustments.
Feeds Tamper (feeds_tamper) - Data adjustments during imports.
Views (core) - Admin views.

### Document store

This module also depends upon the "Insight" tool, provided by Aiimi, having been previously run so that the massive file store of documents is already in place.  This will be accessed by Drupal either 1) directly on its local file store on the VM that it is running on, or 2) via a linux "mount" on the local file store which connects to a remote Azure Blob storage area.  This is a project management decision, this module does not force either path.

### Lookup lists

This module also depends upon lookup lists (Reading Lists, Authors, Series, etc.) already having been exported and made available for upload by this module.


## Document Metadata

This module also depends upon the main metadata export from the Horizon Knowledge Library system.  This is the "heavy lifting" part of the module.  Processing this metadata file is the key function of the module, linking the files in the document store to the correct nodes in Drupal, whilst implementing versioning etc.


## INSTALLATION

Install as you would normally install a contributed Drupal module.
See: https://www.drupal.org/node/895232 for further information.

## CONFIGURATION
- Configuration step #1
- Configuration step #2
- Configuration step #3

## MAINTAINERS

Current maintainers for Drupal 10:

- Stuart McMath (Grannytrolly) - https://www.drupal.org/u/grannytrolly
