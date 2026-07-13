{{-- Thème "Atelier chaleureux" — palette bois/terracotta + typo serif --}}
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    amber: {
                        50:  '#faf6ef',
                        100: '#f4ece1',
                        200: '#e4d3b8',
                        300: '#d9c3a3',
                        400: '#c9986a',
                        500: '#c1682f',
                        600: '#a8532a',
                        700: '#8a5a34',
                        800: '#5a3820',
                        900: '#2b1d14',
                    },
                },
            },
        },
    };
</script>

{{-- Chargée en asynchrone : une police lente/injoignable ne doit jamais bloquer
     l'exécution des scripts de la page. --}}
<link rel="preload" as="style"
      href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"
      onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap"></noscript>

<style>
    h1, h2, h3 { font-family: 'Fraunces', serif; }
</style>
