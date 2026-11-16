<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model
{
    protected $fillable = [
        'company', 'contact_name', 'phone', 'email', 'tax_code',
        'delivery_area', 'frequency', 'note', 'source', 'status',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuoteRequestItem::class);
    }
}
