@extends('layouts.admin')

@section('title')
    Manage Backups
@endsection

@section('content-header')
    <h1>System Backups Retrieval<small>View and download system backups.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">System Backup</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-xs-12">
                    <div class="box box-primary">
                        <div class="box-header with-border">
                            <h3 class="box-title">System Backups</h3>
                        </div>
                        <div class="box-body table-responsive no-padding">
                            <table class="table table-hover">
                                <tbody>
                                <tr>
                                    <th>Node</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Server</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                                @foreach ($backups as $backup)
                                    <tr>
                                        <td>
                                            <a href="/admin/nodes/view/{{ $backup->node }}">
                                                {{ $backup->node }}
                                            </a>
                                        </td>
                                        <td>{{ $backup->date }}</td>
                                        <td>{{ $backup->time }}</td>
                                        <td style="display: flex;flex-direction: column;">
                                            @foreach ($servers as $server)
                                                @if($server->uuid == $backup->server)
                                                    <a href="/admin/servers/view/{{ $server->id }}">
                                                        {{ $server->name }} {{ $server->id }}
                                                    </a>
                                                @endif
                                            @endforeach
                                            <p style="opacity: 0.5">{{ $backup->server }}</p>
                                        </td>
                                        <td>{{ number_format($backup->size/1024/1024, 2, '.', ',') }}MB</td>
                                        <td>
                                            <a title="Download" class="btn btn-xs btn-primary" href="{{ route('admin.sysbackups.download') }}?file_path={{ urlencode($backup->file_path) }}"><i class="fa fa-download"></i></a>
                                            <button title="Copy" class="btn btn-xs btn-primary" onclick="copyDownloadLink('{{ $backup->file_path }}')"><i class="fa fa-clipboard"></i></button>
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
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        function copyDownloadLink (filePath) {
            $.ajax({
                url: '{{ route('admin.sysbackups.link') }}?file_path=' + filePath,
                dataType: 'json',
            }).then(function (data) {
                navigator.clipboard.writeText(data.link).then(() => {
                    alert("Copied the download link to clipboard!");
                }).catch(() => {
                    alert("There was an error copying the link to the clipboard.");
                });
            });
        }
    </script>
@endsection

