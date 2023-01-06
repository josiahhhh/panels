@extends('layouts.admin')

@section('title')
    Backup settings
@endsection

@section('content-header')
    <h1>Backup Settings</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.backups') }}">Backups</a></li>
        <li class="active">Backup settings</li>
    </ol>
@endsection

@section('content')
    @include('admin.backups.partials.navigation')
    <div class="row">
        <div class="col-md-8 col-xs-12">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Server Settings List</h3>
                            <div class="box-tools">
                                <a href="{{ route('admin.backups.settings.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Add Server Specific Settings</button></a>
                            </div>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>Server</th>
                                    <th>File Backups</th>
                                    <th>Backup Limit</th>
                                    <th>Actions</th>
                                </tr>
                                @foreach ($servers as $server)
                                    <tr>
                                        <td>{{$server['server']['name'] ?? $server['server_id']}}</td>
                                        <td>{{ $server['file_backups_enabled'] ? 'Enabled' : 'Disabled' }}</td>
                                        <td>{{ $server['file_backups_total_limit_by_size'] ? $server['file_backups_total_limit_amount'] . ' Bytes' : $server['file_backups_total_limit_amount'] . ' Backups' }}</td>
                                        <td>
                                            <a title="Edit" class="btn btn-xs btn-primary" href="{{ route('admin.backups.settings.edit', $server['id']) }}"><i class="fa fa-pencil"></i></a>
                                            <a title="Delete" class="btn btn-xs btn-danger" data-action="delete-server-settings" data-id="{{ $server['id'] }}"><i class="fa fa-trash"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xs-12">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Backup Upload Settings</h3>
                        </div>
                        <form method="post" action="{{ route('admin.backups.settings.update') }}">
                            <div class="box-body">
                                <div class="form-group">
                                    <label for="s3_endpoint" class="form-label">S3 Endpoint</label>
                                    <input type="text" id="s3_endpoint" name="s3_endpoint" class="form-control" value="{{ $settings['upload']['s3_endpoint'] }}" />
                                </div>
                                <div class="form-group">
                                    <label for="s3_region" class="form-label">S3 Region</label>
                                    <input type="text" id="s3_region" name="s3_region" class="form-control" value="{{ $settings['upload']['s3_region'] }}" />
                                </div>
                                <div class="form-group">
                                    <label for="s3_access_key_id" class="form-label">S3 Access Key ID</label>
                                    <input type="text" id="s3_access_key_id" name="s3_access_key_id" class="form-control" value="{{ $settings['upload']['s3_access_key_id'] }}" />
                                </div>
                                <div class="form-group">
                                    <label for="s3_secret_access_key" class="form-label">S3 Access Key Secret</label>
                                    <input type="text" id="s3_secret_access_key" name="s3_secret_access_key" class="form-control" value="{{ $settings['upload']['s3_secret_access_key'] }}" />
                                </div>
                            </div>
                            <div class="box-footer">
                                {!! csrf_field() !!}
                                <button class="btn btn-success pull-right" type="submit">Update Upload Settings</button>
                            </div>
                        </form>
                    </div>
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">Default Server Settings</h3>
                        </div>
                        <form method="post" action="{{ route('admin.backups.settings.updateServer', $defaults->id)  }}">
                            <div class="box-body">
                                <div class="checkbox checkbox-primary">
                                    <input id="file_backups_enabled" name="file_backups_enabled" type="checkbox" {{ \Pterodactyl\Helpers\Utilities::checked('file_backups_enabled', $defaults->file_backups_enabled) }} />
                                    <label for="file_backups_enabled" class="strong">File backups enabled</label>
                                </div>
                                <div class="checkbox checkbox-primary">
                                    <input id="file_backups_total_limit_by_size" name="file_backups_total_limit_by_size" type="checkbox" {{ \Pterodactyl\Helpers\Utilities::checked('file_backups_total_limit_by_size', $defaults->file_backups_total_limit_by_size) }} />
                                    <label for="file_backups_total_limit_by_size" class="strong">Limit backups by overall storage</label>
                                    <p class="small text-muted no-margin">Limits a user's file backups by their total size instead of a backup count.</p>
                                </div>
                                <div class="form-group">
                                    <label for="file_backups_total_limit_amount" class="form-label">Total Backup Limit</label>
                                    <input type="number" name="file_backups_total_limit_amount" id="file_backups_total_limit_amount" class="form-control"
                                           value="{{ $defaults->file_backups_total_limit_amount }}" />
                                    <p class="small text-muted no-margin">If the previous setting is checked, this is a size in bytes. If it's unchecked, this is the number of allowed backups.</p>
                                </div>
                            </div>
                            <div class="box-footer">
                                {!! csrf_field() !!}
                                <button class="btn btn-success pull-right" type="submit">Update Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
