@extends('layouts.admin')

@section('title')
    Change IP
@endsection

@section('content-header')
    <h1>Change IP Address
        <small>Change allocation ip with one click.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.addresses') }}">IP Address Manager</a></li>
        <li class="active">Change IP</li>
    </ol>
@endsection

@section('content')
    @include('admin.addresses.partials.navigation')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Change Allocation IP Addresses</h3>
                </div>
                <form method="post" action="{{ route('admin.addresses.change.global') }}">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-xs-12">
                                <label for="node_id">Node</label>
                                <select id="node_id" name="node_id" class="form-control">
                                    <option selected disabled value="0">- Please Choose -</option>
                                    @foreach ($nodes as $n)
                                        <option value="{{ $n->id }}" {{ old('node_id', $node ? $node->id : '') == $n->id ? 'selected' : '' }}>
                                            {{ $n->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-md-6">
                                <label for="old_ip">Old IP</label>
                                <select id="old_ip" name="old_ip" class="form-control">
                                    <option selected disabled value="0">- Please Choose -</option>
                                    @foreach ($assigned as $allocation)
                                        <option value="{{ $allocation->ip }}" {{ old('old_ip', $from) == $allocation->ip ? 'selected' : '' }} {{ $node && $allocation->node_id != $node->id ? 'disabled' : '' }}>
                                            {{ $allocation->ip }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-xs-12 col-md-6">
                                <label for="new_ip">New IP</label>
                                <select id="new_ip" name="new_ip" class="form-control">
                                    <option selected disabled value="0">- Please Choose -</option>
                                    @if(!is_null($to))
                                        <option value="{{ $to }}" {{ old('new_ip', $to) == $to ? 'selected' : '' }}>
                                            {{ $to }} (manual)
                                        </option>
                                    @endif
                                    @foreach ($reserved as $address)
                                        <option value="{{ $address->ip_address }}" {{ old('new_ip', $to) == $address->ip_address ? 'selected' : '' }} {{ $node && $address->node_id != $node->id ? 'disabled' : '' }}>
                                            {{ $address->ip_address }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="small text-muted no-margin">
                                    To enter a custom IP, type out the IP in the search field and press enter.
                                </p>
                            </div>
                            <div class="form-group col-xs-12 col-md-6"></div>
                            <div class="form-group col-xs-12 col-md-6">
                                <label for="alias">Allocation Alias</label>
                                <input type="text" id="alias" name="alias" class="form-control" placeholder="54.346.54.41" value="{{ old('alias', $alias) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-12 col-md-6">
                                <div>
                                    <input type="checkbox" name="add_to_reserve" id="add_to_reserve" />
                                    <label for="add_to_reserve" class="control-label">Add old IP to reserve</label>
                                </div>
                                <p class="text-muted">
                                    <small>
                                        Adds the old IP to the reserve after removing it from allocations. If unchecked, all references to this IP address are removed from the panel. This MUST be checked for an "undo" if you want the IP to be added back to the reservation pool.
                                    </small>
                                </p>
                            </div>
                            <div class="form-group col-xs-12 col-md-6">
                                <div>
                                    <input type="checkbox" name="remove_from_reserve" id="remove_from_reserve" checked />
                                    <label for="remove_from_reserve" class="control-label">Remove new IP from reserve</label>
                                </div>
                                <p class="text-muted">
                                    <small>
                                        Removes the new IP from the address reserve. If unchecked, the address will stay in the IP address reserve as available to use for additional IP address changes.
                                    </small>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Change</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent

    <script>
{{--        let allocations = {!! json_encode($allocations) !!};--}}
        let assigned = @json($assigned, JSON_PRETTY_PRINT);
        let reserved = @json($reserved, JSON_PRETTY_PRINT);

        let node = @json($node, JSON_PRETTY_PRINT);

        $(document).ready(() => {

            $('#node_id').select2({
                placeholder: '- Please Choose -'
            });

            $('#node_id').on('change', function () {
                const oldIP = $('#old_ip');
                oldIP.html('').select2({
                    placeholder: '- Please Choose -'
                })
                assigned.filter(a => a.node_id == $('#node_id').val()).forEach(a =>
                    oldIP.append(new Option(a.ip, a.ip, false, false)))

                const newIP = $('#new_ip');
                newIP.html('').select2({
                    placeholder: '- Please Choose -',
                    // Allow manual IP entries that are
                    // not in the pre-defined list.
                    tags: true
                });
                const ips = reserved.filter(a => a.node_id == $('#node_id').val());
                ips.forEach(a => newIP.append(new Option(a.ip_address, a.ip_address, false, false)));
                $('#alias').val(ips[0].alias);
            })

            $('#old_ip').select2({
                placeholder: '- Please Choose -'
            });


            $('#new_ip').select2({
                placeholder: '- Please Choose -',
                // Allow manual IP entries that are
                // not in the pre-defined list.
                tags: true,
            });

            $('#new_ip').on('change', function(e) {
                const ip = reserved.find(ip => ip.ip_address == e.target.value)
                if (ip) {
                    $('#alias').val(ip.alias);
                }
            });
        });
    </script>
@endsection
