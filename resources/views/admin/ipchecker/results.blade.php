@extends('layouts.admin')

@section('title')
    IP Checker
@endsection

@section('content-header')
    <h1>IP Checker Results</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.ipchecker') }}">IP Checker</a></li>
        <li class="active">Results</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Blacklisted IP Addresses</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @if (count($allocations) > 0)
                        <table class="table table-hover">
                            <tr>
                                <th>IP Address</th>
                                <th>IP alias</th>
                                <th>Actions</th>
                            </tr>
                            @foreach($allocations as $allocation)
                                <tr>
                                    <td class="middle"><code>{{ $allocation->ip }}</code></td>
                                    <td class="middle"><code>{{ $allocation->ip_alias }}</code></td>
                                    <td>
                                        <a href="{{ route('admin.nodes.view', $allocation->node_id) }}">
                                            View Node
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <p style="padding: 2rem; text-align: center;">No blacklisted allocations found!</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
