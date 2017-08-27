

@ECHO OFF

set YII_PATH=%~dp0

schtasks /create /tn "DataLink-Connect" /tr "%YII_PATH%web\yiiwm.bat client/sync/online" /f /sc minute /mo 2 /NP
echo "DataLink-Connect Install script success BY SILASOFTTHAILAND"
timeout 5 >nul
exit



