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
                            <h4>Gestion de Categorie</h4>
                            <h6>Ajouter/Modifier Categorie Emballage</h6>
                        </div>
                        <div class="page-btn">
                            <a href="javascript:history.back();" class="btn btn-added"><img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img" class="me-2"></a>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ isset($categoryEmballage) ? route('categories.update', $categoryEmballage->id) : route('categories.store') }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf
                                @if(isset($categoryEmballage))
                                    @method('PUT')
                                @endif
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="slug">Identifiant</label>
                                            <input type="text" id="slug" name="slug" value="{{ old('slug', $categoryEmballage->slug ?? '') }}" class="form-control">
                                            @error('slug')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="category_type">Type</label>
                                            <input type="text" id="category_type" name="category_type" value="{{ old('category_type', $categoryEmballage->category_type ?? '') }}" class="form-control">
                                            @error('category_type')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-sm-12 col-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <input type="text" name="description" id="description" value="{{ old('description', $categoryEmballage->description?? '') }}" class="form-control"/>
                                            @error('description')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        @if(isset($categoryEmballage))
                                            <x-form-actions mode="edit" cancel-route="{{ route('categories.index') }}"/>
                                        @else
                                            <x-form-actions cancel-route="{{ route('categories.index') }}"/>
                                        @endif
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


