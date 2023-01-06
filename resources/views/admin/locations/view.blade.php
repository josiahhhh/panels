{{-- Pterodactyl - Panel --}}
{{-- Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com> --}}

{{-- This software is licensed under the terms of the MIT license. --}}
{{-- https://opensource.org/licenses/MIT --}}
@extends('layouts.admin')

@section('title')
    Locations &rarr; View &rarr; {{ $location->short }}
@endsection

@section('content-header')
    <h1>{{ $location->short }}<small>{{ str_limit($location->long, 75) }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.locations') }}">Locations</a></li>
        <li class="active">{{ $location->short }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Location Details</h3>
            </div>
            <form action="{{ route('admin.locations.view', $location->id) }}" method="POST">
                <div class="box-body">
                    <div class="form-group">
                        <label for="pShort" class="form-label">Short Code</label>
                        <input type="text" id="pShort" name="short" class="form-control" value="{{ $location->short }}" />
                    </div>
                    <div class="form-group">
                        <label for="pLong" class="form-label">Description</label>
                        <textarea id="pLong" name="long" class="form-control" rows="4">{{ $location->long }}</textarea>
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input id="pUserTransferable" name="user_transferable" value="1" type="checkbox" @if($location->user_transferable) checked @endif />
                            <label for="pUserTransferable" class="strong">User Transferable</label>
                        </div>
                        <p class="text-muted small">
                            If disabled, this location will not ever appear as a possible server transfer location for users.
                        </p>
                    </div>
                    <div class="form-group">
                        <label for="transfer_allowed_egg_ids" class="form-label">Allows Eggs for User Transfers</label>
                        <select id="transfer_allowed_egg_ids" name="transfer_allowed_egg_ids[]" class="form-control" multiple>
                            @foreach($eggs as $egg)
                                @if (in_array($egg->id, explode(',', $location->transfer_allowed_egg_ids)))
                                    <option value="{{ $egg->id }}" selected>{{ $egg->name }}</option>
                                @else
                                    <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        <p class="text-muted small">
                            If specified, this location is only shown as an available server transfer location for servers with the specified game. No eggs specified means that any egg can be transferred to this location.
                        </p>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    {!! method_field('PATCH') !!}
                    <button name="action" value="edit" class="btn btn-sm btn-primary pull-right">Save</button>
                    <button name="action" value="delete" class="btn btn-sm btn-danger pull-left muted muted-hover"><i class="fa fa-trash-o"></i></button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-sm-6">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Nodes</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>FQDN</th>
                        <th>Servers</th>
                    </tr>
                    @foreach($location->nodes as $node)
                        <tr>
                            <td><code>{{ $node->id }}</code></td>
                            <td><a href="{{ route('admin.nodes.view', $node->id) }}">{{ $node->name }}</a></td>
                            <td><code>{{ $node->fqdn }}</code></td>
                            <td>{{ $node->servers->count() }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        let egg_ids_select = $('#transfer_allowed_egg_ids');

        let eggs = @json($eggs, JSON_PRETTY_PRINT);

        egg_ids_select.select2({
            placeholder: 'Select Eggs',
        });
    </script>
@endsection
