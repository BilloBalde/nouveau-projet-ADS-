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
                            <h4>{{ isset($expense) ? 'Modifier Depense' : 'Ajouter Depense' }}</h4>
                            <h6>{{ isset($expense) ? 'Modifier une depense existante' : 'Ajouter une nouvelle depense' }}</h6>
                        </div>
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ isset($expense) ? route('expenses.update', $expense->id) : route('expenses.store') }}" method="post" id="Register">
                                @csrf
                                @if(isset($expense))
                                    @method('PUT')
                                @endif
                                {{-- Champs cachés --}}
                                <div class="row">
                                    @if(auth()->user()->store_id === null)
                                        <div class="col-lg-3 col-sm-6 col-12">
                                            <div class="form-group">
                                                <label for="store_id">Magasin</label>
                                                <select id="store_id" name="store_id" class="form-control">
                                                    <option value="">Select Store</option>
                                                    @foreach($stores as $store)
                                                        <option value="{{ $store->id }}"
                                                            {{ (old('store_id') == $store->id) || (isset($expense) && $expense->store_id == $store->id) ? 'selected' : '' }}>
                                                            {{ $store->store_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('store_id')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    @else
                                        <input type="hidden" name="store_id" value="{{ isset($expense) ? $expense->store_id : auth()->user()->store_id }}">
                                    @endif
                                    <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="reference">Reference</label>
                                            <input type="text" id="reference" name="reference" class="form-control" value="{{ isset($expense) ? $expense->reference : $ref }}" readonly>
                                            @error('reference')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="expense_categories_id">Category</label>
                                            <select id="expense_categories_id" name="expense_categories_id" class="form-control">
                                                <option value="">Selectionner la categorie</option>
                                                @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ (old('expense_categories_id') == $category->id) || (isset($expense) && $expense->expense_categories_id == $category->id) ? 'selected' : '' }}>
                                                    {{ $category->categoryName }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('expense_categories_id')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="amount">Amount</label>
                                            <input type="number" step="0.01" id="amount" name="amount" class="form-control" value="{{ isset($expense) ? $expense->amount : old('amount') }}">
                                            @error('amount')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="status">Status</label>
                                            <select id="status" name="status" class="form-control">
                                                <option value="">Selectionner le status</option>
                                                <option value="Pending" {{ (old('status') == 'Pending') || (isset($expense) && $expense->status == 'Pending') ? 'selected' : '' }}>Pending</option>
                                                <option value="Completed" {{ (old('status') == 'Completed') || (isset($expense) && $expense->status == 'Completed') ? 'selected' : '' }}>Completed</option>
                                            </select>
                                            @error('status')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="description">Description</label>
                                            <textarea id="description" name="description" class="form-control">{{ isset($expense) ? $expense->description : old('description') }}</textarea>
                                            <span class="error-danger"><strong id="description-error"></strong></span>
                                        </div>
                                        @error('description')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">Submit</button>
                                        <a href="{{ route('expenses.index') }}" class="btn btn-cancel">Cancel</a>
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
