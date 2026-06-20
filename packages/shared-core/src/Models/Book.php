<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    protected $fillable = [
        'title',
        'available'
    ];

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function loanHistories()
    {
        return $this->hasMany(LoanHistory::class);
    }
}
