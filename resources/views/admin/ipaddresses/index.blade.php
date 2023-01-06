@extends('layouts.admin')

@section('title')
    IP Addresses
@endsection

@section('content-header')
    <h1>IP Addresses</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">IP Addresses</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">All IP Addresses</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    @if (count($addresses) > 0)
                        <table class="table table-hover">
                            <tr>
                                <th>IP Address</th>
                                <th>Node</th>
                                <th>FiveM Blacklisted</th>
                            </tr>
                            @foreach($addresses as $ip)
                                <tr>
                                    <td class="middle"><code>{{ $ip->ip_address }}</code></td>
                                    <td>
                                        @if(isset($ip->node_id))
                                            <a href="{{ route('admin.nodes.view', $ip->node_id) }}">
                                                {{ $ip->node->name }} ({{ $ip->node_id }})
                                            </a>
                                        @else
                                            Not Assigned
                                        @endif
                                    </td>
                                    <td class="middle"><code>{{ $ip->fivem_blacklisted }}</code></td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <p style="padding: 2rem; text-align: center;">No ip addresses have been scanned or added!</p>
                    @endif
                </div>
                @if($addresses->hasPages())
                    <div class="box-footer with-border">
                        <div class="col-md-12 text-center">
                            {!! $addresses->appends(['query' => Request::input('query')])->render() !!}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
