#!/usr/bin/env bash
cd ../
php vendor/bin/doctrine orm:clear-cache:metadata &&
php vendor/bin/doctrine orm:schema-tool:drop --force &&
php vendor/bin/doctrine orm:schema-tool:create