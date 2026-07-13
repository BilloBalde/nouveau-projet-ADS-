@php
    // Quantité utilisée pour la vente (celle du magasin de l'utilisateur, ou le total si vue multi-magasins)
    if ($userStoreId) {
        $userStoreRow = $dataItem->stores->firstWhere('id', $userStoreId);
        $quantity = $userStoreRow ? $userStoreRow->pivot->quantity : 0;
    } else {
        $quantity = $dataItem->stores->sum('pivot.quantity');
    }
@endphp
@if($quantity > 0)
<div class="col-lg-4 col-sm-4 col-6 d-flex product-item"
     data-numeroFacture="{{ $numeroFacture }}"
     data-sku="{{ $dataItem->sku }}"
     data-id="{{ $dataItem->id }}"
     data-price="{{ $dataItem->price }}">
    <div class="productset flex-fill">
        <div class="productsetimg">
            <img src="{{ asset('products/' . $dataItem->image) }}" alt="img" style="height: 170px" loading="lazy" onerror="this.closest('.productsetimg').classList.add('img-fallback')">
            <div class="check-product">
                <i class="fa fa-check"></i>
            </div>
        </div>
        <div class="productsetcontent">
            <span class="cat-chip">
                @foreach ($dataItem->categories as $cat)
                {{ $cat->slug }}
                @endforeach
            </span>
            <h5>{{ $dataItem->libelle }}</h5>
            <h4>{{ $dataItem->sku }}</h4>
            <h4 class="price-vente">{{ number_format($dataItem->price ?? 0, 0, ',', ' ') }} FG</h4>
            <span class="stock-badge {{ $quantity <= 5 ? 'stock-low' : 'stock-ok' }}">
                @if($quantity <= 5)
                    <i class="fa fa-exclamation-triangle"></i> Stock faible : {{ $quantity }}
                @else
                    <i class="fa fa-check-circle"></i> En stock : {{ $quantity }}
                @endif
            </span>
            @if(!$userStoreId)
                <div class="store-stock-breakdown">
                    @foreach ($dataItem->stores->where('pivot.quantity', '>', 0) as $store)
                        <span class="store-pill">
                            <i class="fa fa-shop"></i> {{ $store->store_name }} : {{ $store->pivot->quantity }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endif
