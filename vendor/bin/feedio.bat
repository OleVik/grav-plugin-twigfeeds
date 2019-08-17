@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../debril/feed-io/bin/feedio
php "%BIN_TARGET%" %*
