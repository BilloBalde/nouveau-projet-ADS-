<div class="modal fade" role="dialog" tabindex="-1" id="deleteModal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Suppression</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="p-5">
                    @yield('suppression')
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" type="button" id="fermerModal" data-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
</div>
