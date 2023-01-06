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
        <div class="col-sm-8">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Currently Assigned Addresses</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            @if (count($assigned) > 0)
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Alias</th>
                                            <th>Node</th>
                                            <th>FiveM Blacklisted</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody id="ip_addresses">
                                    @foreach($assigned as $allocation)
                                        <tr>
                                            <td class="middle"><code>{{ $allocation->ip }}</code></td>
                                            <td class="middle"><code>{{ $allocation->ip_alias }}</code></td>
                                            <td class="">
                                                @if(isset($allocation->node_id))
                                                    <a href="{{ route('admin.nodes.view', $allocation->node_id) }}">
                                                        {{ $allocation->node_id }} {{ $allocation->node->name }}
                                                    </a>
                                                @else
                                                    Not Assigned
                                                @endif
                                            </td>
                                            <td>
                                                @if(is_null($allocation->fivem_blacklisted))
                                                    -
                                                @elseif ($allocation->fivem_blacklisted == true)
                                                    <p>Yes</p>
                                                @else
                                                    <p>No</p>
                                                @endif
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-default" href="{{ route('admin.addresses.changeIndex', ['from' => $allocation->ip, 'node' => $allocation->node_id]) }}">Change</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p style="padding: 2rem; text-align: center;">No IP addresses are in use by allocations.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">IP List Filters</h3>
                </div>
                <div class="box-body no-padding">
                    <div class="box-body">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="show_fivem_only" name="show_fivem_only" type="checkbox" value="1" checked />
                            <label for="show_fivem_only" class="strong">Only show FiveM</label>
                        </div>
                        <p class="small text-muted no-margin">
                            Filters IP addresses to only show IP addresses used for FiveM allocations (ports starting with 301)/
                        </p>
                    </div>
                    </form>
                </div>
            </div>
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">FiveM Ban Checker</h3>
                </div>
                <div class="box-body no-padding">
                    <form method="post" action="{{ route('admin.ipchecker.checkIPs') }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="port">Port starts with</label>
                                <input type="number" id="port" name="port" class="form-control" placeholder="123">
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <button type="submit" class="btn btn-success pull-right">Check</button>
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
        const assigned = @json($assigned, JSON_PRETTY_PRINT);

        $(document).ready(function() {
            toggleFiveMAddresses(true)

            function toggleFiveMAddresses (show = false) {
                let addresses = assigned;

                if (show){
                    addresses = addresses.filter(a => a.fivem);
                }

                let html = ''
                addresses.forEach(address => {
                    const node = address.node_id ? `
                        <a href="/admin/nodes/view/${address.node_id}">
                            ${address.node_id} ${address.node.name}
                        </a>` : `Not Assigned`

                    const blacklisted = address.fivem_blacklisted == null ?
                        `-` :
                        address.fivem_blacklisted === true ?
                            `<p>Yes</p>` : `<p>No</p>`

                    html += `<tr>
                        <td class="middle"><code>${address.ip}</code></td>
                        <td class="middle"><code>${address.ip_alias}</code></td>
                        <td class="">
                            ${node}
                        </td>
                        <td>
                            ${blacklisted}
                        </td>
                        <td>
                            <a class="btn btn-sm btn-default" href="/admin/addresses/change?from=${address.ip}&node=${address.node_id}">Change</a>
                        </td>
                    </tr>`
                })

                $('#ip_addresses').html(html);

                console.log(addresses);
            }

            $('#show_fivem_only').change(function(){
                toggleFiveMAddresses($(this).is(':checked'))
            });
        });
    </script>

@endsection
