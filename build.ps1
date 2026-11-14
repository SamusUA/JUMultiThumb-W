<#
.SYNOPSIS
    Builds JUMultiThumb package for Joomla 5 and Joomla 6 (PHP 8.2 - 8.4+).
#>

$root = $PSScriptRoot
$distDir = Join-Path $root "dist"
$pkgDir = Join-Path $distDir "package_root"
$packagesSubDir = Join-Path $pkgDir "packages"

if (Test-Path $distDir) { Remove-Item -Path $distDir -Recurse -Force }
New-Item -ItemType Directory -Path $packagesSubDir -Force | Out-Null

$plugins = @(
    @{ Name = "plg_jumultithumb"; Path = "packages/plg_jumultithumb" },
    @{ Name = "plg_jumultithumb_gallery"; Path = "packages/plg_jumultithumb_gallery" },
    @{ Name = "plg_jumultithumb_contentform"; Path = "packages/plg_jumultithumb_contentform" },
    @{ Name = "plg_jumultithumb_editorbutton"; Path = "packages/plg_jumultithumb_editorbutton" }
)

foreach ($plg in $plugins) {
    $src = Join-Path $root $plg.Path
    $zipDest = Join-Path $packagesSubDir "$($plg.Name).zip"
    $standaloneDest = Join-Path $distDir "$($plg.Name).zip"
    
    if (Test-Path $src) {
        Compress-Archive -Path "$src\*" -DestinationPath $zipDest -Force
        Copy-Item -Path $zipDest -Destination $standaloneDest -Force
        Write-Output "Built: $($plg.Name).zip"
    }
}

Copy-Item -Path (Join-Path $root "pkg_jumultithumb.xml") -Destination (Join-Path $pkgDir "pkg_jumultithumb.xml") -Force
Copy-Item -Path (Join-Path $root "script.php") -Destination (Join-Path $pkgDir "script.php") -Force
if (Test-Path (Join-Path $root "language")) {
    Copy-Item -Path (Join-Path $root "language") -Destination (Join-Path $pkgDir "language") -Recurse -Force
}

$finalPkgZip = Join-Path $distDir "pkg_jumultithumb_v7.0.0_joomla5_php84.zip"
Compress-Archive -Path "$pkgDir\*" -DestinationPath $finalPkgZip -Force
Write-Output "Successfully built package: $finalPkgZip"