{{-- Vignette de compte : la photo du profil, à défaut le logo de l'entreprise,
     à défaut les initiales. Le repli comptait auparavant sur un fichier livré
     avec le thème ; quand il a disparu, $avatarUrl est devenu nul et le
     « src="" » qui en résultait rechargeait la page entière, trois fois par
     écran. --}}
@if ($avatarUrl)
    <img src="{{ $avatarUrl }}" alt="">
@else
    <span class="logo-monogramme logo-monogramme--vignette">{{ $avatarInitiales }}</span>
@endif
