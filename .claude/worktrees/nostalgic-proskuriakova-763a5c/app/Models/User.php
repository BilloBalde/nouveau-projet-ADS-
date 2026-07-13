<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'profilePic',
        'role_id',
        'phone',
        'status',
        'token',
        'description',
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

    public function isStaff(): bool
    {
        return in_array((int) $this->role_id, [
            self::ROLE_ADMIN,
            self::ROLE_MANAGER,
            self::ROLE_STORE_MANAGER,
            self::ROLE_COMPTABLE,
            self::ROLE_ACHAT,
        ], true);
    }

    public function isCustomer(): bool
    {
        return (int) $this->role_id === self::ROLE_CUSTOMER;
    }

    public function getRoleAttribute(){
        return $this->roleRelation?->slug ?? '';
    }

    public function roleRelation(){
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function factures(){
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
    public function effectivePermissionIds(): \Illuminate\Support\Collection
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
