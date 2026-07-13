<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Add event listener for all buttons with the class "dropdown-item"
        const deleteButtons = document.querySelectorAll('.deleteButtionItem');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                // Get the data-id from the clicked button
                const dataId = this.getAttribute('data-slug');

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
