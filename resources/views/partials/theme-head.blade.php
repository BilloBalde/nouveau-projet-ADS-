{{-- Emblème TELICO en favicon pour tout le site public. --}}
<link rel="icon" type="image/png" href="{{ asset('favicon-telico.png') }}">
<link rel="apple-touch-icon" href="{{ asset('companies/telico-embleme.png') }}">
{{-- ════════════════════════════════════════════════════════════════════
     SYSTÈME DE DESIGN — TELICO
     Palette tirée du logo (baobab) : vert TELICO · or baobab (détails) · blanc chaud
     Source unique pour toutes les pages publiques : la config Tailwind, les
     jetons CSS et les classes de composants vivent ici, pas dupliqués par page.
     ════════════════════════════════════════════════════════════════════ --}}
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    /* Vert de marque — structure, navigation, confiance */
                    brand: {
                        50:  '#F4F5EC',
                        100: '#E6E9D5',
                        200: '#CED5AE',
                        300: '#ADB97E',
                        400: '#8C9A55',
                        500: '#6B7A35',
                        600: '#3E4C21',
                        700: '#323D1A',
                        800: '#283114',
                        900: '#1C230D',
                        950: '#10150A',
                    },
                    /* Or baobab — ornement du logo : détails, prix, mises en avant.
                       Les teintes 600+ restent lisibles en texte blanc. */
                    accent: {
                        50:  '#FBF6EA',
                        100: '#F5E9CC',
                        200: '#EBD49D',
                        300: '#DFBC70',
                        400: '#D1AA5C',
                        500: '#C7A150',
                        600: '#9A7A30',
                        700: '#8A6A24',
                        800: '#6D531D',
                        900: '#523E17',
                    },
                    /* Alias de compatibilité : d'anciennes vues utilisent encore
                       `amber-*`. Pointé sur le vert de marque pour qu'aucune page
                       non migrée ne reste orange. À retirer une fois tout migré. */
                    amber: {
                        50:  '#F4F5EC',
                        100: '#E6E9D5',
                        200: '#CED5AE',
                        300: '#ADB97E',
                        400: '#8C9A55',
                        500: '#6B7A35',
                        600: '#3E4C21',
                        700: '#323D1A',
                        800: '#283114',
                        900: '#1C230D',
                    },
                },
                fontFamily: {
                    display: ['Cinzel', 'Georgia', 'serif'],
                    sans: ['"Source Sans 3"', 'system-ui', 'sans-serif'],
                    label: ['"Barlow Condensed"', 'Arial Narrow', 'sans-serif'],
                },
                boxShadow: {
                    soft: '0 1px 2px rgba(28,35,13,.04), 0 8px 24px -12px rgba(28,35,13,.15)',
                    lift: '0 2px 4px rgba(28,35,13,.05), 0 18px 40px -18px rgba(28,35,13,.28)',
                },
            },
        },
    };
</script>

{{-- Chargée en asynchrone : une police lente/injoignable ne doit jamais bloquer
     l'exécution des scripts de la page. --}}
<link rel="preload" as="style"
      href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&display=swap"
      onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Source+Sans+3:wght@400;500;600;700&family=Barlow+Condensed:wght@500;600;700&display=swap"></noscript>

<style>
    :root {
        --brand-50:  #F4F5EC;
        --brand-100: #E6E9D5;
        --brand-200: #CED5AE;
        --brand-300: #ADB97E;
        --brand-500: #6B7A35;
        --brand-600: #3E4C21;
        --brand-700: #323D1A;
        --brand-900: #1C230D;
        --brand-950: #10150A;

        --accent-50:  #FBF6EA;
        --accent-100: #F5E9CC;
        --accent-500: #C7A150;
        --accent-600: #9A7A30;
        --accent-700: #8A6A24;

        --ink:        #1F2414;
        --ink-muted:  #66695A;
        --ink-faint:  #9A9C8A;
        --paper:      #ffffff;
        --paper-warm: #F9F9F3;
        --hairline:   #E6E6D8;

        --radius-sm: 10px;
        --radius:    16px;
        --radius-lg: 22px;
    }

    body { font-family: 'Source Sans 3', system-ui, sans-serif; color: var(--ink); }
    /* Capitales gravées du logo : grands titres seulement. */
    h1, h2 { font-family: 'Cinzel', Georgia, serif; letter-spacing: 0.02em; }
    h3, h4 { font-family: 'Source Sans 3', system-ui, sans-serif; letter-spacing: 0; }

    /* ── Titre de section : surtitre + titre + filet bicolore ── */
    .eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        font-family: 'Source Sans 3', sans-serif;
        font-size: 11px; font-weight: 700; letter-spacing: .16em;
        text-transform: uppercase; color: var(--brand-600);
    }
    .eyebrow::before {
        content: ''; width: 26px; height: 2px; border-radius: 2px;
        background: linear-gradient(90deg, var(--accent-600), var(--brand-500));
    }
    .rule-duo {
        width: 68px; height: 3px; border-radius: 3px; margin-top: 14px;
        background: linear-gradient(90deg, var(--brand-600) 0 55%, var(--accent-600) 55% 100%);
    }
    .rule-duo.is-centered { margin-left: auto; margin-right: auto; }

    /* ── Texte en dégradé vert→rouge, réservé aux mots-clés ── */
    .gradient-text {
        background: linear-gradient(120deg, var(--brand-600), var(--brand-500) 55%, var(--accent-600));
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent; color: transparent;
    }

    /* ── Boutons ── */
    .btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 9px;
        padding: 13px 28px; border-radius: 999px; border: none; cursor: pointer;
        font-family: 'Source Sans 3', sans-serif; font-size: 14.5px; font-weight: 600;
        line-height: 1; text-decoration: none; white-space: nowrap;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
    }
    .btn:active { transform: translateY(1px); }
    .btn-brand {
        background: linear-gradient(135deg, var(--brand-600), var(--brand-700));
        color: #fff; box-shadow: 0 10px 24px -12px rgba(62,76,33,.85);
    }
    .btn-brand:hover { box-shadow: 0 16px 30px -12px rgba(62,76,33,.95); transform: translateY(-2px); color: #fff; }
    .btn-accent {
        background: linear-gradient(135deg, var(--accent-600), var(--accent-700));
        color: #fff; box-shadow: 0 10px 24px -12px rgba(217,45,32,.85);
    }
    .btn-accent:hover { box-shadow: 0 16px 30px -12px rgba(217,45,32,.95); transform: translateY(-2px); color: #fff; }
    .btn-outline {
        background: transparent; color: var(--brand-700);
        box-shadow: inset 0 0 0 1.5px var(--brand-200);
    }
    .btn-outline:hover { background: var(--brand-50); box-shadow: inset 0 0 0 1.5px var(--brand-500); color: var(--brand-700); }
    .btn-on-dark {
        background: rgba(255,255,255,.1); color: #fff;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.28);
        backdrop-filter: blur(6px);
    }
    .btn-on-dark:hover { background: rgba(255,255,255,.18); color: #fff; transform: translateY(-2px); }

    /* ── Surfaces ── */
    .surface {
        background: var(--paper); border: 1px solid var(--hairline);
        border-radius: var(--radius); box-shadow: 0 1px 2px rgba(28,35,13,.04);
        transition: border-color .22s ease, box-shadow .22s ease, transform .22s ease;
    }
    .surface-hover:hover {
        border-color: var(--brand-300);
        box-shadow: 0 2px 4px rgba(28,35,13,.05), 0 18px 40px -18px rgba(28,35,13,.28);
        transform: translateY(-3px);
    }

    /* ── Pastille d'icône ── */
    .icon-pill {
        width: 52px; height: 52px; border-radius: 15px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 19px; background: var(--brand-50); color: var(--brand-600);
    }
    .icon-pill.is-accent { background: var(--accent-50); color: var(--accent-600); }

    /* ── Filigrane décoratif réutilisable (sections sombres) ── */
    .glow-brand { background: radial-gradient(circle, rgba(107,122,53,.32) 0%, transparent 70%); }
    .glow-accent { background: radial-gradient(circle, rgba(217,45,32,.26) 0%, transparent 70%); }

    /* ── Sélecteur de quantité ──
       Définition unique pour toutes les pages : les trois copies précédentes avaient
       déjà divergé (24, 25 et 28px). Volontairement large et coloré — à 24px sur fond
       blanc avec un filet gris, les boutons − / + ne se lisaient pas comme des
       commandes cliquables par un client peu habitué aux achats en ligne. */
    .prod-qty {
        display: flex; align-items: center; gap: 8px;
        margin: 11px 0; flex-wrap: wrap;
    }
    .qty-label {
        width: 100%;
        font-size: 10px; font-weight: 700; letter-spacing: .07em;
        text-transform: uppercase; color: var(--brand-600);
        margin-bottom: 2px;
    }
    .qty-btn {
        width: 36px; height: 36px; border-radius: 10px;
        border: 1.5px solid var(--brand-300);
        background: var(--brand-50);
        color: var(--brand-700); font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 19px; line-height: 1;
        transition: background .18s ease, border-color .18s ease, color .18s ease, transform .1s ease;
    }
    .qty-btn:hover { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
    .qty-btn:active { transform: scale(.94); }
    .qty-btn:focus-visible { outline: 3px solid var(--brand-300); outline-offset: 2px; }
    .qty-input {
        width: 52px; height: 36px; text-align: center;
        font-size: 15px; font-weight: 600;
        border: 1.5px solid var(--brand-200); border-radius: 10px;
        padding: 2px; font-family: 'Source Sans 3', sans-serif; color: var(--ink);
        -moz-appearance: textfield;
    }
    /* Les flèches natives du champ nombre sont minuscules et font doublon avec
       nos boutons : elles embrouillent plus qu'elles n'aident. */
    .qty-input::-webkit-outer-spin-button,
    .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .qty-input:focus {
        outline: none; border-color: var(--brand-500);
        box-shadow: 0 0 0 3px rgba(107,122,53,.14);
    }
    /* Fiche produit : cible tactile de 44px, la recommandation d'accessibilité */
    .prod-qty--lg .qty-btn { width: 44px; height: 44px; font-size: 22px; border-radius: 12px; }
    .prod-qty--lg .qty-input { width: 64px; height: 44px; font-size: 17px; border-radius: 12px; }

    /* ── Bandeau de navigation ──
       Deux états : posé en haut de page, puis densifié dès que le visiteur défile.
       Le filet du bas est une ombre interne et non une bordure, pour que la hauteur
       reste à 83px exactement — c'est sur cette valeur qu'est calé le hero. */
    .nav-dark {
        /* Bandeau clair TELICO : blanc, logo posé directement dessus, liens vert
           TELICO, filet ivoire. Une ombre se dessine dès que la page défile. */
        background: rgba(255,255,255,.97);
        backdrop-filter: blur(10px);
        box-shadow: inset 0 -1px 0 #E6E6D8;
        transition: box-shadow .35s ease, background .35s ease;
    }
    .nav-dark.is-scrolled {
        background: #FFFFFF;
        box-shadow: inset 0 -1px 0 #E6E6D8, 0 14px 30px -18px rgba(28,35,13,.45);
    }
    @media (prefers-reduced-motion: reduce) {
        .nav-dark { transition: none; }
    }

    /* Cloche de notifications : couleurs du bandeau clair. */
    .nav-dark .notif-bell-btn { color: #3E4C21; }
    .nav-dark .notif-bell-btn:hover { background: #F4F5EC; color: #1C230D; }

    ::selection { background: var(--brand-200); color: var(--brand-900); }
</style>
