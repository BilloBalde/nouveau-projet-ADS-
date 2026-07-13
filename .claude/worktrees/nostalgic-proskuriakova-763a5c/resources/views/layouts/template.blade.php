<!DOCTYPE html>
<html lang="en">

    @include('layouts.head')

    <body>
        <div id="global-loader">
            <div class="whirly-loader"> </div>
        </div>

        <div class="main-wrapper">
            @include('layouts.navigation')
            <div class="page-wrapper">
                @yield('content')
            </div>
        </div>
        @yield('script_perso')
        @include('layouts.scripts')
    </body>
</html>

