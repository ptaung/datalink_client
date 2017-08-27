@ECHO OFF

echo Run sending Data DataLink-WebService as an Service
set YII_PATH=%~dp0

%YII_PATH%web\yiiwm grant

timeout 20 >nul
exit




