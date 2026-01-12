@echo off
REM Start Argos Translate Service in PRODUCTION mode (Windows)
echo Starting Argos Translate Service in PRODUCTION mode...
echo Using Waitress WSGI server...
cd /d "%~dp0"
set USE_PRODUCTION_SERVER=true
python argos-translate-service.py
pause
