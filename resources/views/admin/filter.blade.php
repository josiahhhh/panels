@extends('layouts.admin')

@section('title')
    Filters
@endsection

@section('content-header')
    <h1>Filters<small> Manage filters per egg.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Filters</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Filters</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Filter</th>
                                <th>Egg(s)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($filters as $filter)
                                <tr>
                                    <td>{{ $filter->id }}</td>
                                    <td><code>{{ $filter->filter }}</code></td>
                                    <td><span class="label label-info" data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $filter->eggs }}">Show</span></td>
                                    <td>
                                        <button class="btn btn-warning btn-xs" data-action="apply" data-id="{{ $filter->id }}"><i class="fa fa-plus"></i></button>
                                        <a href="{{ route('admin.filters.edit', $filter->id) }}" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>
                                        <button class="btn btn-danger btn-xs" data-action="delete" data-id="{{ $filter->id }}"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <th colspan="4">There are no filter added.</th>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Added Filters</h3>
                    <div class="box-tools search01">
                        <form action="{{ route('admin.filters') }}" method="GET" id="queryForm">
                            <div class="input-group input-group-sm">
                                <select class="form-control pull-right" name="queryFilter" id="queryFilter" onchange="$('#queryForm').submit();">
                                    <option value="0">- All -</option>
                                    @foreach ($types as $type)
                                        <option value="{{ $type['name'] }}" {{ old('queryFilter', request()->input('queryFilter')) == $type['name'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-btn">
                                    <button type="submit" class="btn btn-default"><i class="fa fa-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Server</th>
                                <th>Filter Type</th>
                                <th>Filter ID</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($servers as $server)
                                <tr>
                                    <td><a href="{{ route('admin.servers.view', $server['id']) }}" target="_blank">{{ $server['name'] }}</a></td>
                                    <td>{{ $server['filter']['type'] }}</td>
                                    <td><code>{{ $server['filter']['id'] }}</code></td>
                                    <td><span class="label label-info">{{ $server['ip'] }}:{{ $server['port'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <th colspan="4">No filter added.</th>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="box box-{{ isset($editFilter) ? 'warning' : 'success' }}">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ isset($editFilter) ? 'Edit Filter' : 'Add Filter' }}</h3>
                </div>
                <form method="post" action="{{ isset($editFilter) ? route('admin.filters.update', $editFilter->id) : route('admin.filters.store') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="filter">Filter</label>
                            <select id="filter" name="filter" class="form-control">
                                @foreach ($types as $filter)
                                    <option value="{{ $filter['name'] }}" {{ old('filter', @$editFilter->filter) == $filter['name'] ? 'selected' : '' }}>{{ $filter['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="egg_ids">Egg(s)</label>
                            <select id="egg_ids" name="egg_ids[]" class="form-control" multiple>
                                @foreach($eggs as $egg)
                                    <option value="{{ $egg->id }}" {{ in_array($egg->id, old('egg_ids', explode(',', @$editFilter->egg_ids))) ? 'selected' : '' }}>{{ $egg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        @if (isset($editFilter))
                            <a href="{{ route('admin.filters') }}" class="btn btn-default">Back</a>
                        @endif
                        <button type="submit" class="btn btn-success pull-right">{{ isset($editFilter) ? 'Edit' : 'Create' }}</button>
                    </div>
                </form>
            </div>
            <div class="box box-danger">
                <div class="box-header">
                    <h3 class="box-title">Settings</h3>
                </div>
                <form method="post" action="{{ route('admin.filters.save') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="excluded_node_ids">Excluded Node(s)</label>
                            <select id="excluded_node_ids" name="excluded_node_ids[]" multiple class="form-control">
                                @foreach ($nodes as $node)
                                    <option value="{{ $node->id }}" {{ in_array($node->id, old('excluded_node_ids', explode(',', $excludedNodeIds))) ? 'selected' : '' }}>{{ $node->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        @csrf
                        <button type="submit" class="btn btn-success pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent

    <script>
        $('#filter').select2({
            placeholder: '- Select Filter -',
        });

        $('#egg_ids').select2({
            placeholder: '- Select Egg(s) -',
        });

        $('#queryFilter').select2({
            placeholder: '- Select Filter -',
        });

        $('#excluded_node_ids').select2({
            placeholder: '- Select Node(s) -',
        });

        $('[data-action="apply"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want apply this flag to all existing server in this egg?',
                showCancelButton: true,
                confirmButtonText: 'Apply',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'POST',
                    url: '{{ route('admin.filters.push') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id'),
                    },
                    timeout: 600000,
                }).done((data) => {
                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully applied this flag for all servers.'
                    });
                }).fail((jqXHR) => {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof jqXHR.responseText !== 'undefined') ? jqXHR.responseText : 'A system error has occurred! Please try again later...'
                    });
                });
            });
        });

        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this filter?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.filters.delete') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id'),
                    }
                }).done((data) => {
                    self.parent().parent().slideUp();

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this filter.'
                    });
                }).fail((jqXHR) => {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: (typeof jqXHR.responseJSON.error !== 'undefined') ? jqXHR.responseJSON.error : 'A system error has occurred! Please try again later...'
                    });
                });
            });
        });
    </script>
@endsection
