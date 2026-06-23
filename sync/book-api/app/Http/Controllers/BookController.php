<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    // GET ALL BOOKS
    public function index()
    {
        return response()->json([
            'message' => 'Daftar semua buku berhasil diambil',
            'data' => Book::all()
        ]);
    }

    // GET BOOK BY ID
    public function show($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'message' => 'Detail buku berhasil diambil',
            'data' => $book
        ]);
    }

    // CHECK STATUS BOOK (AVAILABLE / BORROWED)
    public function status($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        // CALL LOAN-SERVICE (MICROSERVICE WAY)
        $response = Http::get(
            env('LOAN_API_URL', 'http://loan-api:8000') . "/loans/book/{$id}"
        );

        // kalau loan-api error / tidak jalan
        if ($response->failed()) {
            return response()->json([
                'book_id' => $book->id,
                'title' => $book->title,
                'available' => true,
                'message' => 'Loan service tidak tersedia, dianggap buku tersedia'
            ]);
        }

        $loan = $response->json();

        // jika sedang dipinjam
        if (!empty($loan['borrowed']) && $loan['borrowed'] === true) {
            return response()->json([
                'book_id' => $book->id,
                'title' => $book->title,
                'available' => false,
                'message' => 'Buku sedang dipinjam',
                'borrowed_by' => $loan['user_id'] ?? null,
                'loan_id' => $loan['loan_id'] ?? null,
            ]);
        }

        // jika tersedia
        return response()->json([
            'book_id' => $book->id,
            'title' => $book->title,
            'available' => true,
            'message' => 'Buku tersedia untuk dipinjam'
        ]);
    }

    // CREATE BOOK
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'available' => 'boolean'
        ]);

        if ($request->has('available') && !$request->available) {
            return response()->json([
                'message' => 'Buku baru harus tersedia (available harus bernilai true)'
            ], 400);
        }

        $book = Book::create([
            'title' => $request->title,
            'available' => $request->available ?? true,
        ]);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan',
            'data' => $book
        ]);
    }

    // UPDATE BOOK
    public function update(Request $request, $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'title' => 'sometimes|required|string',
            'available' => 'sometimes|required|boolean'
        ]);

        $book->fill($request->all());

        if (!$book->isDirty()) {
            return response()->json([
                'message' => 'tidak ada perubahan data',
                'data' => $book
            ], 200);
        }

        $book->save();

        return response()->json([
            'message' => 'Buku berhasil diperbarui',
            'data' => $book
        ]);
    }

    // DELETE BOOK
    public function destroy($id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $book->delete();

        // Re-index all IDs sequentially starting from 1
        \Illuminate\Support\Facades\DB::statement('SET @count = 0');
        \Illuminate\Support\Facades\DB::statement('UPDATE books SET id = (@count:=@count+1) ORDER BY id ASC');
        $maxId = \Illuminate\Support\Facades\DB::table('books')->max('id') ?? 0;
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE books AUTO_INCREMENT = " . ($maxId + 1));

        return response()->json([
            'message' => 'Buku berhasil dihapus'
        ]);
    }
}
