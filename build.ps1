<#
.SYNOPSIS
    Builds JUMultiThumb package for Joomla 5 and Joomla 6 (PHP 8.2 - 8.4+).
    Ensures 100% standard forward-slash (/) paths in all ZIP entries for Linux/PHP compatibility.
#>

$ProgressPreference = 'SilentlyContinue'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

function New-StandardZip($sourceDir, $zipPath) {
    if (Test-Path $zipPath) { Remove-Item -Path $zipPath -Force }
    $mode = [System.IO.Compression.ZipArchiveMode]::Create
    $zipArchive = [System.IO.Compression.ZipFile]::Open($zipPath, $mode)
    $files = Get-ChildItem -Path $sourceDir -Recurse -File
    foreach ($f in $files) {
        $rel = $f.FullName.Substring($sourceDir.Length).TrimStart('\', '/').Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $f.FullName, $rel) | Out-Null
    }
    $zipArchive.Dispose()
}

$root = $PSScriptRoot
$distDir = Join-Path $root "dist"
$pkgDir = Join-Path $distDir "package_root"
$packagesSubDir = Join-Path $pkgDir "packages"

if (Test-Path $pkgDir) { Remove-Item -Path $pkgDir -Recurse -Force }
New-Item -ItemType Directory -Path $packagesSubDir -Force | Out-Null

$plugins = @(
    @{ Name = "lib_juimage"; Path = "packages\lib_juimage" },
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
        New-StandardZip $src $zipDest
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

$finalPkgZip = Join-Path $distDir "pkg_jumultithumb_v6.0.5-alpha.zip"
New-StandardZip $pkgDir $finalPkgZip

# Also copy final package to artifact directory
$artifactDir = 'C:\Users\Svyatoslav\.gemini\antigravity\brain\c0cc74cb-a6bf-4108-a7e7-5d06e89d0e19'
$artifactDest = Join-Path $artifactDir "pkg_jumultithumb_v6.0.5-alpha.zip"
Copy-Item -Path $finalPkgZip -Destination $artifactDest -Force

Write-Output "Created: $finalPkgZip ($((Get-Item $finalPkgZip).Length) bytes)"
Write-Output "Copied to artifact: $artifactDest ($((Get-Item $artifactDest).Length) bytes)"
