#!/bin/bash
set -eu

# Local HTTP cleanup trigger test
# Assuming the function is running on localhost:8080 (see tests/listen_local_http.sh)
curl -X POST localhost:8080/run-cleanup \
    -H "Authorization: Bearer local-test-token" \
    -v
