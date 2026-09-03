<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class DeliveryNoteItem extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'delivery_note_id', 'description', 'quantity', 'unit'];

    protected function casts(): array
    {
        return ['quantity' => 'decimal:2'];
    }

    public function deliveryNote()
    {
        return $this->belongsTo(DeliveryNote::class);
    }
}
