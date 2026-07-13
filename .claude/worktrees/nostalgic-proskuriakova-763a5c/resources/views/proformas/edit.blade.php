@extends('layouts.template')
@section('content')
<div class="content">
    <div class="page-header">
        <div class="page-title">
            <h4>Proforma Management</h4>
            <h6>Update Proforma {{ $proforma->id }}</h6>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            @include('layouts.flash')
            <form action="{{ route('invoiceClients.store') }}" method="POST">
                @csrf
                <input type="hidden" name="proforma_id" value="{{ $proforma->id }}">
                <div class="row">
                    <div class="col-sm-6 col-lg-6 col-12">
                        <div class="form-group">
                            <label for="invoice_date"><strong>Date de Livraison</strong></label>
                            <input type="date" name="invoice_date" id="invoice_date" class="form-control">
                        </div>
                        @error('invoice_date')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="col-sm-6 col-lg-6 col-12">
                        <div class="form-group">
                            <label for="status">Statut</label>
                            <input type="text" name="status" value="delivered" class="form-control" readonly>
                            @error('status')
                            <span class="error-danger"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-submit me-2">Invoicer</button>
                        <a href="{{ url()->previous() }}" class="btn btn-cancel">Cancel</a>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>
@endsection

