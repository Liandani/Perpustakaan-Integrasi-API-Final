<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FineController extends Controller
{
    // GET ALL FINES
    public function index()
    {
        return response()->json([
            'message' => 'List data denda',
            'data' => Fine::all()
        ]);
    }

    // GET DETAIL FINE BY ID
    public function show($id)
    {
        $fine = Fine::find($id);

        if (!$fine) {
            return response()->json([
                'message' => 'Data denda tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail data denda',
            'data' => $fine
        ]);
    }

    // CHECK AND CALCULATE FINE FROM LOAN DATA
    public function checkFine(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer'
        ]);

        $loanResponse = Http::get("http://loan-api:8000/loans/{$request->loan_id}");

        if ($loanResponse->failed()) {
            return response()->json([
                'message' => 'Data peminjaman tidak ditemukan di sistem peminjaman'
            ], 404);
        }

        $loan = (object) $loanResponse->json('data');

        if (!$loan->return_date) {
            return response()->json([
                'message' => 'Buku belum dikembalikan, denda belum bisa dihitung',
                'data' => $loan
            ], 400);
        }

        $dueDate = Carbon::parse($loan->due_date)->startOfDay();
        $returnDate = Carbon::parse($loan->return_date)->startOfDay();

        $finePerDay = 2000;
        $lateDays = 0;
        $totalFine = 0;
        $status = 'no_fine';

        if ($returnDate->gt($dueDate)) {
            $lateDays = $dueDate->diffInDays($returnDate);
            $totalFine = $lateDays * $finePerDay;
            $status = 'unpaid';
        }

        $fine = Fine::updateOrCreate(
            [
                'loan_id' => $loan->id
            ],
            [
                'user_id' => $loan->user_id,
                'book_id' => $loan->book_id,
                'due_date' => $dueDate->format('Y-m-d H:i:s'),
                'return_date' => $returnDate->format('Y-m-d H:i:s'),
                'late_days' => $lateDays,
                'fine_per_day' => $finePerDay,
                'total_fine' => $totalFine,
                'status' => $status
            ]
        );

        return response()->json([
            'message' => $totalFine > 0
                ? 'Denda berhasil dihitung'
                : 'Tidak ada denda karena pengembalian tepat waktu',
            'data' => [
                'fine' => $fine,
                'loan' => $loan,
                'total_denda_rupiah' => 'Rp ' . number_format($totalFine, 0, ',', '.')
            ]
        ]);
    }

    // GET FINE BY LOAN ID
    public function getByLoan($loan_id)
    {
        $fine = Fine::where('loan_id', $loan_id)->first();

        if (!$fine) {
            return response()->json([
                'message' => 'Denda untuk loan ini belum ada'
            ], 404);
        }

        return response()->json([
            'message' => 'Data denda berdasarkan loan',
            'data' => $fine
        ]);
    }

    // CREATE FINE (MANUAL)
    public function store(Request $request)
    {
        $request->validate([
            'loan_id' => 'required|integer',
            'user_id' => 'required|integer',
            'book_id' => 'required|integer',
            'due_date' => 'required|date',
        ]);

        $fine = Fine::create($request->all());

        return response()->json([
            'message' => 'Fine berhasil ditambahkan',
            'fine' => $fine
        ]);
    }

    // UPDATE FINE
    public function update(Request $request, $id)
    {
        $fine = Fine::find($id);

        if (!$fine) {
            return response()->json(['message' => 'Fine tidak ditemukan'], 404);
        }

        $request->validate([
            'loan_id' => 'sometimes|required|integer',
            'user_id' => 'sometimes|required|integer',
            'book_id' => 'sometimes|required|integer',
            'due_date' => 'sometimes|required|date',
            'return_date' => 'sometimes|nullable|date',
            'late_days' => 'sometimes|required|integer',
            'fine_per_day' => 'sometimes|required|numeric',
            'total_fine' => 'sometimes|required|numeric',
            'status' => 'sometimes|required|string|in:unpaid,paid,no_fine'
        ]);

        $fine->fill($request->all());

        if (!$fine->isDirty()) {
            return response()->json([
                'message' => 'tidak ada perubahan data',
                'data' => $fine
            ], 200);
        }

        $fine->save();

        return response()->json([
            'message' => 'Fine berhasil diperbarui',
            'data' => $fine
        ]);
    }

    // DELETE FINE
    public function destroy($id)
    {
        $fine = Fine::find($id);

        if (!$fine) {
            return response()->json(['message' => 'Fine tidak ditemukan'], 404);
        }

        $fine->delete();

        // Re-index all IDs sequentially starting from 1
        \Illuminate\Support\Facades\DB::statement('SET @count = 0');
        \Illuminate\Support\Facades\DB::statement('UPDATE fines SET id = (@count:=@count+1) ORDER BY id ASC');
        $maxId = \Illuminate\Support\Facades\DB::table('fines')->max('id') ?? 0;
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE fines AUTO_INCREMENT = " . ($maxId + 1));

        return response()->json(['message' => 'Fine berhasil dihapus']);
    }
}