<?php

/**
 * Identité visuelle de l'installation. Une ligne par client dans .env, pas un
 * rechercher-remplacer dans les vues : voir App\Support\IdentiteVisuelle.
 */
return [
    /*
     * Nuance principale de la marque — boutons, ligne active du menu, titres.
     * C'est le ton « 600 » ; les dix autres s'en déduisent.
     * Vide = palette d'origine (vert), celle des installations déjà livrées.
     *
     * ATTENTION aux guillemets dans .env : un « # » non protégé ouvre un
     * commentaire, et la valeur arrive vide sans le moindre avertissement.
     *
     *     BRAND_COLOR="#543FC2"     OUI
     *     BRAND_COLOR=543FC2        OUI (le dièse est facultatif)
     *     BRAND_COLOR=#543FC2       NON : lu comme vide : palette d'origine
     *
     * Choisissez une nuance assez soutenue : elle porte du texte blanc sur les
     * boutons et sert de texte sur fond blanc pour le nom de l'entreprise. Il
     * lui faut 4,5:1 de contraste avec le blanc. Les tons clairs — un or, un
     * jaune — ne passent pas, et aucune rampe n'y changera rien.
     */
    'couleur' => env('BRAND_COLOR'),

    /*
     * Métier du commerçant, tel qu'il apparaît sur la page de connexion
     * (« Gérez votre activité de … »). Un mot, en minuscules.
     * Par défaut « quincaillerie » : c'est le métier des installations déjà
     * livrées, qui ne doivent pas changer de texte.
     */
    'metier' => env('BRAND_SECTOR', 'quincaillerie'),

    /*
     * Couleur d'accent : prix, mises en avant, filets. Doit trancher avec la
     * couleur principale sans lui disputer la vedette.
     */
    'accent' => env('BRAND_ACCENT', '#C7A150'),

    /*
     * Devise affichée sur le site public et en pied de page. Laisser vide pour
     * n'en afficher aucune. Par défaut celle des installations déjà livrées.
     */
    'slogan' => env('BRAND_SLOGAN', 'au-delà de la qualité des autres'),

    /*
     * Fiche entreprise posée par le seeder sur une base neuve. Elle reste
     * modifiable ensuite depuis Paramètres > Profil de l'entreprise : ce ne
     * sont que les valeurs de départ, pas la source de vérité.
     *
     * Les valeurs par défaut sont celles de la première installation livrée :
     * les bases déjà en service ne bougent pas.
     */
    /*
     * Vrai si la fiche vient bien de .env. Les valeurs ci-dessous sont celles
     * de la première installation, gardées pour que les bases déjà en service
     * ne changent pas — les appliquer à un autre commerçant lui donnerait
     * l'identité du premier. « identite:appliquer » s'y refuse.
     */
    'entreprise_renseignee' => env('COMPANY_NAME') !== null,

    'entreprise' => [
        'name' => env('COMPANY_NAME', 'COLEAH BANDEYA & FILS'),
        'address' => env('COMPANY_ADDRESS', 'Madina, Ecole Gare Routière Dabola Camion, Conakry'),
        'email' => env('COMPANY_EMAIL', 'abdulghadirybalde@gmail.com'),
        'phone' => env('COMPANY_PHONE', '625 45 35 45'),
        'phone_secondary' => env('COMPANY_PHONE_2', '628 59 31 10'),
        'about' => env('COMPANY_ABOUT', 'Quincaillerie générale : peinture, baguette, pelle, pioche, serrure et divers.'),
        'logo' => env('COMPANY_LOGO', 'ADK.png'),
    ],
];
