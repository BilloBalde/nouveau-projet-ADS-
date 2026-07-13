<?php

namespace App\Models;

use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference',
        'expense_categories_id',
        'amount',
        'status',
        'store_id', 
        'description',
        'user_id',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_categories_id');
    }

    // Relations
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
