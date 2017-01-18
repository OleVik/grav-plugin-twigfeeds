@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../fguillot/picofeed/picofeed
php "%BIN_TARGET%" %*
