{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    List Nodes
@endsection

@section('scripts')
    @parent
    {!! Theme::css('vendor/fontawesome/animation.min.css') !!}
@endsection

@section('content-header')
    <h1>Nodes<small>All nodes available on the system.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Nodes</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Node List</h3>
                <div class="box-tools search01">
                    <form action="{{ route('admin.nodes') }}" method="GET">
                        <div class="input-group input-group-sm">
                            <input type="text" name="filter[name]" class="form-control pull-right" value="{{ request()->input('filter.name') }}" placeholder="Search Nodes">
                            <div class="input-group-btn">
                                <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                <a href="{{ route('admin.nodes.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th></th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Memory</th>
                            <th>Disk</th>
                            <th class="text-center">Servers</th>
                            <th class="text-center">SSL</th>
                            <th class="text-center">Public</th>
                            <th class="text-center">All Server Restart</th>
                            <th class="text-center">Node shutdown</th>
                            <th class="text-center">Node reboot</th>
                            <th class="text-center">Server reboot</th>
                            <th class="text-center">Server shutdown</th>
                        </tr>
                        @foreach ($nodes as $node)
                            <tr>
                                <td class="text-center text-muted left-icon" data-action="ping" data-secret="{{ $node->getDecryptedKey() }}" data-location="{{ $node->scheme }}://{{ $node->fqdn }}:{{ $node->daemonListen }}/api/system"><i class="fa fa-fw fa-refresh fa-spin"></i></td>
                                <td>{!! $node->maintenance_mode ? '<span class="label label-warning"><i class="fa fa-wrench"></i></span> ' : '' !!}<a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td>
                                <td>{{ $node->location->short }}</td>
                                <td>{{ $node->memory }} MB</td>
                                <td>{{ $node->disk }} MB</td>
                                <td class="text-center">{{ $node->servers_count }}</td>
                                <td class="text-center" style="color:{{ ($node->scheme === 'https') ? '#50af51' : '#d9534f' }}"><i class="fa fa-{{ ($node->scheme === 'https') ? 'lock' : 'unlock' }}"></i></td>
                                <td class="text-center"><i class="fa fa-{{ ($node->public) ? 'eye' : 'eye-slash' }}"></i></td>
                                <td class="text-center"><button data-action="allserverrestart"  data-node="{{ $node->id }}" class="btn btn-sm btn-danger"><i class="fa fa-refresh"></i></button></td>
                                <td class="text-center"><button data-action="shutdown"  data-node="{{ $node->id }}" class="btn btn-sm btn-danger"><i class="fa fa-refresh"></i></button></td>
                                <td class="text-center"><button data-action="reboot"  data-node="{{ $node->id }}" class="btn btn-sm btn-danger"><i class="fa fa-refresh"></i></button></td>
                                <td class="text-center"><button data-action="hardreboot"  data-node="{{ $node->id }}" class="btn btn-sm btn-danger"><i class="fa fa-refresh"></i></button></td>
                                <td class="text-center"><button data-action="hardshutdown"  data-node="{{ $node->id }}" class="btn btn-sm btn-danger"><i class="fa fa-refresh"></i></button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($nodes->hasPages())
                <div class="box-footer with-border">
                    <div class="col-md-12 text-center">{!! $nodes->appends(['query' => Request::input('query')])->render() !!}</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    (function pingNodes() {
        $('td[data-action="ping"]').each(function(i, element) {
            $.ajax({
                type: 'GET',
                url: $(element).data('location'),
                headers: {
                    'Authorization': 'Bearer ' + $(element).data('secret'),
                },
                timeout: 5000
            }).done(function (data) {
                $(element).find('i').tooltip({
                    title: 'v' + data.version,
                });
                $(element).removeClass('text-muted').find('i').removeClass().addClass('fa fa-fw fa-heartbeat faa-pulse animated').css('color', '#50af51');
            }).fail(function (error) {
                var errorText = 'Error connecting to node! Check browser console for details.';
                try {
                    errorText = error.responseJSON.errors[0].detail || errorText;
                } catch (ex) {}

                $(element).removeClass('text-muted').find('i').removeClass().addClass('fa fa-fw fa-heart-o').css('color', '#d9534f');
                $(element).find('i').tooltip({ title: errorText });
            });
        }).promise().done(function () {
            setTimeout(pingNodes, 10000);
        });
    })();

    $('button[data-action="allserverrestart"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var node = $(this).data('node');
        swal({
            title: 'Are you sure to restart all server in this node?',
            text: 'This action will restart all servers in this node.',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Restart',
            confirmButtonColor: '#fc1c03',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: '/admin/nodes/allserverrestart/' + node,
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function (data) {
                swal({ type: 'success', title: 'All server triggered to restart!' });
            }).fail(function (jqXHR) {
                swal({ type: 'warning', title: 'Failed to restart all servers!' });
            });
        });
    });

    $('button[data-action="shutdown"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var node = $(this).data('node');
        swal({
            title: 'Are you sure to shutdown this node?',
            text: 'This action run "service wings stop" command on the server',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Shutdown',
            confirmButtonColor: '#fc1c03',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: '/admin/nodes/shutdown/' + node,
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function (data) {
                element.parent().parent().addClass('warning').delay(100).fadeOut();
                swal({ type: 'success', title: 'Wings shutdown!' });
            }).fail(function (jqXHR) {
                swal({ type: 'warning', title: 'Wings shutdown failed!' });
            });
        });
    });

    $('button[data-action="reboot"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var node = $(this).data('node');
        swal({
            title: 'Are you sure to reboot this node?',
            text: 'This action run "service wings restart" command on the server',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Reboot',
            confirmButtonColor: '#de5e02',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: '/admin/nodes/reboot/' + node,
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function (data) {
                element.parent().parent().addClass('warning').delay(100).fadeOut();
                swal({ type: 'success', title: 'Wings rebooted!' });
            }).fail(function (jqXHR) {
                swal({ type: 'warning', title: 'Wings reboot failed!' });
            });
        });
    });

    $('button[data-action="hardreboot"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var node = $(this).data('node');
        swal({
            title: 'Are you sure to reboot this server?',
            text: 'This action run reboot command on the server',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Reboot',
            confirmButtonColor: '#fc1c03',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: '/admin/nodes/hardreboot/' + node,
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function (data) {
                element.parent().parent().addClass('warning').delay(100).fadeOut();
                swal({ type: 'success', title: 'Server rebooted!' });
            }).fail(function (jqXHR) {
                swal({ type: 'danger', title: 'Server reboot failed!' });
                header("Refresh:3");
            });
        });
    });

    $('button[data-action="hardshutdown"]').click(function (event) {
        event.preventDefault();
        var element = $(this);
        var node = $(this).data('node');
        swal({
            title: 'Are you sure to reboot this server?',
            text: 'This action run reboot command on the server',
            type: 'warning',
            showCancelButton: true,
            allowOutsideClick: true,
            closeOnConfirm: false,
            confirmButtonText: 'Reboot',
            confirmButtonColor: '#fc1c03',
            showLoaderOnConfirm: true
        }, function () {
            $.ajax({
                method: 'POST',
                url: '/admin/nodes/hardshutdown/' + node,
                headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content') },
            }).done(function (data) {
                element.parent().parent().addClass('warning').delay(100).fadeOut();
                swal({ type: 'success', title: 'Server shutdown!' });
            }).fail(function (jqXHR) {
                swal({ type: 'danger', title: 'Server shutdown failed!' });
                header("Refresh:3");
            });
        });
    });
    </script>
@endsection
