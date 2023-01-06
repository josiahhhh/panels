@extends('layouts.admin')

@section('title')
    Database backups
@endsection

@section('content-header')
    <h1>Database Backups<small>Manage database backups for all supported servers.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.backups') }}">Backups</a></li>
        <li class="active">Database Backups</li>
    </ol>
@endsection

@section('content')
    @include('admin.backups.partials.navigation')
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Database Backups</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>ID</th>
                                    <th>Server</th>
                                    <th>Database</th>
                                    <th>Name</th>
                                    <th>Error</th>
                                    <th>Successful</th>
                                    <th>Actions</th>
                                </tr>
                                @foreach ($backups as $backup)
                                    <tr>
                                        <td>{{ $backup->id }}</td>
                                        @foreach ($servers as $server)
                                            @if($server->id == $backup->server_id)
                                                <td>
                                                    <a href="/admin/servers/view/{{ $backup->server_id }}">
                                                        {{ $server->name }}
                                                    </a>
                                                </td>
                                            @endif
                                        @endforeach
                                        <td>{{ $backup->server_id }}</td>
                                        <td>{{ $backup->name }}</td>
                                        <td>{{ $backup->error }}</td>
                                        <td>{{ $backup->is_successful }}</td>
                                        <td>
                                            <a title="Download" class="btn btn-xs btn-primary" href="{{ route('admin.backups.database.download', $backup->id) }}"><i class="fa fa-download"></i></a>
                                            <a title="Delete" class="btn btn-xs btn-danger" data-action="delete-database-backup" data-id="{{ $backup->id }}"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($backups->hasPages())
                            <div class="box-footer with-border">
                                <div class="col-md-12 text-center">{!! $backups->appends(['query' => Request::input('query')])->render() !!}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('[data-action="delete-database-backup"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this database backup?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/backups/database/delete',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done((data) => {
                    if (data.success === true) {
                        swal({
                            type: 'success',
                            title: 'Success!',
                            text: 'You have successfully deleted this database backup.'
                        });

                        self.parent().parent().slideUp();
                    } else {
                        swal({
                            type: 'error',
                            title: 'Ooops!',
                            text: (typeof data.error !== 'undefined') ? data.error : 'Failed to delete this database backup! Please try again later...'
                        });
                    }
                }).fail(() => {
                    swal({
                        type: 'error',
                        title: 'Ooops!',
                        text: 'A system error has occurred! Please try again later...'
                    });
                });
            });
        });
    </script>
@endsection
