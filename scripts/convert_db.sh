#!/usr/bin/env bash
#
# Converts the MS Access database to MySQL.
#
# (c) Pere Orga Esteve <pere@orga.cat>
#
# This source file is subject to the AGPL license that is bundled with this
# source code in the file LICENSE.

set -e

DATABASE_FILE="${1:-database.accdb}"

cd "$(dirname "$0")"

##############################################################################
# Shows the help of this command.
# Arguments:
#   None
##############################################################################
usage() {
    echo "Usage: $(basename "$0") [--help] [DATABASE]"
    echo "Converts the PCCD MS Access database to MySQL."
    echo ""
    echo "  --help"
    echo "    Show this help and exit"
    echo "  DATABASE          The MS Access database filename (default: database.accdb)"
}

if [[ $* == *"--help"* ]]; then
    usage
    exit 0
fi

if [[ -n $2 ]]; then
    usage
    exit 1
fi

# Export schema and language table to ensure breaking changes are monitored.
mdb-schema ../"${DATABASE_FILE}" mysql > ../tmp/msaccess_schema.sql
mdb-export --insert=mysql --batch-size=1 ../"${DATABASE_FILE}" 00_EQUIVALENTS > ../tmp/equivalents_dump.sql

cat /dev/null > ../install/db/db.sql

# For some reason, mdb-export dumps some dates as 1900-01-00 00:00:00. Setting this SQL mode was necessary on MySQL.
echo "SET sql_mode = ALLOW_INVALID_DATES;" >> ../install/db/db.sql

# Drop existing tables.
echo "DROP TABLE IF EXISTS 00_PAREMIOTIPUS;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_FONTS;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_IMATGES;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_EDITORIA;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_OBRESVPR;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS 00_EQUIVALENTS;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS common_paremiotipus;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS commonvoice;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS paremiotipus_display;" >> ../install/db/db.sql
echo "DROP TABLE IF EXISTS pccd_is_installed;" >> ../install/db/db.sql

# Export schema.
mdb-schema --no-indexes --no-relations -T 00_PAREMIOTIPUS ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_FONTS ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_IMATGES ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_EDITORIA ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_OBRESVPR ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql
mdb-schema --no-indexes --no-relations -T 00_EQUIVALENTS ../"${DATABASE_FILE}" mysql >> ../install/db/db.sql

# Add normalized (lowercase, without accents) search-specific columns. This may be unnecessary now, but we should
# probably keep it unless we switch to full-text search only.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN PAREMIOTIPUS_LC_WA varchar (255);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN MODISME_LC_WA varchar (255);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN SINONIM_LC_WA varchar (255);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN EQUIVALENT_LC_WA varchar (255);" >> ../install/db/db.sql

# Add additional columns.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD COLUMN ACCEPCIO varchar (2);" >> ../install/db/db.sql

# Dump data.
mdb-export -I mysql ../"${DATABASE_FILE}" "00_PAREMIOTIPUS" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_FONTS" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_IMATGES" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_EDITORIA" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_OBRESVPR" >> ../install/db/db.sql
mdb-export -I mysql ../"${DATABASE_FILE}" "00_EQUIVALENTS" >> ../install/db/db.sql

# Add image width and height columns for images.
echo "ALTER TABLE 00_FONTS ADD COLUMN WIDTH INT NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_FONTS ADD COLUMN HEIGHT INT NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_IMATGES ADD COLUMN WIDTH INT NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_IMATGES ADD COLUMN HEIGHT INT NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_OBRESVPR ADD COLUMN WIDTH INT NOT NULL DEFAULT 0;" >> ../install/db/db.sql
echo "ALTER TABLE 00_OBRESVPR ADD COLUMN HEIGHT INT NOT NULL DEFAULT 0;" >> ../install/db/db.sql

# Create indexes.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD PRIMARY KEY (Id);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (PAREMIOTIPUS);" >> ../install/db/db.sql
# MODISME index is mostly useful in the "repetits" report.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (MODISME);" >> ../install/db/db.sql
# ID_FONT index is useful for counting references in the "obra" page, and also in some reports.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD INDEX (ID_FONT);" >> ../install/db/db.sql
# Full-text indexes are required for the multiple search combinations.
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, MODISME_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, SINONIM_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, EQUIVALENT_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, MODISME_LC_WA, SINONIM_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, MODISME_LC_WA, EQUIVALENT_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, SINONIM_LC_WA, EQUIVALENT_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_PAREMIOTIPUS ADD FULLTEXT (PAREMIOTIPUS_LC_WA, MODISME_LC_WA, SINONIM_LC_WA, EQUIVALENT_LC_WA);" >> ../install/db/db.sql
echo "ALTER TABLE 00_FONTS ADD INDEX (Identificador);" >> ../install/db/db.sql
echo "ALTER TABLE 00_IMATGES ADD INDEX (PAREMIOTIPUS);" >> ../install/db/db.sql
echo "ALTER TABLE 00_EDITORIA ADD INDEX (CODI);" >> ../install/db/db.sql

# Create additional custom tables.
echo "CREATE TABLE common_paremiotipus(Paremiotipus varchar (255), Compt int);" >> ../install/db/db.sql
echo "ALTER TABLE common_paremiotipus ADD INDEX (Compt);" >> ../install/db/db.sql
echo "CREATE TABLE commonvoice(paremiotipus varchar (255), file varchar (200), PRIMARY KEY (paremiotipus, file));" >> ../install/db/db.sql
echo "CREATE TABLE paremiotipus_display(Paremiotipus varchar (255) PRIMARY KEY, Display varchar (255));" >> ../install/db/db.sql

# Normalize UTF-8 combined characters.
if [[ -x "$(command -v uconv)" ]]; then
    uconv -x nfkc ../install/db/db.sql > ../install/db/db_temp.sql
elif [[ -x "$(command -v brew)" ]]; then
    if brew info icu4c &> /dev/null; then
        $(brew list icu4c | grep -F -m1 "bin/uconv") -x nfkc ../install/db/db.sql > ../install/db/db_temp.sql
    else
        echo "Error: uconv command not found."
        exit 1
    fi
else
    echo "Error: uconv and brew commands not found."
    exit 1
fi
# See https://github.com/mdbtools/mdbtools/issues/391.
sed 's/varchar (255)/varchar (300)/g' ../install/db/db_temp.sql > ../install/db/db.sql
rm ../install/db/db_temp.sql
