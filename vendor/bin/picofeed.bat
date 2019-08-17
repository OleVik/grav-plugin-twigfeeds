@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../nicolus/picofeed/picofeed
php "%BIN_TARGET%" %*
