#!/bin/bash
# Start Argos Translate Service for Linux/Mac

echo "Starting Argos Translate Service..."
cd "$(dirname "$0")"
python3 argos-translate-service.py
