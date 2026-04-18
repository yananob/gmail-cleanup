#!/bin/bash
set -eu

# 静的解析を実行
echo "Running PHPStan..."
./vendor/bin/phpstan analyze -c phpstan.neon

if [ -f "./tests/secrets.sh" ]; then
    echo "Exporting secrets..."
    source ./tests/secrets.sh
    if [ -d "./_cf-common/test" ]; then
        source ./_cf-common/test/export_secrets.sh ${SECRETS[*]}
    fi
fi

# PHPUnitを実行
echo "Running PHPUnit..."
./vendor/bin/phpunit tests/

if [ -f "./tests/secrets.sh" ] && [ -d "./_cf-common/test" ]; then
    source ./_cf-common/test/unset_secrets.sh ${SECRETS[*]}
fi
