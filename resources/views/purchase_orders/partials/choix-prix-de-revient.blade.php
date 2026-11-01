{{-- Prix de revient : celui du produit est affiché sur chaque ligne, et
     avant l'enregistrement, si l'achat en donne un différent, le gérant
     choisit ligne par ligne : prendre le nouveau, garder l'ancien, ou en
     saisir un autre. Le serveur refait le calcul et applique ce choix ; le
     coût réel de l'achat reste de toute façon sur sa ligne. --}}
<div class="modal fade" id="choixCoutModal" tabindex="-1" aria-labelledby="choixCoutTitre" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="choixCoutTitre">Le prix de revient change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    Cet achat donne un prix de revient différent de celui enregistré pour
                    <span data-choix-nombre></span>. Lequel retenir sur la fiche ?
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th class="text-end">Ancien</th>
                                <th class="text-end">Nouveau</th>
                                <th style="min-width:260px;">Prix de revient retenu</th>
                            </tr>
                        </thead>
                        <tbody data-choix-lignes></tbody>
                    </table>
                </div>
                <div class="text-danger small mt-2" data-choix-erreur style="display:none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Revenir à l'achat</button>
                <button type="button" class="btn btn-submit" data-choix-valider>Enregistrer l'achat</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('poForm');
        const boite = document.getElementById('choixCoutModal');
        if (!form || !boite) return;

        const modale = new bootstrap.Modal(boite);
        const corps = boite.querySelector('[data-choix-lignes]');
        const erreur = boite.querySelector('[data-choix-erreur]');
        const fg = n => new Intl.NumberFormat('fr-FR').format(Math.round(n)) + ' FG';
        let ecarts = [];
        let confirme = false;

        // ── Prix de revient actuel sous chaque produit choisi ──
        function afficherCoutActuel(select) {
            const zone = select.closest('[data-line]')?.querySelector('[data-cout-actuel]');
            if (!zone) return;
            const opt = select.selectedOptions[0];
            const cout = parseFloat(opt?.dataset.cout || '0');
            zone.textContent = !opt?.value ? ''
                : (cout > 0 ? 'Prix de revient actuel : ' + fg(cout) + ' / pièce' : 'Pas encore de prix de revient');
        }
        // select2 déclenche l'événement jQuery « change », pas l'événement natif.
        $(form).on('change', '[data-field="product_id"]', function () { afficherCoutActuel(this); });
        // Lignes déjà présentes (modification d'un achat).
        form.querySelectorAll('[data-field="product_id"]').forEach(afficherCoutActuel);

        function indexDeLigne(ligne) {
            const nom = ligne.querySelector('[data-field="product_id"]')?.name || '';
            const m = nom.match(/^lines\[(\d+)\]/);
            return m ? m[1] : null;
        }

        // ── Avant l'enregistrement : les lignes dont le prix de revient change ──
        form.addEventListener('submit', function (e) {
            if (confirme) return;

            form.querySelectorAll('[data-choix-cout]').forEach(el => el.remove());
            ecarts = [];
            form.querySelectorAll('[data-line]').forEach(function (ligne) {
                const opt = ligne.querySelector('[data-field="product_id"]')?.selectedOptions[0];
                const index = indexDeLigne(ligne);
                if (!opt?.value || index === null) return;
                const ancien = parseFloat(opt.dataset.cout || '0');
                const nouveau = parseFloat(ligne.querySelector('[data-preview-cost]')?.dataset.valeur || '0');
                // Moins d'un franc d'écart : l'arrondi, pas un changement de prix.
                if (ancien > 0 && nouveau > 0 && Math.abs(nouveau - ancien) >= 1) {
                    ecarts.push({ ligne, index, libelle: opt.textContent.trim(), ancien, nouveau });
                }
            });

            if (!ecarts.length) return;
            e.preventDefault();

            corps.innerHTML = '';
            ecarts.forEach(function (ec, i) {
                const tr = document.createElement('tr');
                const groupe = 'choix_' + i;
                const hausse = ec.nouveau > ec.ancien;
                tr.innerHTML =
                    '<td class="fw-medium"></td>' +
                    '<td class="text-end text-nowrap">' + fg(ec.ancien) + '</td>' +
                    '<td class="text-end text-nowrap ' + (hausse ? 'text-danger' : 'text-success') + '">' + fg(ec.nouveau) +
                        ' <small>(' + (hausse ? '+' : '−') + fg(Math.abs(ec.nouveau - ec.ancien)) + ')</small></td>' +
                    '<td>' +
                        '<label class="d-flex align-items-center gap-2 mb-1"><input type="radio" name="' + groupe + '" value="nouveau" checked> Prendre le nouveau</label>' +
                        '<label class="d-flex align-items-center gap-2 mb-1"><input type="radio" name="' + groupe + '" value="ancien"> Garder l\'ancien</label>' +
                        '<label class="d-flex align-items-center gap-2 mb-0"><input type="radio" name="' + groupe + '" value="saisi"> Autre :' +
                            '<input type="number" min="1" step="1" class="form-control form-control-sm" style="width:130px;" data-choix-saisi disabled placeholder="FG / pièce"></label>' +
                    '</td>';
                tr.querySelector('td').textContent = ec.libelle;
                const saisi = tr.querySelector('[data-choix-saisi]');
                tr.querySelectorAll('input[type="radio"]').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        saisi.disabled = radio.value !== 'saisi' || !radio.checked;
                        if (!saisi.disabled) saisi.focus();
                    });
                });
                ec.tr = tr;
                corps.appendChild(tr);
            });
            boite.querySelector('[data-choix-nombre]').textContent =
                ecarts.length === 1 ? 'un produit' : ecarts.length + ' produits';
            erreur.style.display = 'none';
            modale.show();
        });

        boite.querySelector('[data-choix-valider]').addEventListener('click', function () {
            let ok = true;
            const champ = (nom, valeur) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = nom;
                input.value = valeur;
                input.dataset.choixCout = '1';
                return input;
            };

            ecarts.forEach(function (ec) {
                const choix = ec.tr.querySelector('input[type="radio"]:checked').value;
                const saisi = ec.tr.querySelector('[data-choix-saisi]');
                saisi.classList.remove('is-invalid');
                if (choix === 'saisi' && !(parseFloat(saisi.value) > 0)) {
                    saisi.classList.add('is-invalid');
                    ok = false;
                }
            });
            if (!ok) {
                erreur.textContent = 'Saisissez le prix de revient à retenir.';
                erreur.style.display = 'block';
                return;
            }

            ecarts.forEach(function (ec) {
                const choix = ec.tr.querySelector('input[type="radio"]:checked').value;
                form.appendChild(champ('lines[' + ec.index + '][cost_choice]', choix));
                if (choix === 'saisi') {
                    form.appendChild(champ('lines[' + ec.index + '][cost_custom]', ec.tr.querySelector('[data-choix-saisi]').value));
                }
            });

            confirme = true;
            modale.hide();
            form.submit();
        });
    });
</script>
