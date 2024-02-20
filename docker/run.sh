#!/bin/bash

# without this command, apache2 would not start if var/log folder is not existing.
# note: var/log gets removed when switching environments using env-prod for instance.
mkdir -m 0777 -p ./var
mkdir -m 0777 -p ./var/cache
mkdir -m 0777 -p ./var/log

apache2-foreground

tail -f /dev/null
