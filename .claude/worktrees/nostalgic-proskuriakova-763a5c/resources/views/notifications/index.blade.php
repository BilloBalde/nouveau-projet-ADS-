@if(auth()->user()->isCustomer())
{{-- ============ Version client (storefront Tailwind) ============ --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mes notifications — SMH</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @include('partials.theme-head')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>* { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50">

    @include('partials.storefront-nav')

    <div class="pt-28 pb-16 container mx-auto px-4 sm:px-6 lg:px-8 max-w-3xl">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800"><i class="fas fa-bell text-amber-600 mr-2"></i>Mes notifications</h1>
            @if(auth()->user()->unreadNotifications()->count())
            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button type="submit" class="text-sm font-semibold text-amber-600 hover:text-amber-700 hover:underline">
                    Tout marquer lu
                </button>
            </form>
            @endif
        </div>

        @include('layouts.flash')

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 divide-y divide-gray-50 overflow-hidden">
            @forelse($notifications as $notification)
                <a href="{{ $notification->data['url'] ?? '#' }}"
                   onclick="markRead(event, '{{ $notification->id }}', this.href)"
                   class="block px-6 py-4 hover:bg-amber-50/50 transition {{ $notification->read_at ? '' : 'bg-amber-50/70' }}">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 w-2 h-2 rounded-full shrink-0 {{ $notification->read_at ? 'bg-gray-200' : 'bg-amber-600' }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-800 {{ $notification->read_at ? '' : 'font-semibold' }}">
                                {{ $notification->data['message'] ?? '' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-6 py-14 text-center text-gray-400">
                    <i class="fas fa-bell-slash text-4xl mb-3 block"></i>
                    Aucune notification pour le moment.
                </div>
            @endforelse
        </div>

        <div class="mt-6">{{ $notifications->links() }}</div>
    </div>

    @include('partials.storefront-footer')

    <script>
        function markRead(e, id, url) {
            e.preventDefault();
            fetch(`{{ url('notifications') }}/${id}/read`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            }).finally(() => { if (url && !url.endsWith('#')) window.location.assign(url); });
        }
    </script>
</body>
</html>
@else
{{-- ============ Version staff (layout admin POS) ============ --}}
<!DOCTYPE html>
<html lang="fr">
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
                            <h4>Notifications</h4>
                            <h6>Toutes vos notifications</h6>
                        </div>
                        @if(auth()->user()->unreadNotifications()->count())
                        <div class="page-btn">
                            <form method="POST" action="{{ route('notifications.readAll') }}">
                                @csrf
                                <button type="submit" class="btn btn-added" style="background-color:#c1682f;border-color:#c1682f;">
                                    Tout marquer lu
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @include('layouts.flash')
                    <div class="card">
                        <div class="card-body p-0">
                            @forelse($notifications as $notification)
                                <a href="{{ $notification->data['url'] ?? '#' }}"
                                   onclick="markRead(event, '{{ $notification->id }}', this.href)"
                                   style="display:block;padding:14px 22px;border-bottom:1px solid #f3f4f6;color:#374151;text-decoration:none;{{ $notification->read_at ? '' : 'background:#fdf6ec;font-weight:600;' }}">
                                    {{ $notification->data['message'] ?? '' }}
                                    <span style="display:block;font-size:12px;color:#9ca3af;font-weight:400;margin-top:3px;">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </a>
                            @empty
                                <div style="padding:48px;text-align:center;color:#9ca3af;">
                                    Aucune notification pour le moment.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="mt-3">{{ $notifications->links() }}</div>
                </div>
            </div>
        </div>

        @include('layouts.scripts')
        <script>
            function markRead(e, id, url) {
                e.preventDefault();
                fetch(`{{ url('notifications') }}/${id}/read`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                }).finally(() => { if (url && !url.endsWith('#')) window.location.assign(url); });
            }
        </script>
    </body>
</html>
@endif
