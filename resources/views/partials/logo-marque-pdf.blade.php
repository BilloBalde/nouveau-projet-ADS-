{{--
    Logo du commerçant pour un document PDF, ou son monogramme à défaut.

    Séparé de « partials.logo-marque » parce que dompdf lit une image locale par
    son chemin disque, pas par une URL : asset() lui vaudrait une requête HTTP
    vers lui-même, et une image absente au premier pépin réseau. Le monogramme
    est écrit en styles en ligne, dompdf ne résolvant pas les variables CSS.

    Paramètres : $taille (px, défaut 64), $classe.
--}}
@php
    $taille = $taille ?? 64;
    $logo = marque()?->logo;
    $chemin = $logo ? public_path('companies/'.$logo) : null;
    // Le nom enregistré ne garantit pas que le fichier soit là : un logo
    // supprimé du disque faisait échouer le rendu du PDF entier.
    $chemin = $chemin && is_file($chemin) ? $chemin : null;
@endphp
@if ($chemin)
    <img src="{{ $chemin }}" alt="{{ marqueNom() }}"@isset($classe) class="{{ $classe }}"@endisset style="max-width: {{ $taille }}px; height: auto;">
@else
    <span style="display:inline-block;width:{{ $taille }}px;height:{{ $taille }}px;line-height:{{ $taille }}px;text-align:center;border-radius:{{ max(4, (int) round($taille * 0.22)) }}px;background:{{ marqueCouleur('600') }};color:#ffffff;font-weight:700;font-size:{{ max(10, (int) round($taille * 0.4)) }}px;font-family:Georgia,'Times New Roman',serif;">{{ marqueInitiales() }}</span>
@endif
