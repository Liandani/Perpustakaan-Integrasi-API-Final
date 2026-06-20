$ErrorActionPreference = "Stop"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host " Memulai Pengujian API Secara Otomatis " -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# 1. Test User API
Write-Host "1. Mengetes User API melalui Gateway (Port 8000)..." -ForegroundColor Yellow
try {
    $userResponse = Invoke-RestMethod -Uri "http://localhost:8000/users/1" -Method Get
    Write-Host "   [OK] Respons User API:" -ForegroundColor Green
    Write-Host ($userResponse | ConvertTo-Json -Depth 3)
} catch {
    Write-Host "   [GAGAL] User API tidak bisa dijangkau." -ForegroundColor Red
}
Write-Host ""

# 2. Test Book API
Write-Host "2. Mengetes Book API melalui Gateway (Port 8000)..." -ForegroundColor Yellow
try {
    $bookResponse = Invoke-RestMethod -Uri "http://localhost:8000/books/3" -Method Get
    Write-Host "   [OK] Respons Book API:" -ForegroundColor Green
    Write-Host ($bookResponse | ConvertTo-Json -Depth 3)
} catch {
    Write-Host "   [GAGAL] Book API tidak bisa dijangkau." -ForegroundColor Red
}
Write-Host ""

# 3. Test Loan API (Send Message ke RabbitMQ)
Write-Host "3. Mengetes Loan API melalui Gateway (Port 8000)..." -ForegroundColor Yellow
Write-Host "   -> API ini akan memanggil User API, Book API secara internal, lalu mengirim pesan ke RabbitMQ."
try {
    $loanResponse = Invoke-RestMethod -Uri "http://localhost:8000/send-message" -Method Get
    Write-Host "   [OK] Pesan Berhasil Terkirim ke RabbitMQ!" -ForegroundColor Green
    Write-Host ($loanResponse | ConvertTo-Json -Depth 5)
} catch {
    Write-Host "   [GAGAL] Loan API error. Cek apakah RabbitMQ/API lain berjalan dengan benar." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}
Write-Host ""

# 4. Test Fine API (Consume Message dari RabbitMQ)
Write-Host "4. Mengetes Fine API melalui Gateway (Port 8000)..." -ForegroundColor Yellow
try {
    $fineResponse = Invoke-RestMethod -Uri "http://localhost:8000/consume-message" -Method Get
    if ($null -ne $fineResponse.data) {
        Write-Host "   [OK] Pesan Berhasil Ditarik (Consumed) dari RabbitMQ!" -ForegroundColor Green
        Write-Host ($fineResponse | ConvertTo-Json -Depth 5)
    } else {
        Write-Host "   [PERINGATAN] Endpoint merespons, namun antrean (queue) RabbitMQ sepertinya kosong." -ForegroundColor Yellow
        Write-Host ($fineResponse | ConvertTo-Json -Depth 5)
    }
} catch {
    Write-Host "   [GAGAL] Fine API error saat consume message." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}
Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "        Pengujian Selesai            " -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
