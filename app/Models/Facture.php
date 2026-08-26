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
     * Montant réellement encaissé sur cette facture.
     *
     * Volontairement calculé depuis `reste`, et non depuis `statut`, `avance` ou la
     * somme des paiements — les trois ont un angle mort :
     *  - `statut` n'est écrit qu'en « payé » / « non payé » : une facture soldée à
     *    moitié y figure comme non payée ;
     *  - `avance` n'est renseigné qu'à la création de la vente, jamais mis à jour par
     *    les encaissements suivants ;
     *  - `payments.total_paye` est un cumul, pas un montant unitaire : en faire la
     *    somme compte plusieurs fois le même argent.
     *
     * `reste` est le seul champ que tiennent à jour les deux chemins, la création de
     * la vente comme l'enregistrement d'un paiement.
     */
    public function montantEncaisse(): float
    {
        return max(0, (float) $this->montant_total - (float) $this->reste);
    }

    /**
     * La facture a-t-elle reçu de l'argent, en totalité ou en partie ?
     *
     * Trois conditions cumulées là où une seule suffirait aujourd'hui : elles
     * concordent sur les données actuelles, et le cumul garantit qu'un futur chemin
     * qui n'en mettrait qu'une à jour ne rouvrira pas la porte à la suppression d'un
     * encaissement.
     */
    public function aRecuUnPaiement(): bool
    {
        return $this->montantEncaisse() > 0
            || (float) $this->avance > 0
            || $this->paiements()->where('versement', '>', 0)->exists();
    }

    /**
     * Vérifier si la facture est intégralement payée
     */
    public function isPaid(): bool
    {
        return (float) $this->montant_total > 0
            && $this->montantEncaisse() >= (float) $this->montant_total;
    }

    /**
     * Vérifier si la facture est partiellement payée
     */
    public function isPartial(): bool
    {
        return $this->aRecuUnPaiement() && ! $this->isPaid();
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