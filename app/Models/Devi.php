<?php
// app/Models/Devi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devi extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'valid_until' => 'date',
        'validated_at' => 'datetime',
        'total_amount' => 'decimal:2'
    ];

    protected static function booted()
    {
        static::creating(function ($devis) {
            if (auth()->check()) {
                $devis->created_by = auth()->id();
            }
            
            // Generate numero_devis automatically
            $devis->numero_devis = $devis->generateNumber();
        });
    }

    public function generateNumber()
    {
        $yearMonth = now()->format('Ym');
        $lastDevis = self::where('numero_devis', 'like', 'DEV-' . $yearMonth . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastDevis) {
            $lastNumber = intval(substr($lastDevis->numero_devis, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return 'DEV-' . $yearMonth . '-' . $newNumber;
    }

    public function lines()
    {
        return $this->hasMany(DeviLine::class, 'devis_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'draft' => '<span class="badge bg-secondary">Brouillon</span>',
            'sent' => '<span class="badge bg-info">Envoyé</span>',
            'accepted' => '<span class="badge bg-success">Accepté</span>',
            'rejected' => '<span class="badge bg-danger">Rejeté</span>',
            'expired' => '<span class="badge bg-warning">Expiré</span>',
            default => '<span class="badge bg-secondary">' . $this->status . '</span>'
        };
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total_amount, 0, ',', ' ') . ' FG';
    }

    /**
     * Valider le devis - cela créera une facture et affectera le stock
     */
    public function validate()
    {
        if ($this->status !== 'accepted') {
            throw new \Exception('Seuls les devis acceptés peuvent être validés.');
        }

        if ($this->validated_at) {
            throw new \Exception('Ce devis a déjà été validé.');
        }

        // Create invoice
        $facture = Facture::create([
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'montant_total' => $this->total_amount,
            'quantity' => $this->lines->sum('quantity'),
            'notes' => 'Devis #' . $this->numero_devis . ' - ' . ($this->notes ?? ''),
            'statut' => 'pending',
            'livraison' => 'non livré',
        ]);

        // Create sales from devi lines
        foreach ($this->lines as $line) {
            Sale::create([
                'numeroFacture' => $facture->numero_facture,
                'product_id' => $line->product_id,
                'prix' => $line->unit_price,
                'quantity' => $line->quantity,
                'prixTotal' => $line->total_price,
                'store_id' => $this->store_id,
                'user_id' => auth()->id(),
            ]);

            // Affect stock
            StoreProduct::where('store_id', $this->store_id)
                ->where('product_id', $line->product_id)
                ->decrement('quantity', $line->quantity);
        }

        // Mark devis as validated
        $this->update([
            'validated_by' => auth()->id(),
            'validated_at' => now(),
            'status' => 'validated'
        ]);

        return $facture;
    }
}