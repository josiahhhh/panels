@extends('layouts.admin')

@section('title')
    IP Addresses
@endsection

@section('content-header')
    <h1>IP Address Manager</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.addresses') }}">IP Address Manager</a></li>
        <li class="active">Reserve Addresses</li>
    </ol>
@endsection

@section('content')
    @include('admin.addresses.partials.navigation')
    <div class="row">
        <div class="col-sm-8">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">IP Address Reserve</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            @if (count($addresses) > 0)
                                <table class="table table-hover">
                                    <tr>
                                        <th>IP Address</th>
                                        <th>Alias</th>
                                        <th>Node</th>
                                        <th>FiveM Blacklisted</th>
                                        <th></th>
                                    </tr>
                                    @foreach($addresses as $address)
                                        <tr>
                                            <td class="middle"><code>{{ $address->ip_address }}</code></td>
                                            <td class="middle"><code>{{ $address->alias }}</code></td>
                                            <td class="">
                                                @if(isset($address->node_id))
                                                    <a href="{{ route('admin.nodes.view', $address->node_id) }}">
                                                        {{ $address->node_id }} {{ $address->node->name }}
                                                    </a>
                                                @else
                                                    Not Assigned
                                                @endif
                                            </td>
                                            <td>
                                                @if ($address->fivem_blacklisted == true)
                                                    <p>Yes</p>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-default" href="{{ route('admin.addresses.changeIndex', ['to' => $address->ip_address, 'node' => $address->node_id, 'alias' => $address->alias]) }}">Use</a>
                                                <a class="btn btn-sm btn-default" href="{{ route('admin.addresses.reserve.remove', ['ip_address' => $address->ip_address]) }}">Remove</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            @else
                                <p style="padding: 2rem; text-align: center;">No IP addresses in reserve.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Add New Address</h3>
                </div>
                <div class="box-body no-padding">
                    <form method="post" action="{{ route('admin.addresses.reserve.add') }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="port">IP Address</label>
                                <input type="text" id="ip_address" name="ip_address" class="form-control" placeholder="192.168.0.1">
                            </div>
                            <div class="form-group">
                                <label for="alias">Alias</label>
                                <input type="text" id="alias" name="alias" class="form-control" placeholder="426.654.32.65">
                            </div>
                            <div class="form-group">
                                <label for="node_id">Node</label>
                                <select name="node_id" id="node_id" class="form-control">
                                    @foreach($locations as $location)
                                        <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                            @foreach($location->nodes as $node)

                                                <option value="{{ $node->id }}"
                                                        @if($location->id === old('location_id')) selected @endif
                                                >{{ $node->name }}</option>

                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="small text-muted no-margin">The node which this server will be deployed to.</p>
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-success pull-right">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script type="application/javascript">
        $(document).ready(function() {
            // Persist 'Node' select2
            @if (old('node_id'))
                $('#node_id').val('{{ old('node_id') }}').change();
            @endif
            // END Persist 'Node' select2
        });
    </script>

@endsection
