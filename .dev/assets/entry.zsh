#!/usr/bin/env zsh
# Nova Discord - Test container entry script
# This file is licensed under the MIT License; See LICENSE for full text.

set -eu
cd "$APP_HOME/mediawiki"

trap 'sleep 1' EXIT  # let stdout settle first

printf '%s\n' 'Initializing test environment...'

PASS="$(LC_ALL=C tr -dc 'a-zA-Z0-9' < /dev/urandom | head -c16)"
printf 'Using NovaAdmin:%s for wiki admin if you somehow need it\n' "$PASS"

php maintenance/run.php install \
    --dbname='nova_wiki' \
    --dbserver='database' \
    --installdbuser='nova' \
    --installdbpass="$NOVA_DB_PASSWORD" \
    --dbuser='nova' \
    --dbpass="$NOVA_DB_PASSWORD" \
    --server="https://novadiscord.local/" \
    --scriptpath='' \
    --lang='en' \
    --pass="$PASS" "Nova Discord" "NovaAdmin"

mv -fv LocalSettings.new LocalSettings.php

printf '\n%s\n\n' '=== Starting Tests ==='

composer phpunit:entrypoint -- extensions/NovaDiscord;
