<!-- Modal Structure -->
<div class="modal fade" id="exitModal" tabindex="-1" aria-labelledby="exitModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('exitPurchase', $numeroPurchase) }}" method="POST" id="exitForm">
                @csrf
                <input type="hidden" name="numeroPurchase" value="{{ $numeroPurchase }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="exitModalLabel">Exit Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Etes-vous sûr de vouloir quitter la page?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Quitter</button>
                </div>
            </form>
        </div>
    </div>
</div>