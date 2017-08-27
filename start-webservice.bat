@ECHO OFF

echo Starting... WMC-WebService
set YII_PATH=%~dp0
%YII_PATH%RunHiddenConsole.exe %YII_PATH%php\php.exe -S 0.0.0.0:8080 -t %YII_PATH%web\web