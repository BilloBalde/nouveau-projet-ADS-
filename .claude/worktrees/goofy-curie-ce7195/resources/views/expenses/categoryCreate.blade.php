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
                            <h4>Gestion des Depenses Categories</h4>
                            <h6>Ajouter/Modifier Category Depense</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ isset($expensesCategory) ? route('expensesCategory.update', $expensesCategory->id) : route('expensesCategory.store') }}" method="post" id="Register">
                                @csrf
                                @if(isset($expensesCategory))
                                    @method('PUT')
                                @endif
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="slug">Slug</label>
                                            <input type="text" id="slug" name="slug" value="{{ old('slug', $expensesCategory->slug ?? '') }}">
                                            @error('slug')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="categoryName">Category Name</label>
                                            <input type="text" id="categoryName" name="categoryName" value="{{ old('categoryName', $expensesCategory->categoryName ?? '') }}">
                                            @error('categoryName')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">{{ isset($expensesCategory) ? 'Update' : 'Submit' }}</button>
                                        <a href="{{ route('expensesCategory.index') }}" class="btn btn-cancel">Cancel</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>


