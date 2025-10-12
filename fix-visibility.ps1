# =================================================
# Script PowerShell: Mettre tous les membres PUBLIC
# =================================================

Write-Host "`n" -NoNewline
Write-Host "=================================" -ForegroundColor Cyan
Write-Host "  FIX VISIBILITY - SQL DIRECT" -ForegroundColor Yellow
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# MySQL credentials (ajustez si nécessaire)
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$dbName = "wordpress"  # Ajustez le nom de votre base
$dbUser = "root"       # Ajustez le nom d'utilisateur
$dbPass = ""           # Ajustez le mot de passe si nécessaire

# SQL Query
$sqlQuery = "UPDATE wp_jgk_members SET is_public = 1; SELECT COUNT(*) as 'Membres PUBLIC' FROM wp_jgk_members WHERE is_public = 1;"

Write-Host "Execution de la commande SQL..." -ForegroundColor Yellow
Write-Host ""

# Execute MySQL command
if (Test-Path $mysqlPath) {
    if ($dbPass -eq "") {
        & $mysqlPath -u $dbUser $dbName -e $sqlQuery
    } else {
        & $mysqlPath -u $dbUser -p$dbPass $dbName -e $sqlQuery
    }
    
    Write-Host ""
    Write-Host "=================================" -ForegroundColor Green
    Write-Host "  MISE A JOUR TERMINEE!" -ForegroundColor Yellow
    Write-Host "=================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Tous les membres sont maintenant PUBLIC." -ForegroundColor White
    Write-Host "Testez maintenant 'View Details' dans la liste des membres!" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "ERREUR: MySQL non trouve a $mysqlPath" -ForegroundColor Red
    Write-Host ""
    Write-Host "SOLUTION ALTERNATIVE:" -ForegroundColor Yellow
    Write-Host "Ouvrez cette URL dans votre navigateur:" -ForegroundColor White
    Write-Host "http://localhost/wordpress/wp-content/plugins/juniorgolfkenya/fix-visibility-now.php" -ForegroundColor Cyan
    Write-Host ""
}
