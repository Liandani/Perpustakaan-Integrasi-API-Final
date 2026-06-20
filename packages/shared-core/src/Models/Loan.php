<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'loan_date',
        'due_date',
        'return_date',
        'fine_amount',
        'status'
    ];

    protected $casts = [
        'loan_date' => 'date',
        'due_date' => 'date',
    ];

    // 🔵 RELASI KE USER
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔵 RELASI KE BOOK
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
