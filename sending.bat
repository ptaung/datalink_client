@ECHO OFF

echo Run sending Data WMC-WebService as an Service
set YII_PATH=%~dp0

%YII_PATH%web\yiiwm client/sync/start

timeout 20 >nul
exit




