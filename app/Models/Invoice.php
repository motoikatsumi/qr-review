<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $connection = 'master';

    protected $fillable = [
        'tenant_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'status',
        'paid_date',
        'notes',
        'billing_company_name_snapshot',
        'billing_address_snapshot',
        'billing_postal_code_snapshot',
    ];

    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'subtotal' => 'integer',
        'tax_amount' => 'integer',
        'total_amount' => 'integer',
        'tax_rate' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public static function nextInvoiceNumber(\Carbon\Carbon $issueDate): string
    {
        $prefix = 'INV-' . $issueDate->format('Y-m') . '-';
        $latest = static::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('invoice_number')
            ->first();
        $seq = 1;
        if ($latest) {
            $lastSeq = (int) substr($latest->invoice_number, strlen($prefix));
            $seq = $lastSeq + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'     => '下書き',
            'sent'      => '送付済み',
            'paid'      => '入金済み',
            'overdue'   => '期限超過',
            'cancelled' => '取消',
            default     => $this->status,
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'     => '#6b7280',
            'sent'      => '#3b82f6',
            'paid'      => '#10b981',
            'overdue'   => '#ef4444',
            'cancelled' => '#9ca3af',
            default     => '#6b7280',
        };
    }
}
