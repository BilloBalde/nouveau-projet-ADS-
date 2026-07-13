<!DOCTYPE html>
<html lang="en">
@include('layouts.head')
<body>
    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <style>
                    .receipt-box {
                        background: #fff;
                        border-radius: 12px;
                        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
                        border: 1px solid #E9ECEF;
                    }
                    .receipt-header {
                        background: linear-gradient(135deg, #1c2e5c, #3f6ad8);
                        color: #fff;
                        padding: 18px 20px;
                        border-radius: 10px;
                        margin-bottom: 16px;
                    }
                    .receipt-header h5,
                    .receipt-header div {
                        color: #fff;
                    }
                    .receipt-section-title {
                        font-weight: 600;
                        color: #1c2e5c;
                        margin-bottom: 8px;
                    }
                    .receipt-kv {
                        display: flex;
                        justify-content: space-between;
                        padding: 8px 0;
                        border-bottom: 1px dashed #E9ECEF;
                        font-size: 14px;
                    }
                    .receipt-kv:last-child {
                        border-bottom: 0;
                    }
                    .receipt-badge {
                        background: #e6f0ff;
                        color: #1c2e5c;
                        padding: 6px 12px;
                        border-radius: 999px;
                        font-weight: 600;
                        display: inline-block;
                    }
                    .receipt-table th {
                        background: #f7f9fc;
                    }
                </style>
                <div class="page-header">
                    <div class="page-title">
                        <h4>Reçu Transfert de Stock</h4>
                        <h6>{{ $receiptNumber }}</h6>
                    </div>
                    <div class="page-btn">
                        <button id="downloadPdf" class="btn btn-added">
                            <img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img">
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="receipt-box" style="max-width: 900px;margin:0 auto;padding:20px;">
                            <div class="receipt-header">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <h5>Entreprise</h5>
                                        <div>{{ $company?->name ?? 'N/A' }}</div>
                                        <div>{{ $company?->email ?? '' }}</div>
                                        <div>{{ $company?->phone ?? '' }}</div>
                                    </div>
                                    <div class="col-sm-6 text-end">
                                        <h5>Reçu</h5>
                                        <div>Numéro: {{ $receiptNumber }}</div>
                                        <div>Date: {{ $transfer->created_at }}</div>
                                        <div>Utilisateur: {{ $user?->name }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-6">
                                    <div class="receipt-section-title">Produit</div>
                                    <div class="receipt-kv">
                                        <span>Libellé</span>
                                        <strong>{{ $transfer->product?->libelle ?? 'N/A' }}</strong>
                                    </div>
                                    <div class="receipt-kv">
                                        <span>SKU</span>
                                        <strong>{{ $transfer->product?->sku ?? 'N/A' }}</strong>
                                    </div>
                                </div>
                                <div class="col-sm-6 text-end">
                                    <div class="receipt-section-title">Quantité</div>
                                    <div class="receipt-badge">{{ numberDelimiter($transfer->quantity) }}</div>
                                </div>
                            </div>

                            <table class="table receipt-table">
                                <thead>
                                    <tr>
                                        <th>Source</th>
                                        <th>Destination</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $transfer->fromStore?->store_name ?? 'N/A' }}</td>
                                        <td>{{ $transfer->toStore?->store_name ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="text-center mt-4">
                                <span class="receipt-badge">Transfert confirmé.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    <script>
        document.getElementById('downloadPdf').addEventListener('click', function () {
            var element = document.querySelector('.receipt-box');
            var filename = ({{ json_encode($receiptNumber) }}) + '.pdf';
            var opt = {
                margin: 0.3,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2 },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().from(element).set(opt).save();
        });
    </script>
</body>
</html>
