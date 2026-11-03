{{-- Fenêtre de confirmation, à la place du confirm() gris du navigateur.

     Tout formulaire qui porte data-confirmer l'utilise :
       data-confirmer="Supprimer le comptage INV-0001 ?"   titre (obligatoire)
       data-confirmer-texte="…"                            explication
       data-confirmer-bouton="Supprimer"                    libellé du bouton
       data-confirmer-ton="danger" | "attention"            couleur (défaut : marque)
       data-confirmer-icone="fa-trash"                      icône Font Awesome --}}
<div class="modal fade" id="fenetreConfirmation" tabindex="-1" aria-labelledby="fenetreConfirmationTitre" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content confirmation">
            <div class="modal-body text-center px-4 pt-4 pb-3">
                <div class="confirmation-icone" data-confirmation-pastille>
                    <i class="fas fa-question" data-confirmation-icone></i>
                </div>
                <h5 class="mb-2" id="fenetreConfirmationTitre" data-confirmation-titre></h5>
                <p class="text-muted mb-0" data-confirmation-texte></p>
            </div>
            <div class="modal-footer justify-content-center gap-2 border-0 pt-0 pb-4">
                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn" data-confirmation-valider>Confirmer</button>
            </div>
        </div>
    </div>
</div>
<style>
    .confirmation .confirmation-icone {
        display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px; margin-bottom: 14px; border-radius: 50%;
        font-size: 1.35rem;
        background: var(--adm-brand-50, #F4F5EC); color: var(--adm-brand-600, #3E4C21);
    }
    .confirmation .confirmation-icone.ton-danger { background: #FDECEA; color: #C0392B; }
    .confirmation .confirmation-icone.ton-attention { background: #FFF4E0; color: #B9770E; }
    .confirmation h5 { font-weight: 700; }
    .confirmation p { font-size: .93rem; line-height: 1.5; }
    .confirmation [data-confirmation-valider] { min-width: 130px; padding: 9px 18px; color: #fff; border: 0; }
    .confirmation [data-confirmation-valider].ton-marque { background: var(--adm-brand-600, #3E4C21); }
    .confirmation [data-confirmation-valider].ton-marque:hover { background: var(--adm-brand-700, #323D1A); }
    .confirmation [data-confirmation-valider].ton-danger { background: #C0392B; }
    .confirmation [data-confirmation-valider].ton-danger:hover { background: #A93226; }
    .confirmation [data-confirmation-valider].ton-attention { background: #B9770E; }
    .confirmation [data-confirmation-valider].ton-attention:hover { background: #9C640C; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const boite = document.getElementById('fenetreConfirmation');
        if (!boite || typeof bootstrap === 'undefined') return;

        const modale = new bootstrap.Modal(boite);
        const pastille = boite.querySelector('[data-confirmation-pastille]');
        const icone = boite.querySelector('[data-confirmation-icone]');
        const valider = boite.querySelector('[data-confirmation-valider]');
        let formulaire = null;

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || !form.dataset.confirmer || form.dataset.confirme === '1') return;
            e.preventDefault();
            formulaire = form;

            const ton = ['danger', 'attention'].includes(form.dataset.confirmerTon) ? form.dataset.confirmerTon : 'marque';
            boite.querySelector('[data-confirmation-titre]').textContent = form.dataset.confirmer;
            boite.querySelector('[data-confirmation-texte]').textContent = form.dataset.confirmerTexte || '';
            pastille.className = 'confirmation-icone ton-' + ton;
            icone.className = 'fas ' + (form.dataset.confirmerIcone || (ton === 'danger' ? 'fa-trash' : 'fa-question'));
            valider.className = 'btn ton-' + ton;
            valider.textContent = form.dataset.confirmerBouton || 'Confirmer';
            modale.show();
        }, true);

        boite.addEventListener('shown.bs.modal', () => valider.focus());

        valider.addEventListener('click', function () {
            if (!formulaire) return;
            formulaire.dataset.confirme = '1';
            valider.disabled = true;
            formulaire.submit();
        });

        boite.addEventListener('hidden.bs.modal', function () {
            valider.disabled = false;
            if (formulaire && formulaire.dataset.confirme !== '1') formulaire = null;
        });
    });
</script>
