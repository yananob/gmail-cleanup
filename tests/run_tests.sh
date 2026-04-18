#!/bin/bash
set -eu

# 静的解析を実行
./vendor/bin/phpstan analyze -c phpstan.neon

# PHPUnitを実行
./vendor/bin/phpunit --colors=auto tests/
