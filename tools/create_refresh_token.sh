#!/bin/bash
set -eu

source ./tests/secrets.sh
if [ -d "./_myapps-common/test" ] && [ ${#SECRETS[@]} -gt 0 ]; then
    source ./_myapps-common/test/export_secrets.sh "${SECRETS[@]}"
fi

php tools/create_refresh_token.php

source ./_myapps-common/test/unset_secrets.sh "${SECRETS[@]}"
