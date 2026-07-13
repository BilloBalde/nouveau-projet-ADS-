<!DOCTYPE html>
<html lang="en">
    @include('layouts.head')
    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>
        <style>
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            td {
                word-wrap: break-word;
            }
            .invoice-box {
                page-break-after: always;
            }
        </style>

        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Details Proforma</h4>
                            <h6>Voire les detailles</h6>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <div class="card-sales-split">
                                <h2>{{ $identifiant }}</h2>
                                <ul>
                                    <li>
                                        <a href="{{ url()->previous() }}"><img src="{{ asset('assets/img/icons/return1.svg') }}" alt="img"></a>
                                    </li>
                                    <li>
                                        <button id="sharePdf" data-name="{{ $identifiant }}" class="btn btn-success">
                                            <i class="fa fa-share"></i>
                                        </button>
                                    </li>
                                    <li>
                                        <button id="downloadPdf" data-name="{{ $identifiant }}"><img src="{{ asset('assets/img/icons/printer.svg') }}" alt="img"></button>
                                    </li>
                                </ul>
                            </div>
                            <div class="invoice-box table-height" style="max-width: 1600px;width:100%;overflow: auto;margin:15px auto;padding: 0;font-size: 14px;line-height: 24px;color: #555;">
                                <table cellpadding="0" cellspacing="0" style="width: 100%;line-height: inherit;text-align: left;">
                                    <tbody>
                                        <tr class="top">
                                            <td colspan="12" style="padding: 5px;vertical-align: top;">
                                                <table style="width: 100%;line-height: inherit;text-align: left;">
                                                    <tbody>
                                                        <tr>
                                                            <td style="padding:5px;vertical-align:top;text-align:left;padding-bottom:20px">
                                                                <font style="vertical-align: inherit;margin-bottom:25px;"><font style="vertical-align: inherit;font-size:14px;color:#7367F0;font-weight:600;line-height: 35px; ">Informations Bureau</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"><img src="{{ asset('companies/'.$compagnie->company_logo) }}" alt="img" class="me-2" style="width:40px;height:40px;">{{ config('APP_NAME') }}
                                                                    </font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="3a4d5b565117535417594f494e55575f487a5f425b574a565f14595557">{{ $user->email }}</a></font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> {{ $user->phone }}</font></font><br>
                                                            </td>
                                                            <td style="padding:5px;vertical-align:top;text-align:left;padding-bottom:20px">
                                                                <font style="vertical-align: inherit;margin-bottom:25px;"><font style="vertical-align: inherit;font-size:14px;color:#7367F0;font-weight:600;line-height: 35px; ">Informations Client</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> {{ $customer->customerName }}</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> <a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="3a4d5b565117535417594f494e55575f487a5f425b574a565f14595557">{{ $customer->email }}</a></font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> {{ $customer->tel }}</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> {{ $customer->address }}</font></font><br>
                                                            </td>
                                                            <td style="padding:5px;vertical-align:top;text-align:left;padding-bottom:20px">
                                                                <font style="vertical-align: inherit;margin-bottom:25px;"><font style="vertical-align: inherit;font-size:14px;color:#7367F0;font-weight:600;line-height: 35px; ">Informations Facture</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> Reference </font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> Status</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> Date D'Invoice</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> Date</font></font><br>
                                                            </td>
                                                            <td style="padding:5px;vertical-align:top;text-align:right;padding-bottom:20px">
                                                                <font style="vertical-align: inherit;margin-bottom:25px;"><font style="vertical-align: inherit;font-size:14px;color:#7367F0;font-weight:600;line-height: 35px; ">&nbsp;</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;">{{ $identifiant }} </font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:{{ ($proforma->status == "commanded") ? '#de2016' : '#2E7D32' }};font-weight: 400;"> {{ $proforma->status }}</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> {{ $dateInvoice }}</font></font><br>
                                                                <font style="vertical-align: inherit;"><font style="vertical-align: inherit;font-size: 14px;color:#000;font-weight: 400;"> {{ Carbon\Carbon::now() }}</font></font><br>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </td>
                                        </tr>
                                        <tr class="heading " style="background: #F3F2F7;">
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                Produit
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                Crtns
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                Qty/Ctn
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                Quanity
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                Prix Unit
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                Montant
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                CBM
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 5px;vertical-align: middle;font-weight: 600;color: #5E5873;font-size: 14px;padding: 10px; ">
                                                WEIGHT
                                            </td>
                                        </tr>
                                        @foreach ($ligneCommandes as $item)
                                        <tr class="details">
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top;">
                                                <div style="display: block; text-align: center;">
                                                    <a><img src="{{ asset('products/' . $item->product->image) }}" alt="img" style="width:70px;height:80px;"></a>
                                                </div>
                                                <div style="display: block; text-align: center;">
                                                    <p>{{ $item->product->item_no }}</p>
                                                </div>
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->cartons }}
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->product->qtityCtn }}
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->quantity }}
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->unit_price }} 元
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->total_price }} 元
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->total_cbm }} cbm
                                            </td>
                                            <td style="border:1px solid #0a0b0c; padding: 10px;vertical-align: top; align-items: center;">
                                                {{ $item->total_weight }}kg
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <br>
                                <div class="row">
                                    <div class="col-lg-6 ">
                                        <div class="total-order w-100 max-widthauto m-auto mb-4">
                                            <ul>
                                                <li class="total">
                                                    <h4>Cartons Total</h4>
                                                    <h5>{{ $proforma->total_ctns }} CTNS</h5>
                                                </li>
                                                <li class="total">
                                                    <h4>Qtity Total</h4>
                                                    <h5>{{ $proforma->total_pcs }} PCS</h5>
                                                </li>
                                                <li class="total">
                                                    <h4>Volume Total</h4>
                                                    <h5>{{ $proforma->total_cbm }} CBM</h5>
                                                </li>
                                                <li class="total">
                                                    <h4>Poids Total</h4>
                                                    <h5>{{ $proforma->total_weight }} KG</h5>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 ">
                                        <div class="total-order w-100 max-widthauto m-auto mb-4">
                                            <ul>
                                                <li class="total">
                                                    <h4>Total Marchandise</h4>
                                                    <h5>{{ $proforma->total_amount }} RMB</h5>
                                                </li>
                                                <li class="total">
                                                    <h4>Commission</h4>
                                                    <h5>{{ $proforma->commission }} RMB</h5>
                                                </li>
                                                <li class="total">
                                                    <h4>Grand Total</h4>
                                                    <h5>{{ $proforma->total_amount + $proforma->commission }} RMB</h5>
                                                    <input type="hidden" name="total_rmb" id="total_rmb" value="{{ $proforma->total_amount + $proforma->commission }}">
                                                </li>
                                                <br>
                                                <li class="total">
                                                    <h4>Taux du Jour RMB</h4>
                                                    <h5><input type="text" name="tauxJour" id="tauxJour" value="7" style="width: 50px;"></h5>
                                                </li>
                                                <li class="total">
                                                    <h4>Grand Total USD</h4>
                                                    <h5><span id="total_usd"></span> USD</h5>
                                                </li>
                                                <br>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12" style="text-align: center">
                                        <p>
                                            {{ $compagnie->company_name }} /
                                            Adresse : {{ $compagnie->company_address }} /
                                            BANK INFO: {{ $compagnie->company_bank }}<br/>
                                            CARGO : {{ $compagnie->cargo_name }} /
                                            Add: {{ $compagnie->cargo_address }}<br/>
                                            <strong>{{ $compagnie->note }}</strong>
                                        </p>
                                    </div>
                                </div>
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
                // Select the section you want to download
                var element = document.querySelector('.invoice-box');
                //console.log(html2pdf);
                console.log(this.getAttribute('data-name'));


                // Configuration options for the PDF
                var opt = {
                    margin:       0.18,
                    filename:     this.getAttribute('data-name')+'.pdf',
                    image:        { type: 'jpeg', quality: 5.0 },
                    html2canvas:  { scale: 2 },
                    jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
                };

                // Create the PDF and download it
                html2pdf().from(element).set(opt).save();
            });
            document.addEventListener('DOMContentLoaded', function () {
                const total_rmb = document.getElementById('total_rmb').value;
                const tauxJour = document.getElementById('tauxJour');
                calculate();
                tauxJour.addEventListener('input', calculate);

                function calculate(){
                    document.getElementById('total_usd').innerText = (total_rmb / (tauxJour.value || 1) ).toFixed(2);
                }
            });
            document.getElementById('sharePdf').addEventListener('click', function () {
                var element = document.querySelector('.invoice-box');
                var fileName = this.getAttribute('data-name') + '.pdf';
            
                var opt = {
                    margin: 0.18,
                    filename: fileName,
                    image: { type: 'jpeg', quality: 1.0 },
                    html2canvas: { scale: 2 },
                    jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
                };
            
                html2pdf().from(element).set(opt).toPdf().output('blob').then(function (pdfBlob) {
                    var file = new File([pdfBlob], fileName, { type: "application/pdf" });
            
                    if (navigator.canShare) {
                        navigator.share({
                            title: "Invoice",
                            text: "Here is your invoice.",
                            files: [file]
                        }).then(() => console.log("Shared successfully"))
                          .catch(error => console.log("Error sharing:", error));
                    } else {
                        // Fallback for devices like iPhone Safari that don’t support navigator.share
                        const blobUrl = URL.createObjectURL(pdfBlob);
                
                        // Simulate download
                        const a = document.createElement('a');
                        a.href = blobUrl;
                        a.download = fileName;
                        a.click();
                
                        // Open in new tab (Safari will show share options for PDFs)
                        window.open(blobUrl, '_blank');
                        
                        alert("Sharing not supported on this device. Please download and share manually.");
                    }
                });
            });
        </script>
    </body>
</html>
