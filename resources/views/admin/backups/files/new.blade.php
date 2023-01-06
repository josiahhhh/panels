@extends('layouts.admin')

@section('title')
    Create File Backup
@endsection

@section('content-header')
    <h1>Create File Backup
        <small>Create a file backup for a server.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.mods') }}">Backup Manager</a></li>
        <li class="active">Create File Backup</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Create File Backup</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.backups') }}">
                            <button type="button" class="btn btn-sm btn-primary"
                                    style="border-radius: 0 3px 3px 0;margin-left:-1px;">Go Back
                            </button>
                        </a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.backups.files.create')  }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name" class="form-label">Backup Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                   placeholder="Before Server Maintenance" />
                        </div>
                        <div class="form-group">
                            <label for="server_id" class="form-label">Server</label>
                            <select id="server_id" name="server_id" class="form-control" multiple>
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}">{{ $server->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        let server_id_select = $('#server_id');

        server_id_select.select2({
            placeholder: 'Select Server',
            multiple: false,
        });

    </script>
@endsection
