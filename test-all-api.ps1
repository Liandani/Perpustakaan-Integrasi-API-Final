$ErrorActionPreference = "Continue"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host " PENGUJIAN PENUH SEMUA MICROSERVICES " -ForegroundColor Cyan
Write-Host " MELALUI API GATEWAY (PORT 8000)     " -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

$gateway = "http://localhost:8000"

# ---------------------------------------------------------
# 1. TEST USER API
# ---------------------------------------------------------
Write-Host "1. MENGETES USER API" -ForegroundColor Yellow
$userPayload = @{
    name = "Budi Santoso"
    email = "budi$(Get-Random)@example.com"
    password = "password123"
} | ConvertTo-Json

$userCreated = Invoke-RestMethod -Uri "$gateway/users" -Method Post -Body $userPayload -ContentType "application/json"
$userId = $userCreated.user.id
Write-Host "   [+] Berhasil membuat User dengan ID: $userId" -ForegroundColor Green

$userRead = Invoke-RestMethod -Uri "$gateway/users/$userId" -Method Get
Write-Host "   [+] Berhasil membaca User API:" -ForegroundColor Green
Write-Host ($userRead | ConvertTo-Json -Depth 3)
Write-Host ""

# ---------------------------------------------------------
# 2. TEST BOOK API
# ---------------------------------------------------------
Write-Host "2. MENGETES BOOK API" -ForegroundColor Yellow
$bookPayload = @{
    title = "Seni Berpikir Positif"
    available = $true
} | ConvertTo-Json

$bookCreated = Invoke-RestMethod -Uri "$gateway/books" -Method Post -Body $bookPayload -ContentType "application/json"
$bookId = $bookCreated.book.id
Write-Host "   [+] Berhasil membuat Buku dengan ID: $bookId" -ForegroundColor Green

$bookRead = Invoke-RestMethod -Uri "$gateway/books/$bookId" -Method Get
Write-Host "   [+] Berhasil membaca Book API:" -ForegroundColor Green
Write-Host ($bookRead | ConvertTo-Json -Depth 3)
Write-Host ""

# ---------------------------------------------------------
# 3. TEST LOAN API
# ---------------------------------------------------------
Write-Host "3. MENGETES LOAN API (PEMINJAMAN BUKU)" -ForegroundColor Yellow
$loanPayload = @{
    user_id = $userId
    book_id = $bookId
    loan_date = (Get-Date).ToString("yyyy-MM-dd")
    due_date = (Get-Date).AddDays(7).ToString("yyyy-MM-dd")
} | ConvertTo-Json

$loanCreated = Invoke-RestMethod -Uri "$gateway/loans" -Method Post -Body $loanPayload -ContentType "application/json"
$loanId = $loanCreated.loan.id
Write-Host "   [+] Berhasil melakukan peminjaman! Loan ID: $loanId" -ForegroundColor Green
Write-Host ($loanCreated | ConvertTo-Json -Depth 3)
Write-Host ""

# ---------------------------------------------------------
# 4. TEST LOAN RETURN & FINE API
# ---------------------------------------------------------
Write-Host "4. MENGETES PENGEMBALIAN BUKU & DENDA (FINE API)" -ForegroundColor Yellow
$returnPayload = @{
    loan_id = $loanId
    return_date = (Get-Date).AddDays(10).ToString("yyyy-MM-dd") # Terlambat 3 hari untuk memicu denda
} | ConvertTo-Json

$returnProcess = Invoke-RestMethod -Uri "$gateway/loans/return" -Method Post -Body $returnPayload -ContentType "application/json"
Write-Host "   [+] Buku berhasil dikembalikan. Status dari Loan API:" -ForegroundColor Green
Write-Host ($returnProcess | ConvertTo-Json -Depth 3)

Write-Host "   [-] Mengecek denda ke Fine API..." -ForegroundColor Yellow
$finePayload = @{ loan_id = $loanId } | ConvertTo-Json
$fineCheck = Invoke-RestMethod -Uri "$gateway/fines/check" -Method Post -Body $finePayload -ContentType "application/json"
Write-Host "   [+] Respons Fine API:" -ForegroundColor Green
Write-Host ($fineCheck | ConvertTo-Json -Depth 3)
Write-Host ""

# ---------------------------------------------------------
# 5. TEST RABBITMQ END-TO-END FLOW
# ---------------------------------------------------------
Write-Host "5. MENGETES RABBITMQ MESSAGE BROKER" -ForegroundColor Yellow
try {
    $sendMsg = Invoke-RestMethod -Uri "$gateway/send-message" -Method Get
    Write-Host "   [+] Loan API berhasil mengirim pesan ke RabbitMQ:" -ForegroundColor Green
    Write-Host ($sendMsg | ConvertTo-Json -Depth 3)
    
    $consumeMsg = Invoke-RestMethod -Uri "$gateway/consume-message" -Method Get
    if ($null -ne $consumeMsg.data) {
        Write-Host "   [+] Fine API berhasil menarik pesan dari RabbitMQ:" -ForegroundColor Green
        Write-Host ($consumeMsg | ConvertTo-Json -Depth 3)
    } else {
        Write-Host "   [!] Pesan berhasil ditarik namun kosong (Mungkin antrean terlanjur bersih)." -ForegroundColor Yellow
    }
} catch {
    Write-Host "   [X] Gagal melakukan test RabbitMQ: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# ---------------------------------------------------------
# 6. CLEANUP (DELETE DATA)
# ---------------------------------------------------------
Write-Host "6. MEMBERSIHKAN DATA (DELETE API)" -ForegroundColor Yellow
Invoke-RestMethod -Uri "$gateway/loans/$loanId" -Method Delete | Out-Null
Write-Host "   [+] Data Loan dihapus." -ForegroundColor Green
Invoke-RestMethod -Uri "$gateway/books/$bookId" -Method Delete | Out-Null
Write-Host "   [+] Data Book dihapus." -ForegroundColor Green
Invoke-RestMethod -Uri "$gateway/users/$userId" -Method Delete | Out-Null
Write-Host "   [+] Data User dihapus." -ForegroundColor Green

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "     SEMUA PENGUJIAN SELESAI!!       " -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
