@php
    $router = app('router');
@endphp
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="{{ $router->currentRouteNamed('admin.backups') ? 'active' : '' }}">
                    <a href="{{ route('admin.backups') }}">Overview</a>
                </li>
                <li class="{{ $router->currentRouteNamed('admin.backups.files') ? 'active' : '' }}">
                    <a href="{{ route('admin.backups.files') }}">File Backups</a>
                </li>
                <li class="{{ $router->currentRouteNamed('admin.backups.database') ? 'active' : '' }}">
                    <a href="{{ route('admin.backups.database') }}">Database Backups</a>
                </li>
            </ul>
        </div>
    </div>
</div>
