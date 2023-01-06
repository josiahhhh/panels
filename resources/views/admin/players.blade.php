@extends('layouts.admin')

@section('title')
    Popular Servers
@endsection

@section('content-header')
    <h1>Popular Servers<small> See the top popular servers.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Popular Servers</li>
    </ol>
@endsection

@section('content')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Top Servers</h3>
        </div>
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Server Name</th>
                        <th>Server Owner</th>
                        <th>Players</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servers as $server)
                        <tr>
                            <td>{{ $server->id }}</td>
                            <td><a href="{{ route('admin.servers.view', $server->id) }}" target="_blank">{{ $server->name }}</a></td>
                            <td><a href="{{ route('admin.users.view', $server->owner_id) }}">{{ $server->firstname }} {{ $server->lastname }}</a></td>
                            <td><span class="label label-{{ $players[$server->id]['max'] < 1 ? 'danger' : 'success' }}">{{ $players[$server->id]['online'] }}/{{ $players[$server->id]['max'] }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <th colspan="3">There are no online servers.</th>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($servers->hasPages())
            <div class="box-footer with-border">
                <div class="text-center col-md-12">
                    {!! $servers->render() !!}
                </div>
            </div>
        @endif
    </div>
@endsection
