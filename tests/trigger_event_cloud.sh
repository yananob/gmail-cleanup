#!/bin/bash
set -eu

gcloud pubsub topics publish XXX-event --message='{"command": "COMMAND"}'
