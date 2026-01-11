#!/bin/bash
# Start Argos Translate Service in PRODUCTION mode (Linux/Mac)

echo "Starting Argos Translate Service in PRODUCTION mode..."
echo "Using Waitress WSGI server..."
cd "$(dirname "$0")"
export USE_PRODUCTION_SERVER=true
python3 argos-translate-service.py
