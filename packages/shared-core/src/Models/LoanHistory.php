<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanHistory extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'borrow_date',
        'return_date',
        'status'
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
