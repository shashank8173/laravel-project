<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCompany extends Model
{
    protected $table = 'companiesexpense';

    protected $fillable = ['name'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'company_id');
    }
}
