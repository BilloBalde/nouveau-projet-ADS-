<!DOCTYPE html>
<html lang="fr">
@include('layouts.head')
<head>
    <link rel="stylesheet" href="{{ asset('assets/css/receipt-invoice-style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/invoice-details.css') }}">
</head>
<body>
    <div class="main-wrapper">
        @include('layouts.header')
        @include('layouts.sidebar')

        <div class="page-wrapper">
            <div class="content">
                <div class="page-header no-print">
                    <div class="page-title">
                        <h4>Reçu de Paiement</h4>
                        <h6>{{ $payment->receipt_number ?? 'N/A' }}</h6>
                    </div>
                    <div class="page-btn">
                        <button id="downloadPdf" class="btn btn-added" title="Télécharger en PDF">
                            <i class="fas fa-file-pdf me-2"></i> Télécharger PDF
                        </button>
                       
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="receipt-invoice-wrapper avoid-page-break has-watermark" id="receiptContent">
                            <div class="document-watermark" aria-hidden="true">
                            </div>
                            <!-- Titre principal avec style bleu -->
                            <div class="invoice-title-wrapper">
                                <div class="invoice-title-row">
                                    <div class="invoice-logo">
                                        <img src="{{ asset('images/customers/logo.jpg') }}" alt="{{ $company?->name ?? 'Logo' }}">
                                    </div>
                                    <h1 class="invoice-title">REÇU DE PAIEMENT</h1>
                                </div>
                                <div class="invoice-subtitle">Original</div>
                            </div>
                            
                            <!-- Section numéro de reçu et date -->
                            <div class="invoice-number-section">
                                <div class="invoice-number-row">
                                    <div>
                                        <span class="invoice-number-label">Reçu N°:</span>
                                        <span class="invoice-number-value">#{{ $payment->receipt_number ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="invoice-number-label">Date:</span>
                                        <span class="invoice-number-value">{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y H:i') }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Informations du client comme dans l'image -->
                            <div class="client-info-box">
                                <div class="client-info-row">
                                    <div>
                                        <strong>Client:</strong> {{ $customer?->customerName ?? 'N/A' }}
                                    </div>
                                    <div>
                                        <strong>Téléphone:</strong> {{ $customer?->phone ?? 'N/A' }}
                                    </div>
                                </div>
                                <div class="client-info-row">
                                    <div>
                                        <strong>Adresse:</strong> {{ $customer?->address ?? 'N/A' }}
                                    </div>
                                </div>
                            </div>

                            <!-- Détails de la facture -->
                            @if($facture)
                            <div class="invoice-details">
                                <div class="details-row">
                                    <span>Facture N°:</span>
                                    <span><strong>#{{ $facture->numero_facture }}</strong></span>
                                </div>
                                <div class="details-row">
                                    <span>Date de la facture:</span>
                                    <span>{{ \Carbon\Carbon::parse($facture->created_at)->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            @endif

                            <!-- Tableau des détails de paiement -->
                            <table class="invoice-table">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>DÉSIGNATION</th>
                                        <th>MODE DE PAIEMENT</th>
                                        <th>MONTANT (GNF)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>Paiement de la facture {{ $facture?->numero_facture ?? 'N/A' }}</td>
                                        <td>{{ $payment->paid_by ?? 'N/A' }}</td>
                                        <td>{{ number_format($payment->versement, 0, '.', ' ') }} FG</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="invoice-total">
                                        <td colspan="3"><strong>TOTAL</strong></td>
                                        <td><strong>{{ number_format($payment->versement, 0, '.', ' ') }} FG</strong></td>
                                    </tr>
                                </tfoot>
                            </table>

                            @if($facture)
                                <!-- Résumé de la facture -->
                                <div class="invoice-details">
                                    <div class="details-row">
                                        <span>Montant total de la facture:</span>
                                        <span>{{ number_format($facture->montant_total, 0, '.', ' ') }} FG</span>
                                    </div>
                                    <div class="details-row">
                                        <span>Montant payé:</span>
                                        <span>{{ number_format($payment->versement, 0, '.', ' ') }} FG</span>
                                    </div>
                                    <div class="details-row">
                                        <span>Reste à payer:</span>
                                        <span><strong>{{ number_format($payment->reste ?? $facture->reste, 0, '.', ' ') }} FG</strong></span>
                                    </div>
                                </div>
                            @endif

                            <!-- Montant en lettres comme dans l'image -->
                            <div class="amount-in-words">
                                <strong>Montant en lettres:</strong><br>
                                {{ ucfirst(numberToWords($payment->versement)) }} Francs Guinéens
                            </div>

                            <!-- Section des signatures -->
                            <div class="signature-section">
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p><strong>SIGNATURE DU CLIENT</strong></p>
                                </div>
                                
                                <div class="signature-box">
                                    <div class="signature-line"></div>
                                    <p><strong>POUR {{ strtoupper($company?->name ?? 'SMH') }}</strong></p>
                                </div>
                            </div>

                            <!-- Informations de l'entreprise en bas -->
                            <div class="company-info">
                                <h4>{{ $company?->name ?? 'SMH' }}</h4>
                                <p>{{ $company?->address ?? 'Centre Faloulay, 1er étage B30, Madina route Niger / T6, en face de la station Star, Sonfonia' }}</p>
                                <p>Tél: {{ $company?->phone ?? '+224 626 311 915' }} | Email: {{ $company?->email ?? 'saikououmar47@gmail.com' }}</p>
                                <p>Merci pour votre confiance et à bientôt !</p>
                                <p>Reçu généré le {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layouts.scripts')
    <script>
        // Génération PDF conforme aux normes
        document.addEventListener('DOMContentLoaded', function() {
            const downloadBtn = document.getElementById('downloadPdf');
            const element = document.getElementById('receiptContent');
            const mainWrapper = document.querySelector('.main-wrapper');
            const header = document.querySelector('header');
            const sidebar = document.querySelector('.sidebar');
            const pageHeader = document.querySelector('.page-header');
            
            if (!downloadBtn || !element) {
                console.error('Éléments requis non trouvés');
                return;
            }
            
            // Vérifier que html2pdf est chargé
            function checkHtml2Pdf() {
                if (typeof html2pdf === 'undefined') {
                    console.error('Bibliothèque html2pdf non chargée');
                    setTimeout(checkHtml2Pdf, 100);
                    return false;
                }
                return true;
            }
            
            if (!checkHtml2Pdf()) {
                setTimeout(function() {
                    if (typeof html2pdf === 'undefined') {
                        alert('La bibliothèque PDF n\'est pas chargée. Veuillez rafraîchir la page.');
                        return;
                    }
                }, 2000);
            }
            
            downloadBtn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (typeof html2pdf === 'undefined') {
                    alert('La bibliothèque PDF n\'est pas encore chargée. Veuillez patienter quelques secondes et réessayer.');
                    return;
                }
                
                const filename = 'Recu_Paiement_{{ $payment->receipt_number ?? 'recu' }}.pdf';
                
                // Créer un conteneur isolé pour le PDF (visible mais hors écran)
                const pdfContainer = document.createElement('div');
                pdfContainer.style.position = 'absolute';
                pdfContainer.style.left = '0';
                pdfContainer.style.top = '0';
                pdfContainer.style.width = '210mm';
                pdfContainer.style.maxWidth = '210mm';
                pdfContainer.style.background = 'white';
                pdfContainer.style.zIndex = '99999';
                pdfContainer.style.padding = '0';
                pdfContainer.style.margin = '0';
                pdfContainer.style.visibility = 'visible';
                pdfContainer.style.opacity = '1';
                pdfContainer.style.display = 'flex';
                pdfContainer.style.justifyContent = 'center';
                
                // Cloner le contenu avec deep clone pour inclure tous les styles
                const clonedElement = element.cloneNode(true);
                clonedElement.id = 'receiptContentClone';
                clonedElement.classList.add('pdf-mode');
                clonedElement.style.position = 'relative';
                clonedElement.style.margin = '0 auto';
                clonedElement.style.padding = '0';
                clonedElement.style.width = '100%';
                clonedElement.style.maxWidth = '190mm';
                clonedElement.style.visibility = 'visible';
                clonedElement.style.opacity = '1';
                clonedElement.style.transform = 'scale(0.92)';
                clonedElement.style.transformOrigin = 'top center';
                
                // S'assurer que toutes les images sont chargées et visibles
                const images = clonedElement.querySelectorAll('img');
                images.forEach(img => {
                    if (img.src) {
                        img.style.display = 'block';
                        img.style.maxWidth = '100%';
                        img.style.visibility = 'visible';
                        img.style.opacity = '1';
                    }
                });
                
                pdfContainer.appendChild(clonedElement);
                document.body.appendChild(pdfContainer);
                
                // Faire défiler vers le haut pour s'assurer que le conteneur est visible
                window.scrollTo(0, 0);
                
                // Masquer les éléments non nécessaires
                if (header) header.style.display = 'none';
                if (sidebar) sidebar.style.display = 'none';
                if (pageHeader) pageHeader.style.display = 'none';
                
                // Attendre que le clone soit rendu
                setTimeout(function() {
                    // Configuration PDF pour une seule page A4 - Tout sur une page
                    const opt = {
                        margin: [8, 8, 8, 8], // Marges réduites pour tenir sur une page
                        filename: filename,
                        enableLinks: false,
                        image: { 
                            type: 'jpeg', 
                            quality: 0.98 
                        },
                        html2canvas: { 
                            scale: 1.5,
                            useCORS: true,
                            letterRendering: true,
                            logging: false,
                            backgroundColor: '#ffffff',
                            allowTaint: false,
                            scrollX: 0,
                            scrollY: 0,
                            windowWidth: clonedElement.scrollWidth,
                            windowHeight: clonedElement.scrollHeight
                        },
                        jsPDF: { 
                            unit: 'mm', 
                            format: 'a4',
                            orientation: 'portrait',
                            compress: true
                        },
                        pagebreak: { 
                            mode: ['avoid-all'],
                            avoid: '.receipt-invoice-wrapper, .invoice-title, .invoice-number-section, .client-info-box, .invoice-table, .amount-in-words, .signature-section, .company-info'
                        }
                    };
                    
                    // État de chargement
                    const btn = downloadBtn;
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Génération...';
                    btn.disabled = true;
                    
                    // Générer le PDF depuis le clone isolé (forcer une page)
                    html2pdf().set(opt).from(clonedElement).toPdf().get('pdf').then(function(pdf) {
                        const totalPages = pdf.internal.getNumberOfPages();
                        for (let i = totalPages; i > 1; i--) {
                            pdf.deletePage(i);
                        }
                        pdf.save(filename);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        // Nettoyer : supprimer le conteneur et restaurer l'affichage
                        document.body.removeChild(pdfContainer);
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.style.background = '';
                    }).catch(function(error) {
                        console.error('Erreur lors de la génération du PDF:', error);
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        
                        // Nettoyer même en cas d'erreur
                        if (pdfContainer.parentNode) {
                            document.body.removeChild(pdfContainer);
                        }
                        if (header) header.style.display = '';
                        if (sidebar) sidebar.style.display = '';
                        if (pageHeader) pageHeader.style.display = '';
                        document.body.style.background = '';
                        
                        alert('Erreur lors de la génération du PDF: ' + (error.message || 'Erreur inconnue'));
                    });
                }, 300);
            });
        });
    </script>
</body>
</html>