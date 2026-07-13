<!DOCTYPE html>
<html lang="fr">
    @include('layouts.head')
    <body>
        <style>
            /* La pagination côté serveur (Laravel) fait déjà foi ici — celle
               générée automatiquement par le plugin DataTable ferait doublon
               et affiche un total incorrect (limité à la page chargée). */
            .dataTables_paginate, .dataTables_info {
                display: none !important;
            }
        </style>
        <div id="global-loader"><div class="whirly-loader"></div></div>
        <div class="main-wrapper">
            @include('layouts.header')
            @include('layouts.sidebar')
            <div class="page-wrapper">
                <div class="content">
                    <div class="page-header">
                        <div class="page-title">
                            <h4>Messages de contact</h4>
                            <h6>Messages reçus via le formulaire de contact</h6>
                        </div>
                    </div>

                    @include('layouts.flash')

                    <div class="card">
                        <div class="card-body">
                            @if($messages->isEmpty())
                                <p class="text-center text-muted py-4">Aucun message reçu pour le moment.</p>
                            @else
                            <div class="table-responsive">
                                <table class="table datanew">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Sujet</th>
                                            <th>Message</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($messages as $msg)
                                        <tr style="{{ $msg->is_read ? '' : 'font-weight:600;background:#fffbeb;' }}">
                                            <td style="white-space:nowrap">{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $msg->name }}</td>
                                            <td><a href="mailto:{{ $msg->email }}">{{ $msg->email }}</a></td>
                                            <td>{{ $msg->phone ?? '—' }}</td>
                                            <td>{{ $msg->subject }}</td>
                                            <td style="max-width:300px">
                                                <span title="{{ $msg->message }}">
                                                    {{ \Str::limit($msg->message, 80) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($msg->is_read)
                                                    <span class="badge bg-success">Lu</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Non lu</span>
                                                @endif
                                            </td>
                                            <td class="d-flex gap-2">
                                                @if(!$msg->is_read)
                                                <form method="POST" action="{{ route('contact.read', $msg->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Marquer comme lu">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                <form method="POST" action="{{ route('contact.destroy', $msg->id) }}"
                                                      onsubmit="return confirm('Supprimer ce message ?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">{{ $messages->links() }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.scripts')
    </body>
</html>
