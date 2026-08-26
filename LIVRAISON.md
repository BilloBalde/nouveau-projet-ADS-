# Mise en ligne — Coleah Bandeya & Fils

Application de gestion (Laravel 11 / PHP 8.2 / MySQL) : ventes, achats avec coût de
revient, stocks multi-boutiques, devis, factures, dettes, et boutique en ligne.

---

## 1. Installation

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 2. À renseigner dans `.env`

Le fichier `.env.example` est un gabarit : **toutes les valeurs ci-dessous doivent être
remplacées** avant la mise en ligne.

| Variable | Valeur attendue |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — **impératif** |
| `APP_URL` | le domaine réel du site, avec `https://` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | accès à la base de production |
| `MAIL_*` | compte d'envoi de l'entreprise |

> **`APP_DEBUG=false` n'est pas optionnel.** À `true`, la moindre erreur affiche au
> visiteur la trace complète : chemins serveur, requêtes SQL et valeurs de configuration.

> **`APP_URL` doit correspondre au domaine réel.** C'est lui qui construit les liens de
> réinitialisation de mot de passe et les adresses des images dans les e-mails et les PDF.

## 3. Base de données

```bash
php artisan migrate
php artisan db:seed        # rôles, permissions, fiche entreprise, comptes
```

Les migrations créent les 50 tables, une par fichier. Le seeder installe les 6 rôles,
les 36 permissions et leurs 102 associations, la fiche entreprise et 3 comptes, puis
un jeu de données de démonstration (produits, boutiques, clients, ventes, achats).

Les seeders sont idempotents : les relancer ne crée pas de doublon.

> Pour partir d'une base vierge une fois la prise en main terminée :
> `php artisan client:reset-data`. Elle vide les données commerciales (produits,
> clients, boutiques, stocks, ventes, achats, devis, factures, paiements, dépenses)
> et conserve les comptes, les rôles, les permissions, la fiche entreprise et les
> paramètres. Elle demande confirmation avant d'agir.

## 4. Fichiers et médias

```bash
php artisan storage:link
```

Sans cette commande, les fichiers déposés depuis l'application ne sont pas servis.
Vérifier que `storage/` et `bootstrap/cache/` sont accessibles en écriture par le serveur web.

## 5. Tâches planifiées

Trois traitements automatiques en dépendent :

| Tâche | Quand | Rôle |
|---|---|---|
| `debt:send-reminders` | samedi 18h | récapitulatif des dettes impayées au personnel |
| `purchase-orders:refresh-status` | 00h10 | passe les achats en « reçu » à leur date de réception |
| `db:backup` | 02h00 | sauvegarde de la base, 14 jours conservés |

Sans cette ligne de cron, **aucun des trois ne s'exécute — sauvegarde comprise** :

```
* * * * * cd /chemin/du/projet && php artisan schedule:run >> /dev/null 2>&1
```

Vérifier après installation : `php artisan schedule:list` doit afficher les trois.

## 5 bis. Sauvegardes

Les copies sont écrites dans `storage/app/backups`, compressées, nommées
`coleah-{date}.sql.gz`. Les fichiers de plus de 14 jours sont supprimés
automatiquement.

Lancer une sauvegarde à la demande :

```
php artisan db:backup
```

Si `mysqldump` n'est pas dans le PATH du serveur, renseigner son chemin complet dans
`.env` via `DB_DUMP_BINARY`.

Restaurer une sauvegarde :

```
gunzip -c storage/app/backups/coleah-AAAA-MM-JJ_HHMMSS.sql.gz | mysql -u UTILISATEUR -p NOM_BASE
```

**Limite importante à connaître.** Ces copies restent **sur le même disque que la base
de données**. Elles protègent d'une fausse manœuvre — une suppression malencontreuse,
une mise à jour ratée — mais **pas d'une panne matérielle ni d'un vol du serveur** :
tout disparaîtrait ensemble.

Une copie hors du serveur reste donc à mettre en place, et c'est le point le plus
important de cette section. Au minimum, une copie hebdomadaire du dossier
`storage/app/backups` vers un disque externe ou un espace de stockage distant.

## 6. Tarifs de gros

Le site vend au prix affiché quelle que soit la quantité commandée.

Les paliers de remise automatique (3+ −5 %, 5+ −10 %, 10+ −15 %) sont codés mais
désactivés : `PRICING_TIERS_ENABLED=false`. Les passer à `true`, puis vider le cache
de configuration, réaffiche le tableau « Tarifs de gros » sur les fiches produit **et**
applique la remise au panier et aux commandes.

Les promotions par produit (prix promo, badge −X %) sont indépendantes de ce réglage
et restent actives.

## 7. Optimisation

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

À rejouer après toute modification de `.env` ou des routes.

---

## Logo et identité

Le nom, l'adresse et le logo affichés dans l'application, les factures et les e-mails
proviennent de la **fiche entreprise en base**, modifiable depuis *Mon profil*. Le fichier
image se dépose dans `public/companies/`.

## Comptes

Le seeder crée trois comptes de départ, dont l'administrateur
(`admin@example.com`). **Changer les mots de passe et l'adresse e-mail à la première
connexion**, et supprimer les comptes inutilisés.

## Notes techniques

- Le taux de change d'un achat import est saisi à chaque commande ; le taux de référence
  enregistré dans les paramètres de devise ne sert que d'indication.
- Le transport et la douane sont répartis entre les produits au prorata du volume (CBM)
  de chaque ligne, ce qui donne le coût de revient réel par article.
- Une vente en dessous du prix de revient est refusée, à la caisse comme à la modification.
