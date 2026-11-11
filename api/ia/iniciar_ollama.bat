@echo off
REM Enciende Ollama si no está corriendo. Ponlo en shell:startup o en el Programador de tareas.
tasklist /FI "IMAGENAME eq ollama.exe" | find /I "ollama.exe" >nul
if errorlevel 1 (
  start "" /B ollama serve
)
REM Opcional: precargar el modelo cuando Apache ya esté arriba
REM timeout /t 15 >nul
REM curl http://localhost/api/ollama.php?action=warm
