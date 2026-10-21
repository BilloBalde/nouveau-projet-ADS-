<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'motdepasse',
        'username',
        'profilePic',
        'role_id',
        'phone',
        'status',
        'token',
        'description',
        'store_id',
        'fund_id',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /** Administrateur (back-office complet). */
    public const ROLE_ADMIN = 1;

    /** Manager / superuser plateforme. */
    public const ROLE_MANAGER = 2;

    /** Gérant de boutique (tableau de bord magasin). */
    public const ROLE_STORE_MANAGER = 3;

    /** Client e-commerce (boutique en ligne uniquement). */
    public const ROLE_CUSTOMER = 4;

    /** Comptable (finances, paiements, rapports). */
    public const ROLE_COMPTABLE = 5;

    /** Responsable achats (approvisionnement, fournisseurs). */
    public const ROLE_ACHAT = 6;

    /**
     * Employé (accès au back-office) : tout rôle existant autre que « client ».
     * La liste figée des ids 1, 2, 3, 5, 6 excluait les rôles ajoutés ensuite
     * (magasinier, responsable magasin, ou tout rôle créé depuis l'écran des
     * rôles) : ces comptes étaient renvoyés à l'accueil. Ce que chacun peut
     * faire reste décidé par ses permissions.
     */
    public function isStaff(): bool
    {
        return $this->role_id !== null
            && ! $this->isCustomer()
            && $this->roleRelation !== null;
    }

    public function isCustomer(): bool
    {
        return (int) $this->role_id === self::ROLE_CUSTOMER;
    }

    public function getRoleAttribute()
    {
        return $this->roleRelation?->slug ?? '';
    }

    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function factures()
    {
        return $this->hasMany(Facture::class);
    }

    public function addresses()
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /** Fonds dont ce compte est l'associé (lecture seule de son relevé). */
    public function fund()
    {
        return $this->belongsTo(Fund::class);
    }

    /**
     * Compte d'associé : il ne voit que son relevé. Reconnu au rôle « associe »
     * (créé par LocationPermissions), pas à un numéro de rôle.
     */
    public function isAssociate(): bool
    {
        return $this->roleRelation?->slug === 'associe';
    }

    public function storeAssignments()
    {
        return $this->hasMany(StoreAssignment::class)->latest('started_at');
    }

    // -----------------------------------------------------------------
    // Périmètre magasin
    // -----------------------------------------------------------------

    /** Memo par requête, invalidé par assignToStore(). */
    private ?array $accessibleStoreIdsCache = null;

    /**
     * Vrai si l'employé est limité à son magasin, par ses droits : il ne
     * possède pas la permission « stores.all ». Admin, manager, comptable et
     * achats l'ont ; vendeur, magasinier et responsable magasin ne l'ont pas.
     * Sert aux décisions de droits (menus d'administration…), pas au filtrage.
     */
    public function isRestrictedToStore(): bool
    {
        return ! $this->hasPermission('stores.all');
    }

    /**
     * Vrai si les données affichées doivent être limitées à une section :
     * toujours pour un employé rattaché, et pour le propriétaire quand il est
     * « entré » dans une section (sinon il est en vue globale).
     */
    public function isStoreScoped(): bool
    {
        return $this->isRestrictedToStore() || $this->activeSectionId() !== null;
    }

    /**
     * Section choisie par un profil à vue globale (session), ou null pour la
     * vue globale. Un employé rattaché n'a pas de choix : sa section est son
     * magasin d'affectation.
     */
    public function activeSectionId(): ?int
    {
        // La session est celle de l'utilisateur connecté : elle ne vaut pas pour
        // un autre compte manipulé pendant la requête (fiche employé…).
        if ((int) auth()->id() !== (int) $this->id
            || $this->isRestrictedToStore()
            || ! app()->bound('session') || ! request()->hasSession()) {
            return null;
        }

        $id = (int) session('section_id');

        if (! $id) {
            return null;
        }

        // Une seule section : le sélecteur n'existe pas, et une valeur restée en
        // session après une réorganisation des emplacements ne doit pas
        // enfermer le propriétaire dans une section sans moyen d'en sortir.
        if (! Store::plusieursSections()) {
            return null;
        }

        return $this->activeSectionCache[$id] ??= Store::whereKey($id)->exists() ? $id : null;
    }

    /** @var array<int, int|null> */
    private array $activeSectionCache = [];

    /**
     * Section courante : celle choisie par le propriétaire, ou le magasin
     * d'affectation d'un employé. Null = vue globale.
     */
    public function currentSection(): ?Store
    {
        $id = $this->isRestrictedToStore() ? $this->assignedStoreId() : $this->activeSectionId();

        return $id ? Store::find($id) : null;
    }

    /**
     * Magasin d'affectation. users.store_id fait foi ; stores.user_id (l'ancien
     * rattachement, une seule personne par boutique) sert de repli pour les
     * comptes que la migration n'aurait pas pu reprendre. Pour le propriétaire
     * entré dans une section, c'est cette section.
     */
    public function assignedStoreId(): ?int
    {
        if ($section = $this->activeSectionId()) {
            return $section;
        }

        if ($this->store_id) {
            return (int) $this->store_id;
        }

        // Le repli ne vaut que pour un compte jamais affecté par le nouveau
        // mécanisme : un employé explicitement désaffecté ne doit pas retrouver
        // son ancien magasin par ce biais.
        if (StoreAssignment::where('user_id', $this->id)->exists()) {
            return null;
        }

        $legacy = Store::where('user_id', $this->id)->orderBy('id')->value('id');

        return $legacy ? (int) $legacy : null;
    }

    /**
     * Emplacements accessibles : le magasin d'affectation et tout ce qui lui
     * est rattaché (stocks internes, entrepôts). Tableau vide si l'employé n'a
     * pas de magasin — il ne voit alors rien, plutôt que tout.
     * Sans objet pour un profil à vue globale (voir isStoreScoped()).
     */
    public function accessibleStoreIds(): array
    {
        if ($this->accessibleStoreIdsCache !== null) {
            return $this->accessibleStoreIdsCache;
        }

        $storeId = $this->assignedStoreId();

        return $this->accessibleStoreIdsCache = $storeId ? Store::withDescendantIds($storeId) : [];
    }

    public function canAccessStore($storeId): bool
    {
        if (! $storeId) {
            return false;
        }

        return ! $this->isStoreScoped() || in_array((int) $storeId, $this->accessibleStoreIds(), true);
    }

    /**
     * Magasin imposé à l'employé dans les formulaires (null = choix libre).
     * Remplace les anciens `role_id == 3 ? Store::where('user_id', …)` éparpillés.
     */
    public function forcedStoreId(): ?int
    {
        return $this->isStoreScoped() ? $this->assignedStoreId() : null;
    }

    /**
     * Affecte l'employé à un seul magasin. L'affectation précédente est close
     * (historique conservé dans store_assignments), pas écrasée.
     */
    public function assignToStore(?int $storeId, ?int $assignedBy = null): void
    {
        $current = $this->store_id ? (int) $this->store_id : null;

        if ($current === $storeId) {
            return;
        }

        $now = now();

        StoreAssignment::where('user_id', $this->id)->whereNull('ended_at')->update(['ended_at' => $now]);

        if ($storeId) {
            StoreAssignment::create([
                'user_id' => $this->id,
                'store_id' => $storeId,
                'assigned_by' => $assignedBy,
                'started_at' => $now,
            ]);
        }

        $this->store_id = $storeId;
        $this->save();

        $this->accessibleStoreIdsCache = null;
    }

    // -----------------------------------------------------------------
    // Mot de passe
    // -----------------------------------------------------------------

    /**
     * Change le mot de passe en écrivant les DEUX colonnes : `password`, le
     * hash qui authentifie, et `motdepasse`, la copie en clair que l'écran des
     * utilisateurs affiche et que le formulaire de modification pré-remplit.
     *
     * Tout changement de mot de passe doit passer par ici. L'écran de profil et
     * la réinitialisation par e-mail n'écrivaient que le hash : la copie en
     * clair restait figée sur l'ancien mot de passe, donc l'écran des
     * utilisateurs affichait une valeur fausse, et le formulaire de
     * modification le pré-remplissait avec — ce qui faussait aussi la
     * détection « le mot de passe a-t-il changé ? » dans Authentification.
     */
    public function definirMotDePasse(string $motDePasseClair): void
    {
        $this->password = Hash::make($motDePasseClair);
        $this->motdepasse = $motDePasseClair;
    }

    /** Slugs de permissions effectifs, memoïsés pour la durée de la requête. */
    private ?array $permissionSlugs = null;

    /**
     * Vérifie une permission : l'admin passe toujours ; les grants explicites
     * (user_permissions) priment ; sinon fallback sur le preset du rôle.
     */
    public function hasPermission(string $slug): bool
    {
        if ((int) $this->role_id === self::ROLE_ADMIN) {
            return true;
        }

        if ($this->permissionSlugs === null) {
            $own = $this->permissions()->pluck('slug');

            $this->permissionSlugs = $own->isNotEmpty()
                ? $own->all()
                : ($this->roleRelation?->permissions()->pluck('slug')->all() ?? []);
        }

        return in_array($slug, $this->permissionSlugs, true);
    }

    /**
     * Ids des permissions effectives : toutes pour l'admin, sinon les grants
     * explicites (user_permissions) ou, à défaut, le preset du rôle.
     * Sert à limiter ce qu'un non-admin peut accorder à d'autres.
     */
    public function effectivePermissionIds(): Collection
    {
        if ((int) $this->role_id === self::ROLE_ADMIN) {
            return Permission::pluck('id');
        }

        $own = $this->permissions()->pluck('permissions.id');

        return $own->isNotEmpty()
            ? $own
            : ($this->roleRelation?->permissions()->pluck('permissions.id') ?? collect());
    }
}
