#!/bin/bash
set -eu

# Cloud HTTP cleanup trigger test
# Usage: ./tests/trigger_cleanup_cloud.sh [FUNCTION_URL]

if [ $# -lt 1 ]; then
    echo "Usage: $0 [FUNCTION_URL]"
    exit 1
fi

URL=$1

curl -X POST "${URL}/run-cleanup" \
    -H "Authorization: Bearer $(gcloud auth print-identity-token)" \
    -v
