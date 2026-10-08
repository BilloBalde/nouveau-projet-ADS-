{{-- Qui finance cet achat : fonds propres, ou le fonds d'un associé. La
     marchandise reçue lui appartiendra, même rangée avec le reste. --}}
@php $fondsChoisi = old('fund_id', $selectedFundId ?? ''); @endphp
<div class="col-lg-3 col-md-4 col-sm-6 col-12">
    <div class="form-group">
        <label>Financé par</label>
        <select name="fund_id" class="form-control">
            <option value="">{{ \App\Models\Fund::OWNER_LABEL }}</option>
            @foreach ($funds as $fund)
                <option value="{{ $fund->id }}" {{ (string) $fondsChoisi === (string) $fund->id ? 'selected' : '' }}>{{ $fund->name }}</option>
            @endforeach
        </select>
        @error('fund_id') <span class="text-danger">{{ $message }}</span> @enderror
    </div>
</div>
