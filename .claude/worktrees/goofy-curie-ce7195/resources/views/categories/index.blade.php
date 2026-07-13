<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Emballage Category List</h4>
                            <h6>Gerer Votre Emballage Category</h6>
                        </div>
                        <div class="page-btn">
                            <a href="{{ route('categories.create') }}" class="btn btn-added"><img src="assets/img/icons/plus.svg" alt="img" class="me-2">Ajouter une Category</a>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <div class="table-top">
                                <div class="search-set">

                                    <div class="search-input">
                                        <a class="btn btn-searchset"><img src="assets/img/icons/search-white.svg" alt="img"></a>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table  datanew">
                                    <thead>
                                        <tr>
                                            <th>Identifiant</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($categories as $categoryEmballage)
                                        <tr>
                                            <td>{{ $categoryEmballage->slug }}</td>
                                            <td>{{ $categoryEmballage->category_type }}
                                            <td>{{ $categoryEmballage->description }}</td>
                                            <td>
                                                <a class="me-3" href="{{ route('categories.edit', $categoryEmballage->id) }}">
                                                    <img src="assets/img/icons/edit.svg" alt="img">
                                                </a>
                                                <a
                                                    type="button"
                                                    class="me-3 deleteButtionItem"
                                                    data-bs-toggle="modal"
                                                    data-slug="{{ $categoryEmballage->slug }}"
                                                    data-bs-target="#confirmDeleteModal"
                                                    onclick="setDeleteFormAction('{{ route('categories.destroy', $categoryEmballage->id) }}')">
                                                    <img src="assets/img/icons/delete.svg" class="me-2" alt="img">
                                                </a>
                                                {{-- <a class="me-3" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="{{ $categoryEmballage->id }}"><img src="assets/img/icons/delete.svg" alt="img"></a> --}}
                                            </td>
                                        </tr>
                                        @endforeach

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
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
        @include('categories.delete')
    </body>
</html>
