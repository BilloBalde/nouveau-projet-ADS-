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
                            <h4>Gestion des Clients</h4>
                            <h6>Ajouter/Modifier Client</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{ isset($customer) ? route('customers.update', $customer->id) : route('customers.store') }}" method="post" id="Register" enctype="multipart/form-data">
                                @csrf
                                @if(isset($customer))
                                    @method('PUT')
                                @endif
                                <div class="row">
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="mark">Mark Client</label>
                                            <input type="text" id="mark" name="mark" value="{{ old('mark', $customer->mark ?? '') }}" class="form-control">
                                            @error('mark')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="customerName">Nom Client</label>
                                            <input type="text" id="customerName" name="customerName" value="{{ old('customerName', $customer->customerName ?? '') }}" class="form-control">
                                            @error('customerName')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="email">Email Client</label>
                                            <input type="email" id="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-control">
                                            @error('email')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6 col-12">
                                        <div class="form-group">
                                            <label for="tel">Phone Client</label>
                                            <input type="tel" id="tel" name="tel" value="{{ old('tel', $customer->tel ?? '') }}" class="form-control">
                                            @error('tel')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="address">Addresse</label>
                                            <textarea class="form-control" id="address" name="address">{{ old('address', $customer->address ?? '') }}</textarea>
                                            @error('address')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-lg-12">
                                        <button type="submit" class="btn btn-submit me-2">{{ isset($customer) ? 'Update' : 'Submit' }}</button>
                                        <a href="{{ route('customers.index') }}" class="btn btn-cancel">Cancel</a>
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


