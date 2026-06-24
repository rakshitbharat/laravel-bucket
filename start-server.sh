#!/bin/sh

echo "Setting up SQLite database..."
touch /app/database.sqlite

echo "Running migrations..."
vendor/bin/testbench migrate

echo "Starting LaraBucket server on http://0.0.0.0:8000 ..."
vendor/bin/testbench serve --host 0.0.0.0 --port 8000
