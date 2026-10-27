# Mise en ligne

Application de gestion (Laravel 11 / PHP 8.2 / MySQL) : ventes, achats avec coût de
revient, stocks multi-boutiques, devis, factures, dettes, et boutique en ligne.

Le nom, le logo et les coordonnées affichés viennent de la **fiche entreprise en base**,
pas du code : la même application sert plusieurs clients. Voir « Logo et identité ».

> ## À lire avant tout : le mode de vente
>
> L'application fonctionne de **deux façons**, et le choix se fait à l'installation par
> la variable `SALES_MODE`. Ce n'est pas un détail de confort : il change les écrans, les
> menus et la façon dont le chiffre d'affaires est calculé.
>
> | `SALES_MODE` | Pour qui | Comment le chiffre d'affaires est obtenu |
> |---|---|---|
> | `caisse` (défaut) | Boutique qui enregistre chaque vente | Somme des ventes saisies au point de vente |
> | `inventaire` | Boutique qui ne saisit pas ses ventes | Déduit des comptages de stock successifs |
>
> **Se tromper ne provoque aucune erreur visible** : l'application démarre, fonctionne,
> et affiche simplement les mauvais écrans. En mode `caisse` chez un client qui comptait
> son stock, il n'aura ni le menu Inventaires ni son chiffre d'affaires — et rien ne dira
> pourquoi. Demandez lequel avant d'installer.
>
> La marche à suivre complète du mode `inventaire` est à la section 9.

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
| `SALES_MODE` | `caisse` ou `inventaire` — **demandez au client**, voir l'encadré en tête et la section 8 |
| `BRAND_*` / `COMPANY_*` | identité du client — voir la section 2 bis |

> **`APP_DEBUG=false` n'est pas optionnel.** À `true`, la moindre erreur affiche au
> visiteur la trace complète : chemins serveur, requêtes SQL et valeurs de configuration.

> **`APP_URL` doit correspondre au domaine réel.** C'est lui qui construit les liens de
> réinitialisation de mot de passe et les adresses des images dans les e-mails et les PDF.

## 2 bis. Identité du client

Le nom, l'adresse et les couleurs ne sont plus écrits dans le code. Un bloc de
`.env` habille toute l'installation — en-tête, page de connexion, boutons,
menus, documents — et le seeder en pose la fiche entreprise sur une base neuve.

```dotenv
# ── Identité de l'installation ──────────────────────────────────────
BRAND_COLOR="#543FC2"      # nuance principale : boutons, menu actif, titres
BRAND_ACCENT="#C7A150"     # accent : prix, mises en avant, filets
BRAND_SECTOR=commerce      # « Gérez votre activité de … » sur la connexion

COMPANY_NAME="THB BANTIGUEL"
COMPANY_ADDRESS="Boutique 22, en face de la Grande Mosquée, Madina Marché, Conakry, République de Guinée"
COMPANY_EMAIL=thiankoyebarry@gmail.com
COMPANY_PHONE="628 263 636"
COMPANY_PHONE_2="666 321 811"
COMPANY_ABOUT="Commerce de détail — Madina Marché, Conakry."
COMPANY_LOGO=              # nom du fichier déposé dans public/companies/
BRAND_SLOGAN=              # devise du site public ; vide = aucune
```

> `APP_NAME` doit également porter le nom du client : c'est ce qui s'affiche si
> la base est momentanément injoignable, et ce que portent les e-mails sortants.

> **Les guillemets autour de la couleur ne sont pas décoratifs.** Dans un
> `.env`, un `#` non protégé ouvre un commentaire : `BRAND_COLOR=#543FC2`
> arrive **vide**, sans le moindre avertissement, et la palette d'origine
> s'affiche. Écrivez `BRAND_COLOR="#543FC2"` ou `BRAND_COLOR=543FC2`.

### Choisir la couleur

Une seule nuance est à choisir : le reste de la palette — onze tons, du fond de
tableau au menu actif — s'en déduit, à teinte et saturation constantes.

Elle porte du texte blanc sur les boutons et sert de texte sur fond blanc pour
le nom de l'entreprise : il lui faut **4,5:1 de contraste avec le blanc**. Un or
ou un jaune clair ne passent pas, et aucun calcul n'y changera rien — c'est le
choix de la nuance qui le règle.

Sans `BRAND_COLOR`, la palette d'origine (vert) est rendue à l'identique : les
installations déjà livrées ne bougent pas.

### Ce que l'identité couvre

Aucun nom de client n'est plus écrit dans le code — un test l'interdit. Tout ce
qui suit lit la fiche entreprise et la couleur de l'installation :

| | |
|---|---|
| Logiciel | en-tête, menu, boutons, tableaux, icône d'onglet, page de connexion |
| Documents | factures, devis, bons de commande et de sortie, reçus |
| PDF | rapport journalier, exports de ventes, paiements, mouvements, comptages |
| E-mails | bienvenue, mot de passe oublié, formulaire de contact |
| Site public | accueil, catalogue, à propos, contact, bandeau et pied de page |
| Rappels | message WhatsApp de relance de dette |

> Les PDF ne peuvent pas utiliser de variables CSS — dompdf ne les résout pas et
> la couleur deviendrait invisible, sans message d'erreur. Ces vues écrivent donc
> la valeur avec `marqueCouleur()`. Un test le vérifie à chaque exécution.

### Logo

Tant qu'aucun fichier n'est déposé, l'application affiche un **monogramme** aux
initiales de l'entreprise, à la couleur de la marque. C'est volontaire :
l'emblème livré avec le thème appartient à un autre client et n'a rien à faire
en haut des pages d'une nouvelle installation.

Pour mettre le vrai logo : Paramètres > Profil de l'entreprise > Logo. Le
fichier atterrit dans `public/companies/` et sert aussi d'icône d'onglet.

### Vérifier

La fiche entreprise reste modifiable depuis l'interface — `.env` ne fait que
poser les valeurs de départ. Après `db:seed`, contrôlez :

```bash
php artisan tinker
```

```php
App\Models\Company::current()->only(['name', 'address', 'email', 'phone', 'phone_secondary']);
App\Support\IdentiteVisuelle::ton('600');   // la nuance doit être celle du .env
```

## 3. Base de données

```bash
php artisan migrate
php artisan db:seed        # rôles, permissions, fiche entreprise, comptes
```

Après ces deux commandes, une base neuve contient **54 tables**, **9 rôles**,
**58 permissions** et leurs **208 associations**, la fiche entreprise et **3 comptes**,
plus un jeu de démonstration (19 produits, 2 boutiques, 6 clients, ventes, achats).

Les 9 rôles : administrateur, manager, vendeur, client, comptable, responsable achats,
magasinier, responsable magasin, associé.

Les seeders sont idempotents : les relancer ne crée pas de doublon. C'est d'ailleurs la
façon de reprendre les permissions ajoutées par une mise à jour, sur une base déjà en
service :

```bash
php artisan db:seed --class=PermissionSeeder
```

> Pour partir d'une base vierge une fois la prise en main terminée :
> `php artisan client:reset-data`. Elle vide les données commerciales (produits,
> clients, boutiques, stocks, ventes, achats, devis, factures, paiements, dépenses)
> et conserve les comptes, les rôles, les permissions, la fiche entreprise et les
> paramètres. Elle demande confirmation avant d'agir.

### Une seule boutique : pas de sections

Une **section**, c'est un emplacement de premier niveau et tout ce qui lui est
rattaché — ses entrepôts et ses stocks internes. Le mécanisme sert à cloisonner
l'application quand le client tient plusieurs boutiques : le propriétaire
« entre » dans l'une d'elles et ne voit plus que la sienne.

Pour un client qui ne tient **qu'une boutique**, avec son entrepôt et ses
réserves rattachés, il n'y a rien à cloisonner. Rien à régler : l'application
s'en aperçoit seule. Le menu « Sections » et le sélecteur en haut de l'écran
disparaissent, et une section restée en mémoire de session est ignorée.

Il n'y a donc qu'une chose à faire au moment de l'installation : créer
l'entrepôt et les réserves **rattachés à la boutique** (champ « Emplacement
parent »), pas comme des emplacements de premier niveau. Deux boutiques
rattachées à rien = deux sections, et le sélecteur réapparaît — ce qui est le
comportement voulu le jour où le client ouvre une deuxième boutique.

> Le jeu de démonstration est déjà dans ce cas : une boutique
> (« Quincaillerie Madina ») et son dépôt rattaché. Le menu « Sections » n'y
> apparaît donc pas.

Pour savoir sur une installation donnée quels emplacements comptent comme
sections, c'est-à-dire lesquels ne sont rattachés à rien :

```bash
php artisan tinker
```

```php
App\Models\Store::whereNull('parent_id')->pluck('store_name', 'id');
```

Plus d'une ligne = le menu « Sections » s'affiche. Il suffit alors de rattacher
les emplacements de trop à la boutique (« Magasins & entrepôts » → modifier →
champ « Emplacement parent »).

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
`sauvegarde-{date}.sql.gz`. Les fichiers de plus de 14 jours sont supprimés
automatiquement.

Lancer une sauvegarde à la demande :

```
php artisan db:backup
```

Si `mysqldump` n'est pas dans le PATH du serveur, renseigner son chemin complet dans
`.env` via `DB_DUMP_BINARY`.

Restaurer une sauvegarde :

```
gunzip -c storage/app/backups/sauvegarde-AAAA-MM-JJ_HHMMSS.sql.gz | mysql -u UTILISATEUR -p NOM_BASE
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

### Marge appliquée au prix de revient

```
PRICING_SALE_MARGIN=30
```

Pourcentage ajouté au prix de revient pour proposer le prix de vente, à la création d'un
produit comme à chaque achat qui le réapprovisionne. 30 % par défaut.

Un prix de vente **fixé à la main** sur une fiche produit n'est plus recalculé par les
achats suivants : la décision du gérant tient. Pour remettre un produit en automatique,
resaisir exactement le prix conseillé (prix de revient + marge).

En mode `inventaire`, tout prix est considéré comme fixé à la main — voir section 8.

## 7. Optimisation

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

À rejouer après toute modification de `.env` ou des routes.

---

## 8. Installation en mode « inventaire »

À lire seulement si le client **ne saisit pas ses ventes une par une** et calcule son
chiffre d'affaires en comptant son stock. Sinon, laisser `SALES_MODE=caisse` et ignorer
cette section.

### Ce que ce mode change

- Le menu **Stock** gagne « Inventaires et chiffre d'affaires ».
- La caisse (POS), la liste des ventes et la facturation **disparaissent** — côté menu
  comme côté serveur : une adresse connue ou un favori y mènent à un refus.
- Le tableau de bord et le rapport des ventes lisent les comptages, pas les ventes.

### Réglage

```
SALES_MODE=inventaire
```

```bash
php artisan config:clear    # sans ça, la variable reste ignorée
```

> **Oublier `config:clear` est l'erreur la plus fréquente** : la configuration est mise en
> cache, le fichier `.env` est modifié, et rien ne change à l'écran. Si le menu
> « Inventaires » n'apparaît pas, c'est presque toujours ça.

### Les prix de vente doivent être saisis à la main

Dans ce mode, le prix de vente est la **seule** chose qui transforme « 120 sacs
manquants » en francs. Il doit être renseigné sur chaque fiche produit avant le premier
comptage.

L'application s'en charge déjà : en mode `inventaire`, un achat ne recalcule plus le prix
de vente (il met seulement à jour le prix de revient), et le champ « prix de vente » du
formulaire produit devient saisissable et obligatoire. Rien à faire de plus — c'est
indiqué ici pour que vous sachiez pourquoi ce champ se comporte autrement qu'en mode
caisse.

### Premier comptage

Chaque boutique doit être comptée une première fois : ce comptage **pose le stock de
départ** et affiche **0 F de chiffre d'affaires**. C'est normal. Le chiffre apparaît au
comptage suivant, qui mesure ce qui est parti entre les deux.

Prévenez le client, sinon il appellera en disant que l'application ne calcule rien.

### Documentation pour le gérant

Une marche à suivre d'une page, à imprimer et à laisser près de la caisse, est accessible
depuis l'écran « Inventaires et chiffre d'affaires », bouton **Mode opératoire**. Elle
explique les quatre étapes du mois et les trois règles qui décident si le chiffre sera
juste (saisir les achats, passer par les transferts, déclarer les pertes).

## 9. Vérification après mise en ligne

À faire une fois le site en place, dans l'ordre. Chaque ligne prend moins d'une minute.

| | À vérifier | Si ça échoue |
|---|---|---|
| 1 | Le site s'ouvre et la page de connexion s'affiche | `APP_URL`, droits sur `storage/` et `bootstrap/cache/` |
| 2 | Connexion avec le compte administrateur | Base vide : le seeder n'a pas tourné |
| 3 | Le tableau de bord s'affiche sans erreur | Voir `storage/logs/` |
| 4 | Une image de produit s'affiche | `php artisan storage:link` oublié |
| 5 | `php artisan schedule:list` montre **trois** tâches | Ligne de cron absente (section 5) |
| 6 | `php artisan db:backup` produit un fichier dans `storage/app/backups` | `mysqldump` absent du PATH : renseigner `DB_DUMP_BINARY` |
| 7 | Le bon mode est actif : menu « Inventaires » présent **ou** bouton POS présent, selon le client | `SALES_MODE` + `config:clear` |
| 8 | `APP_DEBUG=false` et le site ne montre aucune trace d'erreur | Repasser la variable à `false` et `config:cache` |

## Logo et identité

Le nom, l'adresse et le logo affichés dans l'application, les factures et les e-mails
proviennent de la **fiche entreprise en base**, modifiable depuis *Mon profil*. Le fichier
image se dépose dans `public/companies/`.

## Comptes

Le seeder crée trois comptes de départ, dont l'administrateur
(`admin@example.com`). **Changer les mots de passe et l'adresse e-mail à la première
connexion**, et supprimer les comptes inutilisés.

## Commandes utiles

| Commande | Ce qu'elle fait |
|---|---|
| `php artisan migrate` | Applique les migrations en attente (à relancer à chaque mise à jour) |
| `php artisan db:seed --class=PermissionSeeder` | Reprend les rôles et permissions ajoutés par une mise à jour, sans rien dupliquer |
| `php artisan db:backup` | Sauvegarde la base à la demande |
| `php artisan schedule:list` | Vérifie que les trois tâches planifiées sont bien en place |
| `php artisan client:reset-data` | Vide les données commerciales, garde comptes et paramètres (demande confirmation) |
| `php artisan config:clear` | **Après toute modification du `.env`**, sinon la nouvelle valeur est ignorée |

## Notes techniques

- Le taux de change d'un achat import est saisi à chaque commande ; le taux de référence
  enregistré dans les paramètres de devise ne sert que d'indication.
- Le transport et la douane sont répartis entre les produits au prorata du volume (CBM)
  de chaque ligne, ce qui donne le coût de revient réel par article.
- Une vente en dessous du prix de revient est refusée, à la caisse comme à la modification.
- En mode « inventaire », le chiffre d'affaires d'une période est figé à la validation du
  comptage, avec les prix et les coûts de ce moment-là : un rapport déjà établi ne change
  plus, même si les prix bougent ensuite. Les pertes déclarées sortent du stock mais pas
  du chiffre d'affaires, et sont chiffrées au prix de revient.
