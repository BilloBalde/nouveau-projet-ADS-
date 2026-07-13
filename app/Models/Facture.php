<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facture extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    /**
     * Les attributs qui peuvent être assignés en masse
     */
    protected $fillable = [
        'numero_facture',
        'customer_id',
        'store_id',
        'quantity',
        'montant_total',
        'avance',
        'reste',
        'statut',
        'livraison',
        'notes',
        'order_id', // Ajouter pour la relation avec la commande
        'user_id',  // Ajouter pour l'utilisateur qui a créé la facture
    ];

    /**
     * Accesseur pour le nom complet du client
     */
    public function getCustomerNameAttribute()
    {
        $client = $this->customer;
        if ($client) {
            return $client->customerName . '-' . $client->mark;
        }
        return 'Client inconnu';
    }

    /**
     * Relation avec la boutique (store)
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Relation avec l'utilisateur qui a créé la facture
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le client (customer)
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relation avec les paiements
     */
    public function paiements()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Relation avec les ventes (sales)
     */
    public function sales()
    {
        return $this->hasMany(Sale::class, 'numeroFacture', 'numero_facture');
    }

    /**
     * ============================================
     * RELATION AVEC LA COMMANDE (ORDER) - AJOUT
     * ============================================
     */
    
    /**
     * Relation avec la commande (e-commerce)
     * Si la table orders a une colonne 'invoice_number'
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'numero_facture', 'invoice_number');
    }

    /**
     * OU si vous avez une colonne order_id dans factures
     */
    // public function order()
    // {
    //     return $this->belongsTo(Order::class, 'order_id');
    // }

    /**
     * OU si vous voulez utiliser la relation inverse
     */
    // public function order()
    // {
    //     return $this->hasOne(Order::class, 'invoice_number', 'numero_facture');
    // }

    /**
     * ============================================
     * AUTRES RELATIONS UTILES
     * ============================================
     */

    /**
     * Relation avec les produits via les ventes
     */
    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            Sale::class,
            'numeroFacture',
            'id',
            'numero_facture',
            'product_id'
        );
    }

    /**
     * Vérifier si la facture est payée
     */
    public function isPaid(): bool
    {
        return $this->statut === 'payé';
    }

    /**
     * Vérifier si la facture est partiellement payée
     */
    public function isPartial(): bool
    {
        return $this->statut === 'partiel';
    }

    /**
     * Vérifier si la facture est livrée
     */
    public function isDelivered(): bool
    {
        return $this->livraison === 'livré';
    }

    /**
     * Obtenir le montant total payé
     */
    public function getTotalPaidAttribute()
    {
        return $this->paiements()->sum('versement');
    }

    /**
     * Obtenir le montant restant à payer
     */
    public function getRemainingAttribute()
    {
        return $this->montant_total - $this->getTotalPaidAttribute();
    }
}