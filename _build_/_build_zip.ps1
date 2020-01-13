# script eedomus-milight6-plugin

$zip = ".\release\milight6.zip"  


$scriptpath = $MyInvocation.MyCommand.Path
$dir = Split-Path $scriptpath
set-location $dir

if (-Not (Test-Path ".\release")) {  New-Item -Path ".\" -Name "release" -ItemType "directory"}
if (-Not (Test-Path ".\tmp"))     {  New-Item -Path ".\" -Name "tmp" -ItemType "directory"}
if (-Not (Test-Path ".\tmp\img"))     {  New-Item -Path ".\tmp" -Name "img" -ItemType "directory"}

Remove-Item ".\tmp\*.*"
Remove-Item ".\tmp\img\*.*"

if (Test-Path $zip) {  Remove-Item $zip }


Copy-Item -Path "..\img\*.png" -Destination ".\tmp\img\" -Force
Copy-Item -Path "..\php\*.php" -Destination ".\tmp" -Force
Copy-Item "..\eedomus_plugin.json" -Destination ".\tmp" -Force
Copy-Item "..\readme_fr.md" -Destination ".\tmp" -Force

$json = ".\tmp\eedomus_plugin.json"

# Garder icones eedomus 
# (Get-Content $json) | Foreach-Object {$_ -replace '"{1}icon2b"{1}.{0,}"{1}.{1,}"{1}.{0,},'   , ''} | Set-Content $json

# Garder mes icones    
(Get-Content $json) | Foreach-Object {$_ -replace ':{1}.{0,}"{1}.{1,}"icon2b"'  , ''} | Set-Content $json


$compress = @{
Path= "tmp\*.*", "tmp\img"
CompressionLevel = "Optimal"
DestinationPath = ".\$zip"
}
Compress-Archive @compress

