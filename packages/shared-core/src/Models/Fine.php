<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    protected $fillable = [
        'loan_id',
        'user_id',
        'book_id',
        'due_date',
        'return_date',
        'late_days',
        'fine_per_day',
        'total_fine',
        'status',
    ];
}