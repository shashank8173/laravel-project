<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $table = 'employee_expenses';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'category_id',
        'expense_date',
        'amount',
        'description',
        'receipt_path',
        'status',
        'approved_by',
        'approved_at',
        'submitted_at',
        'payment_method',
        'reference_id',
        'company_id',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'approved_at' => 'datetime',
            'submitted_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(ExpenseCompany::class, 'company_id');
    }

    public function receiptUrl(): ?string
    {
        $path = trim((string) ($this->receipt_path ?? ''));
        if ($path === '') {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        }

        if (is_file(public_path($path))) {
            return asset($path);
        }

        $legacy = base_path('../hrmpulse_live-main/'.$path);
        if (is_file($legacy)) {
            $target = 'receipts/legacy/'.basename($legacy);
            if (! \Illuminate\Support\Facades\Storage::disk('public')->exists($target)) {
                \Illuminate\Support\Facades\Storage::disk('public')->put($target, file_get_contents($legacy));
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->url($target);
        }

        return asset($path);
    }

    public function entityName(): string
    {
        if ($this->employee) {
            return $this->employee->full_name;
        }
        if ($this->company) {
            return $this->company->name;
        }

        return '—';
    }

    public function isAdvanceDisbursement(): bool
    {
        if ($this->relationLoaded('category') && $this->category) {
            return $this->category->isAdvanceDisbursement();
        }

        $name = optional($this->category()->first())->name;

        return strtoupper(trim((string) $name)) === ExpenseCategory::ADVANCE_DISBURSEMENT;
    }
}
