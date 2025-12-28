@echo off
REM Start LibreTranslate Local Server
REM This runs the translation API on http://localhost:5000

echo ========================================
echo   Starting LibreTranslate Server
echo ========================================
echo.

cd LibreTranslate-main

echo Checking Python installation...
python --version
if errorlevel 1 (
    echo ERROR: Python is not installed!
    echo Please install Python 3.8 or higher
    pause
    exit /b 1
)

echo.
echo Starting LibreTranslate on http://localhost:5000
echo Press Ctrl+C to stop the server
echo.

REM Start LibreTranslate
python main.py --host 0.0.0.0 --port 5000

pause

