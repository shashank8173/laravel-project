<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    public const ADVANCE_DISBURSEMENT = 'ADVANCE DISBURSEMENT AMOUNT';

    protected $table = 'expense_categories';

    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function isAdvanceDisbursement(): bool
    {
        return strtoupper(trim((string) $this->name)) === self::ADVANCE_DISBURSEMENT;
    }

    public static function ensureAdvanceExists(): self
    {
        return static::query()->firstOrCreate(
            ['name' => self::ADVANCE_DISBURSEMENT],
            ['description' => 'Advance money given to employee; used to calculate remaining balance against expenses.']
        );
    }
}
