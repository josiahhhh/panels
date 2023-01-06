@php
    /** @var \Pterodactyl\Models\Server $server */
    $router = app('router');
@endphp
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="{{ $router->currentRouteNamed('admin.addresses') ? 'active' : '' }}">
                    <a href="{{ route('admin.addresses') }}">Active</a>
                </li>
                <li class="{{ $router->currentRouteNamed('admin.addresses.changeIndex') ? 'active' : '' }}">
                    <a href="{{ route('admin.addresses.changeIndex') }}">Change</a>
                </li>
                <li class="{{ $router->currentRouteNamed('admin.addresses.reserve') ? 'active' : '' }}">
                    <a href="{{ route('admin.addresses.reserve') }}">Reserve</a>
                </li>
                <li class="{{ $router->currentRouteNamed('admin.addresses.logs') ? 'active' : '' }}">
                    <a href="{{ route('admin.addresses.logs') }}">Logs</a>
                </li>
            </ul>
        </div>
    </div>
</div>
