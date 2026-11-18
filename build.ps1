<#
.SYNOPSIS
    Builds JUMultiThumb package for Joomla 5 and Joomla 6 (PHP 8.2 - 8.4+).
#>

$ProgressPreference = 'SilentlyContinue'
Add-Type -AssemblyName System.IO.Compression.FileSystem

$root = $PSScriptRoot
$distDir = Join-Path $root "dist"
$pkgDir = Join-Path $distDir "package_root"
$packagesSubDir = Join-Path $pkgDir "packages"

if (Test-Path $distDir) { Remove-Item -Path $distDir -Recurse -Force }
New-Item -ItemType Directory -Path $packagesSubDir -Force | Out-Null

$plugins = @(
    @{ Name = "plg_jumultithumb"; Path = "packages\plg_jumultithumb" },
    @{ Name = "plg_jumultithumb_gallery"; Path = "packages\plg_jumultithumb_gallery" },
    @{ Name = "plg_jumultithumb_contentform"; Path = "packages\plg_jumultithumb_contentform" },
    @{ Name = "plg_jumultithumb_editorbutton"; Path = "packages\plg_jumultithumb_editorbutton" }
)

foreach ($plg in $plugins) {
    $src = Join-Path $root $plg.Path
    $zipDest = Join-Path $packagesSubDir "$($plg.Name).zip"
    $standaloneDest = Join-Path $distDir "$($plg.Name).zip"
    
    if (Test-Path $src) {
        if (Test-Path $zipDest) { Remove-Item -Path $zipDest -Force }
        [System.IO.Compression.ZipFile]::CreateFromDirectory($src, $zipDest)
        Copy-Item -Path $zipDest -Destination $standaloneDest -Force
        Write-Output "Built: $($plg.Name).zip ($((Get-Item $zipDest).Length) bytes)"
    } else {
        Write-Output "[ERROR] Missing source: $src"
    }
}

Copy-Item -Path (Join-Path $root "pkg_jumultithumb.xml") -Destination (Join-Path $pkgDir "pkg_jumultithumb.xml") -Force
Copy-Item -Path (Join-Path $root "script.php") -Destination (Join-Path $pkgDir "script.php") -Force
if (Test-Path (Join-Path $root "language")) {
    Copy-Item -Path (Join-Path $root "language") -Destination (Join-Path $pkgDir "language") -Recurse -Force
}

$finalPkgZip = Join-Path $distDir "pkg_jumultithumb_v6.0.0-alpha.zip"
if (Test-Path $finalPkgZip) { Remove-Item -Path $finalPkgZip -Force }
[System.IO.Compression.ZipFile]::CreateFromDirectory($pkgDir, $finalPkgZip)

# Also copy final package to artifact directory
$artifactDir = 'C:\Users\Svyatoslav\.gemini\antigravity\brain\c0cc74cb-a6bf-4108-a7e7-5d06e89d0e19'
$artifactDest = Join-Path $artifactDir "pkg_jumultithumb_v6.0.0-alpha.zip"
Copy-Item -Path $finalPkgZip -Destination $artifactDest -Force

Write-Output "Created: $finalPkgZip ($((Get-Item $finalPkgZip).Length) bytes)"
Write-Output "Copied to artifact: $artifactDest ($((Get-Item $artifactDest).Length) bytes)"
