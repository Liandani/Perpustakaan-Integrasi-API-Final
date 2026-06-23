<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\Book;
use App\Models\LoanHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LoanController extends Controller
{
    // GET ALL LOANS
    public function index()
    {
        return response()->json([
            'message' => 'Daftar semua peminjaman berhasil diambil',
            'data' => Loan::all()
        ]);
    }

    // CREATE LOAN
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'book_id' => 'required|integer',
            'loan_date' => 'nullable|date',
            'due_date' => 'nullable|date'
        ]);

        // Verify User existence
        $userResponse = Http::get(env('USER_API_URL', 'http://user-api:8000') . '/users/' . $request->user_id);
        if ($userResponse->status() === 404) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Verify Book existence
        $bookResponse = Http::get(env('BOOK_API_URL', 'http://book-api:8000') . '/books/' . $request->book_id);
        if ($bookResponse->status() === 404) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $activeLoan = Loan::where('book_id', $request->book_id)
            ->where('status', 'borrowed')
            ->first();

        if ($activeLoan) {
            return response()->json([
                'message' => 'Book sedang dipinjam dan tidak tersedia'
            ], 400);
        }

        $loanDate = $request->loan_date
            ? Carbon::parse($request->loan_date)
            : now();

        $dueDate = $request->due_date
            ? Carbon::parse($request->due_date)
            : $loanDate->copy()->addDays(7);

        $loan = Loan::create([
            'user_id' => $request->user_id,
            'book_id' => $request->book_id,
            'loan_date' => $loanDate,
            'due_date' => $dueDate,
            'status' => 'borrowed'
        ]);

        return response()->json([
            'message' => 'Loan berhasil dibuat',
            'data' => $loan
        ]);
    }

    // GET LOAN HISTORY
    public function history()
    {
        $histories = LoanHistory::all();

        return response()->json([
            'message' => 'Daftar riwayat peminjaman berhasil diambil',
            'data' => $histories
        ]);
    }

    // PENGEMBALIAN BUKU
    public function returnBook(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'return_date' => 'nullable|date'
        ]);

        $loan = Loan::find($request->loan_id);

        if (!$loan) {
            return response()->json([
                'message' => 'Data peminjaman tidak ditemukan'
            ], 404);
        }

        if ($loan->status === 'returned') {
            return response()->json([
                'message' => 'Buku sudah dikembalikan sebelumnya'
            ], 400);
        }

        $returnDate = $request->return_date
            ? Carbon::parse($request->return_date)
            : now();

        $dueDate = Carbon::parse($loan->due_date);

        $daysLate = 0;
        $fine = 0;

        if ($returnDate->gt($dueDate)) {
            $daysLate = $dueDate->diffInDays($returnDate);
            $fine = $daysLate * 2000;
        }

        $loan->update([
            'status' => 'returned',
            'return_date' => $returnDate
        ]);

        return response()->json([
            'message' => 'Buku berhasil dikembalikan',
            'detail_denda' => [
                'hari_terlambat' => $daysLate,
                'total_denda' => 'Rp ' . number_format($fine, 0, ',', '.')
            ],
            'data' => $loan
        ]);
    }

    // GET DETAIL LOAN BY ID
    public function show($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json([
                'message' => 'Peminjaman tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail peminjaman berhasil diambil',
            'data' => $loan
        ]);
    }

    // GET ACTIVE LOAN BY BOOK ID (For Book Service Check)
    public function getActiveLoanByBook($bookId)
    {
        $activeLoan = Loan::where('book_id', $bookId)
            ->where('status', 'borrowed')
            ->first();

        if ($activeLoan) {
            return response()->json([
                'borrowed' => true,
                'user_id' => $activeLoan->user_id,
                'loan_id' => $activeLoan->id,
                'message' => 'Buku sedang dipinjam'
            ]);
        }

        return response()->json([
            'borrowed' => false,
            'message' => 'Buku tersedia'
        ]);
    }

    // UPDATE LOAN
    public function update(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json(['message' => 'Peminjaman tidak ditemukan'], 404);
        }

        $request->validate([
            'user_id' => 'sometimes|required|integer',
            'book_id' => 'sometimes|required|integer',
            'loan_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'return_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|required|string|in:borrowed,returned'
        ]);

        // If user_id is changed, verify it exists in User Service
        if ($request->has('user_id') && $request->user_id != $loan->user_id) {
            $userResponse = Http::get(env('USER_API_URL', 'http://user-api:8000') . '/users/' . $request->user_id);
            if ($userResponse->status() === 404) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 404);
            }
        }

        // If book_id is changed, verify it exists in Book Service
        if ($request->has('book_id') && $request->book_id != $loan->book_id) {
            $bookResponse = Http::get(env('BOOK_API_URL', 'http://book-api:8000') . '/books/' . $request->book_id);
            if ($bookResponse->status() === 404) {
                return response()->json([
                    'message' => 'Buku tidak ditemukan'
                ], 404);
            }
        }

        $loan->fill($request->all());

        if (!$loan->isDirty()) {
            return response()->json([
                'message' => 'tidak ada perubahan data',
                'data' => $loan
            ], 200);
        }

        $loan->save();

        return response()->json([
            'message' => 'Peminjaman berhasil diperbarui',
            'data' => $loan
        ]);
    }

    // DELETE LOAN
    public function destroy($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json(['message' => 'Peminjaman tidak ditemukan'], 404);
        }

        $loan->delete();

        // Re-index all IDs sequentially starting from 1
        \Illuminate\Support\Facades\DB::statement('SET @count = 0');
        \Illuminate\Support\Facades\DB::statement('UPDATE loans SET id = (@count:=@count+1) ORDER BY id ASC');
        $maxId = \Illuminate\Support\Facades\DB::table('loans')->max('id') ?? 0;
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE loans AUTO_INCREMENT = " . ($maxId + 1));

        return response()->json(['message' => 'Peminjaman berhasil dihapus']);
    }
}
