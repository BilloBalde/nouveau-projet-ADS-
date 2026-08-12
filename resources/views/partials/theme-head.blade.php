{{-- ════════════════════════════════════════════════════════════════════
     SYSTÈME DE DESIGN — Coleah Bandeya & Fils
     Palette : vert forêt (marque) · rouge (action/prix) · blanc (respiration)
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
                        50:  '#f0f8f3',
                        100: '#dcefe3',
                        200: '#b9dfc8',
                        300: '#8bc8a5',
                        400: '#55aa7c',
                        500: '#2e8b57',
                        600: '#1f6f43',
                        700: '#195836',
                        800: '#14452c',
                        900: '#0c2c1c',
                        950: '#061710',
                    },
                    /* Rouge d'accent — prix, promotions, appels à l'action */
                    accent: {
                        50:  '#fef3f2',
                        100: '#fee4e2',
                        200: '#fecdca',
                        300: '#fda29b',
                        400: '#f97066',
                        500: '#ef4444',
                        600: '#d92d20',
                        700: '#b42318',
                        800: '#912018',
                        900: '#7a271a',
                    },
                    /* Alias de compatibilité : d'anciennes vues utilisent encore
                       `amber-*`. Pointé sur le vert de marque pour qu'aucune page
                       non migrée ne reste orange. À retirer une fois tout migré. */
                    amber: {
                        50:  '#f0f8f3',
                        100: '#dcefe3',
                        200: '#b9dfc8',
                        300: '#8bc8a5',
                        400: '#55aa7c',
                        500: '#2e8b57',
                        600: '#1f6f43',
                        700: '#195836',
                        800: '#14452c',
                        900: '#0c2c1c',
                    },
                },
                fontFamily: {
                    display: ['Fraunces', 'Georgia', 'serif'],
                    sans: ['Inter', 'system-ui', 'sans-serif'],
                },
                boxShadow: {
                    soft: '0 1px 2px rgba(12,44,28,.04), 0 8px 24px -12px rgba(12,44,28,.15)',
                    lift: '0 2px 4px rgba(12,44,28,.05), 0 18px 40px -18px rgba(12,44,28,.28)',
                },
            },
        },
    };
</script>

{{-- Chargée en asynchrone : une police lente/injoignable ne doit jamais bloquer
     l'exécution des scripts de la page. --}}
<link rel="preload" as="style"
      href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap"
      onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap"></noscript>

<style>
    :root {
        --brand-50:  #f0f8f3;
        --brand-100: #dcefe3;
        --brand-200: #b9dfc8;
        --brand-300: #8bc8a5;
        --brand-500: #2e8b57;
        --brand-600: #1f6f43;
        --brand-700: #195836;
        --brand-900: #0c2c1c;
        --brand-950: #061710;

        --accent-50:  #fef3f2;
        --accent-100: #fee4e2;
        --accent-500: #ef4444;
        --accent-600: #d92d20;
        --accent-700: #b42318;

        --ink:        #10221a;
        --ink-muted:  #5d6b64;
        --ink-faint:  #93a09a;
        --paper:      #ffffff;
        --paper-warm: #f7faf8;
        --hairline:   #e6efe9;

        --radius-sm: 10px;
        --radius:    16px;
        --radius-lg: 22px;
    }

    body { font-family: 'Inter', system-ui, sans-serif; color: var(--ink); }
    h1, h2, h3, h4 { font-family: 'Fraunces', Georgia, serif; letter-spacing: -0.015em; }

    /* ── Titre de section : surtitre + titre + filet bicolore ── */
    .eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        font-family: 'Inter', sans-serif;
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
        font-family: 'Inter', sans-serif; font-size: 14.5px; font-weight: 600;
        line-height: 1; text-decoration: none; white-space: nowrap;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease, color .18s ease;
    }
    .btn:active { transform: translateY(1px); }
    .btn-brand {
        background: linear-gradient(135deg, var(--brand-600), var(--brand-700));
        color: #fff; box-shadow: 0 10px 24px -12px rgba(31,111,67,.85);
    }
    .btn-brand:hover { box-shadow: 0 16px 30px -12px rgba(31,111,67,.95); transform: translateY(-2px); color: #fff; }
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
        border-radius: var(--radius); box-shadow: 0 1px 2px rgba(12,44,28,.04);
        transition: border-color .22s ease, box-shadow .22s ease, transform .22s ease;
    }
    .surface-hover:hover {
        border-color: var(--brand-300);
        box-shadow: 0 2px 4px rgba(12,44,28,.05), 0 18px 40px -18px rgba(12,44,28,.28);
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
    .glow-brand { background: radial-gradient(circle, rgba(46,139,87,.32) 0%, transparent 70%); }
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
        padding: 2px; font-family: 'Inter', sans-serif; color: var(--ink);
        -moz-appearance: textfield;
    }
    /* Les flèches natives du champ nombre sont minuscules et font doublon avec
       nos boutons : elles embrouillent plus qu'elles n'aident. */
    .qty-input::-webkit-outer-spin-button,
    .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .qty-input:focus {
        outline: none; border-color: var(--brand-500);
        box-shadow: 0 0 0 3px rgba(46,139,87,.14);
    }
    /* Fiche produit : cible tactile de 44px, la recommandation d'accessibilité */
    .prod-qty--lg .qty-btn { width: 44px; height: 44px; font-size: 22px; border-radius: 12px; }
    .prod-qty--lg .qty-input { width: 64px; height: 44px; font-size: 17px; border-radius: 12px; }

    /* ── Bandeau de navigation ──
       Deux états : posé en haut de page, puis densifié dès que le visiteur défile.
       Le filet du bas est une ombre interne et non une bordure, pour que la hauteur
       reste à 83px exactement — c'est sur cette valeur qu'est calé le hero. */
    .nav-dark {
        /* Vert forêt moyen en haut de page, vert quasi noir une fois défilé :
           deux valeurs volontairement éloignées, le passage doit se voir. */
        /* .96 et non .92 : plus le fond est translucide, plus le contraste des liens
           dépend de ce qui défile derrière — à .92 ils tombaient sous 4,5:1. */
        background: rgba(25,88,54,.96);
        backdrop-filter: blur(10px);
        box-shadow: inset 0 -1px 0 rgba(255,255,255,.14), 0 8px 28px -18px rgba(0,0,0,.6);
        transition: background .35s ease, box-shadow .35s ease;
    }
    .nav-dark.is-scrolled {
        background: rgba(5,20,13,.985);
        box-shadow: inset 0 -1px 0 rgba(255,255,255,.06), 0 18px 38px -14px rgba(0,0,0,.95);
    }
    @media (prefers-reduced-motion: reduce) {
        .nav-dark { transition: none; }
    }

    /* ── Cloche de notifications posée sur le bandeau vert ──
       Le partial notification-bell est partagé avec l'espace de gestion, dont
       l'en-tête est resté blanc : ses couleurs claires sont donc appliquées ici,
       dans la feuille du site public que l'admin ne charge pas. */
    .nav-dark .notif-bell-btn { color: rgba(255,255,255,.82); }
    .nav-dark .notif-bell-btn:hover { background: rgba(255,255,255,.12); color: #fff; }

    ::selection { background: var(--brand-200); color: var(--brand-900); }
</style>
