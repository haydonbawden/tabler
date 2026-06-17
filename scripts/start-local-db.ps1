$mariaDb = 'C:\Program Files\MariaDB 12.3\bin\mariadbd.exe'
$dataDir = 'C:\Program Files\MariaDB 12.3\data'

if (Get-Process mariadbd -ErrorAction SilentlyContinue) {
    Write-Host 'MariaDB is already running.'
    exit 0
}

if (!(Test-Path -LiteralPath $mariaDb)) {
    Write-Error 'MariaDB is not installed at the expected path.'
    exit 1
}

Start-Process -FilePath $mariaDb -ArgumentList @('--datadir=' + $dataDir, '--port=3306', '--console') -WindowStyle Hidden
Start-Sleep -Seconds 4

if (Get-Process mariadbd -ErrorAction SilentlyContinue) {
    Write-Host 'MariaDB started on localhost:3306.'
    exit 0
}

Write-Error 'MariaDB did not start.'
exit 1
