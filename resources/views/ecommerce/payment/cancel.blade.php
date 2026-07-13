<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement annulé — SMH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-red-50 to-orange-100 flex items-center justify-center p-4">

    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full p-10 text-center">

        <div class="flex items-center justify-center w-24 h-24 bg-red-100 rounded-full mx-auto mb-6">
            <i class="fas fa-times-circle text-red-400 text-5xl"></i>
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-2">Paiement annulé</h1>
        <p class="text-gray-500 mb-6 text-sm leading-relaxed">
            Vous avez annulé le paiement ou une erreur est survenue.<br>
            Votre commande est conservée et vous pouvez réessayer.
        </p>

        @if($order)
        <div class="bg-gray-50 rounded-2xl p-5 mb-6 text-left">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-500 text-sm">N° Commande</span>
                <span class="font-bold text-gray-800">#{{ $order->id }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-500 text-sm">Montant</span>
                <span class="font-bold text-gray-800">{{ number_format($order->total_amount, 0, ',', ' ') }} GNF</span>
            </div>
        </div>
        @endif

        <div class="flex flex-col gap-3">
            @if($order)
            <a href="{{ route('payment.pay', $order->id) }}"
               class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-xl transition-colors">
                <i class="fas fa-redo me-2"></i> Réessayer le paiement
            </a>
            @endif
            <a href="{{ route('orders.index') }}"
               class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition-colors">
                <i class="fas fa-list me-2"></i> Voir mes commandes
            </a>
            <a href="{{ route('shop.home') }}"
               class="w-full bg-gray-50 hover:bg-gray-100 text-gray-500 font-semibold py-3 rounded-xl transition-colors text-sm">
                Retour à la boutique
            </a>
        </div>
    </div>

</body>
</html>
