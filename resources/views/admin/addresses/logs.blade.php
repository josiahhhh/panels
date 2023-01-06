@extends('layouts.admin')

@section('title')
    IP Addresses
@endsection

@section('content-header')
    <h1>IP Address Manager</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">IP Address Manager</li>
    </ol>
@endsection

@section('content')
    @include('admin.addresses.partials.navigation')
    <div class="row">
        <div class="col-xs-12 col-md-8">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">IP Address Logs</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            @if (count($logs) > 0)
                                <table class="table table-hover">
                                    <tr>
                                        <th>IP Address</th>
                                        <th>To</th>
                                        <th>Node</th>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th></th>
                                    </tr>
                                    @foreach($logs as $log)
                                        <tr>
                                            <td class="middle"><code>{{ $log->ip_address }}</code></td>
                                            <td class="middle"><code>
                                                @if(isset($log->node_id))
                                                    {{ $log->to }}
                                                @else
                                                    -
                                                @endif
                                            </code></td>
                                            <td class="">
                                                @if(isset($log->node_id))
                                                    <a href="{{ route('admin.nodes.view', $log->node_id) }}">
                                                        <code>{{ $log->node_id }}</code> {{ $log->node->name }}
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                @datetimeHuman($log->created_at)
                                            </td>
                                            <td>
                                                {{ $log->reason }}
                                            </td>
                                            <td>
                                                @if(!is_null($log->to))
                                                    <a class="btn btn-sm btn-default" href="{{ route('admin.addresses.changeIndex', ['from' => $log->to, 'node' => $log->node_id, 'to' => $log->ip_address]) }}">Undo</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <p style="padding: 2rem; text-align: center;">No IP address logs found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-md-4">
        </div>
    </div>
@endsection
