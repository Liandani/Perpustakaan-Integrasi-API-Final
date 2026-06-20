<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\User;
use App\Models\Book;
use App\Models\LoanHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LoanController extends Controller
{
    // GET ALL LOANS
    public function index()
    {
        return response()->json(Loan::all());
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

        // No monolithic foreign key checks. Assume gateway checked it.

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
            'loan' => $loan
        ]);
    }

    // GET LOAN HISTORY
    public function history()
    {
        $histories = LoanHistory::all();

        return response()->json($histories);
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
                'message' => 'Loan not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Loan detail retrieved successfully',
            'data' => $loan
        ]);
    }

    // UPDATE LOAN
    public function update(Request $request, $id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json(['message' => 'Loan tidak ditemukan'], 404);
        }

        $loan->update($request->all());

        return response()->json([
            'message' => 'Loan berhasil diperbarui',
            'loan' => $loan
        ]);
    }

    // DELETE LOAN
    public function destroy($id)
    {
        $loan = Loan::find($id);

        if (!$loan) {
            return response()->json(['message' => 'Loan tidak ditemukan'], 404);
        }

        $loan->delete();

        return response()->json(['message' => 'Loan berhasil dihapus']);
    }
}