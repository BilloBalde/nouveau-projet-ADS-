@if (Session::has('fall'))
<div class="alert alert-danger">
    {{ Session::get('fall') }}
    @php
        Session::forget('fall');
    @endphp
</div>
@elseif (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@elseif (Session::has('warning'))
<div class="alert alert-warning">
    {{ Session::get('warning') }}
    @php
        Session::forget('warning');
    @endphp
</div>
@elseif (Session::has('success'))
<div class="alert alert-success">
    {{ Session::get('success') }}
    @php
        Session::forget('success');
    @endphp
</div>
@endif

