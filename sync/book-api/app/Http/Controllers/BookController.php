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
        return response()->json(Book::all());
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

        return response()->json($book);
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

        $book = Book::create([
            'title' => $request->title,
            'available' => $request->available ?? true,
        ]);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan',
            'book' => $book
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

        $book->update($request->all());

        return response()->json([
            'message' => 'Buku berhasil diperbarui',
            'book' => $book
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

        return response()->json([
            'message' => 'Buku berhasil dihapus'
        ]);
    }
}
