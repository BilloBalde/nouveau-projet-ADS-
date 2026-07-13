<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Etes vous sûr de supprimer cet élement?</p>
                <p><strong>Item ID:</strong> <span id="deleteId"></span></p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Add event listener for all buttons with the class "dropdown-item"
        const deleteButtons = document.querySelectorAll('.deleteButtionItem');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                // Get the data-id from the clicked button
                const dataId = this.getAttribute('data-id');

                // Set the ID in the modal (in the span with id 'deleteId')
                document.getElementById('deleteId').textContent = dataId;

                // Update the form action dynamically
                const form = document.getElementById('deleteForm');
                const deleteAction = this.getAttribute('onclick').match(/'(.*?)'/)[1]; // Extract the URL
                form.setAttribute('action', deleteAction);
            });
        });
    });
</script>
