#!/bin/bash

## About: Install the CiviHR extensions using drush
## Usage: install.sh [--with-sample-data] [drush-options]
## Example: ./drush-install.sh --with-sample-data
## Example: ./drush-install.sh --root=/var/www/drupal -l newdomain.ex
## Example: ./drush-install.sh --with-sample-data --root=/var/www/drupal -l newdomain.ex

##################################
## List of extensions defining basic entity types
ENTITY_EXTS=\
org.civicrm.hrbank,\
org.civicrm.hrdemog,\
org.civicrm.hrident,\
org.civicrm.hrjobcontract,\
com.civicrm.hrjobroles,\
org.civicrm.hrabsence,\
org.civicrm.hrmed,\
org.civicrm.hrqual,\
org.civicrm.hrvisa,\
org.civicrm.hremergency,\
org.civicrm.hrcareer,\
uk.co.compucorp.contactaccessrights,\
uk.co.compucorp.civicrm.tasksassignments

## List of extensions defining applications/UIs on top of the basic entity types
APP_EXTS=\
org.civicrm.hrreport,\
org.civicrm.hrui,\
org.civicrm.hrcase,\
org.civicrm.hrim,\
org.civicrm.hrrecruitment,\
org.civicrm.reqangular,\
org.civicrm.contactsummary,\
org.civicrm.bootstrapcivicrm,\
org.civicrm.bootstrapcivihr

##
# Set Default localisation settings
# It expect one parameter ($1) which points to civicrm absolute path
function set_default_localisation_settings() {
  LOC_FILE="en_US"
  if wget -q "https://download.civicrm.org/civicrm-l10n-core/mo/en_GB/civicrm.mo" > /dev/null; then
    mkdir -p $1/l10n/en_GB/LC_MESSAGES/
    mv civicrm.mo $1/l10n/en_GB/LC_MESSAGES/civicrm.mo
    LOC_FILE="en_GB"
  fi

  UKID=$(drush cvapi Country.getsingle return="id" iso_code="GB" | grep -oh '[0-9]*')

  drush cvapi Setting.create sequential=1 defaultCurrency="GBP" \
  dateformatDatetime="%d/%m/%Y %l:%M %P" dateformatFull="%d/%m/%Y" \
  dateformatFinancialBatch="%d/%m/%Y" dateInputFormat="dd/mm/yy" \
  lcMessages=${LOC_FILE} defaultContactCountry=${UKID}

  drush cvapi OptionValue.create sequential=1 option_group_id="currencies_enabled" \
  label="GBP (£)" value="GBP" is_default=1 is_active=1
}

##################################
## Main

if [ "$1" == "--with-sample-data" ]; then
  WITHSAMPLE=1
  shift
else
  WITHSAMPLE=
fi

set -ex
drush "$@" cvapi extension.install keys=$ENTITY_EXTS,$APP_EXTS
set +ex

if [ -n "$WITHSAMPLE" ]; then
  set -ex
  drush "$@" cvapi extension.install keys=org.civicrm.hrsampledata
  set +ex
fi

set_default_localisation_settings $1
