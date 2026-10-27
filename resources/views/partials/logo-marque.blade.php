{{--
    Logo du commerçant, ou son monogramme tant qu'aucun fichier n'a été
    téléversé. Utilisable partout — écrans, e-mails, PDF — d'où les styles
    écrits en ligne : un e-mail n'a pas de feuille de style et dompdf ne
    connaît pas les variables CSS.

    Paramètres : $taille (px, défaut 48), $classe, $style.
--}}
@php
    $urlLogo = marqueLogoUrl();
    $taille = $taille ?? 48;
@endphp
@if ($urlLogo)
    <img src="{{ $urlLogo }}" alt="{{ marqueNom() }}"@isset($classe) class="{{ $classe }}"@endisset @isset($style) style="{{ $style }}"@endisset>
@else
    <span style="display:inline-block;width:{{ $taille }}px;height:{{ $taille }}px;line-height:{{ $taille }}px;text-align:center;border-radius:{{ max(4, (int) round($taille * 0.22)) }}px;background:{{ marqueCouleur('600') }};color:#ffffff;font-weight:700;font-size:{{ max(10, (int) round($taille * 0.4)) }}px;font-family:Georgia,'Times New Roman',serif;letter-spacing:.03em;">{{ marqueInitiales() }}</span>
@endif
