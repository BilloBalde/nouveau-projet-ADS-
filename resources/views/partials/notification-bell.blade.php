{{-- Cloche de notifications autonome (CSS scopé + JS vanilla, polling 30s).
     Fonctionne dans le layout admin (Bootstrap POS) comme dans le storefront (Tailwind). --}}
@auth
<div class="notif-bell-wrap" id="notif-bell">
    <button type="button" class="notif-bell-btn" id="notif-bell-btn" title="Notifications" aria-label="Notifications">
        <i class="fas fa-bell"></i>
        <span class="notif-badge" id="notif-badge" style="display:none">0</span>
    </button>

    <div class="notif-dropdown" id="notif-dropdown">
        <div class="notif-dropdown-head">
            <span>Notifications</span>
            <button type="button" class="notif-mark-all" id="notif-mark-all">Tout marquer lu</button>
        </div>
        <div class="notif-list" id="notif-list">
            <div class="notif-empty">Chargement…</div>
        </div>
        <a href="{{ route('notifications.index') }}" class="notif-see-all">Voir toutes les notifications</a>
    </div>
</div>

<style>
    .notif-bell-wrap { position: relative; display: inline-flex; align-items: center; }
    .notif-bell-btn {
        position: relative; width: 36px; height: 36px; border: none; cursor: pointer;
        border-radius: 50%; background: transparent; color: #6b7280; font-size: 17px;
        display: inline-flex; align-items: center; justify-content: center;
        transition: background .15s, color .15s;
    }
    .notif-bell-btn:hover { background: #faf6ef; color: #c1682f; }
    .notif-badge {
        position: absolute; top: 1px; right: 0;
        min-width: 16px; height: 16px; padding: 0 4px;
        background: #c1682f; color: #fff; border-radius: 999px;
        font-size: 10px; font-weight: 700; line-height: 16px; text-align: center;
    }
    .notif-dropdown {
        display: none; position: absolute; top: calc(100% + 8px); right: -8px;
        width: 330px; max-width: 92vw; background: #fff; border: 1px solid #eee;
        border-radius: 14px; box-shadow: 0 12px 32px rgba(0,0,0,.14); z-index: 1000;
        overflow: hidden; text-align: left;
    }
    .notif-dropdown.open { display: block; }
    .notif-dropdown-head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; border-bottom: 1px solid #f3f4f6;
        font-weight: 700; font-size: 14px; color: #1f2937;
    }
    .notif-mark-all {
        border: none; background: none; cursor: pointer;
        color: #c1682f; font-size: 12px; font-weight: 600; padding: 0;
    }
    .notif-mark-all:hover { text-decoration: underline; }
    .notif-list { max-height: 320px; overflow-y: auto; }
    .notif-item {
        display: block; width: 100%; text-align: left; border: none; background: none; cursor: pointer;
        padding: 11px 16px; border-bottom: 1px solid #f8f8f8;
        font-size: 13px; color: #374151; line-height: 1.45;
    }
    .notif-item:hover { background: #faf6ef; }
    .notif-item.unread { background: #fdf6ec; font-weight: 600; }
    .notif-item.unread:hover { background: #f4ece1; }
    .notif-item .notif-time { display: block; font-size: 11px; color: #9ca3af; font-weight: 400; margin-top: 3px; }
    .notif-empty { padding: 26px 16px; text-align: center; color: #9ca3af; font-size: 13px; }
    .notif-see-all {
        display: block; padding: 11px 16px; text-align: center;
        font-size: 13px; font-weight: 600; color: #c1682f;
        border-top: 1px solid #f3f4f6; text-decoration: none; background: #fff;
    }
    .notif-see-all:hover { background: #faf6ef; color: #a8532a; }
</style>

<script>
(function () {
    const csrf     = @json(csrf_token());
    const feedUrl  = @json(route('notifications.feed'));
    const allUrl   = @json(route('notifications.readAll'));
    const btn      = document.getElementById('notif-bell-btn');
    const badge    = document.getElementById('notif-badge');
    const dropdown = document.getElementById('notif-dropdown');
    const list     = document.getElementById('notif-list');

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s ?? '';
        return d.innerHTML;
    }

    function render(data) {
        badge.style.display = data.unread_count > 0 ? '' : 'none';
        badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;

        if (!data.notifications.length) {
            list.innerHTML = '<div class="notif-empty">Aucune notification.</div>';
            return;
        }
        list.innerHTML = data.notifications.map(n => `
            <button type="button" class="notif-item ${n.read ? '' : 'unread'}" data-id="${n.id}" data-url="${esc(n.url ?? '')}">
                ${esc(n.message)}
                <span class="notif-time">${esc(n.time)}</span>
            </button>
        `).join('');
    }

    function fetchFeed() {
        fetch(feedUrl, { headers: { 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : null)
            .then(d => { if (d) render(d); })
            .catch(() => {});
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
        if (!dropdown.contains(e.target)) dropdown.classList.remove('open');
    });

    list.addEventListener('click', function (e) {
        const item = e.target.closest('.notif-item');
        if (!item) return;
        fetch(`{{ url('notifications') }}/${item.dataset.id}/read`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).finally(() => {
            if (item.dataset.url) window.location.assign(item.dataset.url);
            else { item.classList.remove('unread'); fetchFeed(); }
        });
    });

    document.getElementById('notif-mark-all').addEventListener('click', function () {
        fetch(allUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        }).then(fetchFeed);
    });

    fetchFeed();
    setInterval(fetchFeed, 30000);
})();
</script>
@endauth
