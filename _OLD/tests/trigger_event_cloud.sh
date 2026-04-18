#!/bin/bash
set -eu

gcloud pubsub topics publish gmail-cleanup --message="test!"
