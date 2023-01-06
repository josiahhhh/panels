@extends('layouts.admin')

@section('title')
    Alerts
@endsection

@section('content-header')
    <h1>Alerts<small>Manage alerts.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Alerts</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-8">
            <div class="box box-info">
                <div class="box-header with-border">
                    <h3 class="box-title">Alerts</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Delete When Expired</th>
                                <th>Show</th>
                                <th>Expire</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($alerts as $alert)
                                <tr>
                                    <td>{{ $alert->id }}</td>
                                    <td data-toggle="tooltip" data-placement="top" data-container="body" title="{{ $alert->message }}">
                                        {{ strlen($alert->message) > 30 ? substr($alert->message, 0, 30) . '...' : substr($alert->message, 0, 30) }}
                                    </td>
                                    <td><span class="label label-{{ $alert->type }}">{{ ucfirst($alert->type) }}</span></td>
                                    <td>
                                        <span class="label label-{{ $alert->delete_when_expired == 1 ? 'danger' : 'success' }}">{{ $alert->delete_when_expired == 1 ? 'No' : 'Yes' }}</span>
                                    </td>
                                    <td><code>{{ $alert->created_at }}</code></td>
                                    <td><code>{{ $alert->expire_at }}</code></td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick="edit({{ $alert->id }});">
                                            <i class="fa fa-pencil"></i></button>
                                        <button class="btn btn-danger btn-xs" data-action="delete" data-id="{{ $alert->id }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xs-4">
            <div class="box box-success" id="create-box" {!! old('alert_id', 0) == 0 ? '' : 'style="display: none;"' !!}>
                <div class="box-header with-border">
                    <h3 class="box-title">Create Alert</h3>
                </div>
                <form method="post" action="{{ route('admin.alert.create') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control">{{ old('message') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select id="type" name="type" class="form-control">
                                <option value="error" {{ old('type', '') == 'error' ? 'selected' : '' }}>Error</option>
                                <option value="info" {{ old('type', '') == 'info' ? 'selected' : '' }}>Info</option>
                                <option value="success" {{ old('type', '') == 'success' ? 'selected' : '' }}>Success</option>
                                <option value="warning" {{ old('type', '') == 'warning' ? 'selected' : '' }}>Warning</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="node_ids">Nodes</label>
                            <select id="node_ids" name="node_ids[]" class="form-control node_ids" multiple>
                                @foreach ($nodes as $node)
                                    <option value="{{ $node->id }}" {{ in_array($node->id, old('node_ids', [])) ? 'selected' : '' }}>{{ $node->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="form-group">
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input id="select_all_node" type="checkbox" value="0" />
                                    <label for="select_all_node" class="strong">Select all node</label>
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="form-group">
                            <label for="created_at">Start Showing</label>
                            <input type="datetime-local" id="created_at" name="created_at" class="form-control" value="{{ old('created_at') }}">
                        </div>
                        <div class="form-group">
                            <label for="expire_at">Expire</label>
                            <input type="datetime-local" id="expire_at" name="expire_at" class="form-control" value="{{ old('expire_at') }}">
                        </div>
                        <div class="form-group">
                            <div class="form-group">
                                <div class="checkbox checkbox-primary no-margin-bottom">
                                    <input id="delete_when_expired" name="delete_when_expired" type="checkbox" value="1" onclick="$(this).val(1 + $(this).prop('checked'));">
                                    <label for="delete_when_expired" class="strong">Delete when it's expired</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Create</button>
                    </div>
                </form>
            </div>

            @foreach ($alerts as $alert)
                <div class="box box-primary" id="edit-box-{{ $alert->id }}" {!! old('alert_id', 0) != 0 ? (old('alert_id', 0) == $alert->id ? '' : 'style="display: none;"') : 'style="display: none;"' !!}>
                    <div class="box-header with-border">
                        <h3 class="box-title">Edit Alert</h3>
                    </div>
                    <form method="post" action="{{ route('admin.alert.edit', $alert->id) }}">
                        <div class="box-body">
                            <div class="form-group">
                                <label for="message-{{ $alert->id }}">Message</label>
                                <textarea id="message-{{ $alert->id }}" name="message" class="form-control">{{ old('message', $alert->message) }}</textarea>
                            </div>
                            <div class="form-group">
                                <label for="type-{{ $alert->id }}">Type</label>
                                <select id="type-{{ $alert->id }}" name="type" class="form-control">
                                    <option value="error" {{ old('type', $alert->type) == 'error' ? 'selected' : '' }}>Error</option>
                                    <option value="info" {{ old('type', $alert->type) == 'info' ? 'selected' : '' }}>Info</option>
                                    <option value="success" {{ old('type', $alert->type) == 'success' ? 'selected' : '' }}>Success</option>
                                    <option value="warning" {{ old('type', $alert->type) == 'warning' ? 'selected' : '' }}>Warning</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="node_ids-{{ $alert->id }}">Nodes</label>
                                <select id="node_ids-{{ $alert->id }}" name="node_ids[]" class="form-control node_ids" multiple>
                                    @foreach ($nodes as $node)
                                        <option value="{{ $node->id }}" {{ in_array($node->id, old('node_ids', json_decode($alert->node_ids, true))) ? 'selected' : '' }}>{{ $node->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label for="created_at-{{ $alert->id }}">Start Showing</label>
                                <input type="datetime-local" id="created_at-{{ $alert->id }}" name="created_at" class="form-control" value="{{ old('created_at', date('Y-m-d\TH:i', strtotime($alert->created_at))) }}">
                            </div>
                            <div class="form-group">
                                <label for="expire_at-{{ $alert->id }}">Expire</label>
                                <input type="datetime-local" id="expire_at-{{ $alert->id }}" name="expire_at" class="form-control" value="{{ old('expire_at', date('Y-m-d\TH:i', strtotime($alert->expire_at))) }}">
                            </div>
                            <div class="form-group">
                                <div class="form-group">
                                    <div class="checkbox checkbox-primary no-margin-bottom">
                                        <input id="delete_when_expired-{{ $alert->id }}" name="delete_when_expired" type="checkbox" value="{{ old('delete_when_expired', $alert->delete_when_expired) }}" {{ old('delete_when_expired', $alert->delete_when_expired) == 2 ? 'checked' : '' }} onclick="$(this).val(1 + $(this).prop('checked'));">
                                        <label for="delete_when_expired-{{ $alert->id }}" class="strong">Delete when
                                            it's expired</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            {!! csrf_field() !!}
                            <input type="hidden" name="alert_id" value="{{ $alert->id }}">
                            <button class="btn btn-danger" type="button" onclick="cancelEdit({{ $alert->id }});">
                                Cancel
                            </button>
                            <button class="btn btn-primary pull-right" type="submit">Edit</button>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        $('.node_ids').select2({
            placeholder: '- Select node(s) -',
        });

        $('[data-toggle="tooltip"]').tooltip();

        $('#select_all_node').on('change', function () {
            $('#node_ids option').prop('selected', $('#select_all_node').prop('checked'));

            $('.node_ids').select2({
                placeholder: '- Select node(s) -',
            });
        });

        let lastEditId = 0;

        function edit(id) {
            $('#create-box').slideUp(500);
            $('#edit-box-' + lastEditId).slideUp(500);
            $('#edit-box-' + id).slideDown(500);

            lastEditId = id;
        }

        function cancelEdit(id) {
            $('#edit-box-' + id).slideUp(500);
            $('#create-box').slideDown(500);
        }

        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this alert?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '{{ route('admin.alert.delete') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done(() => {
                    self.parent().parent().slideUp();

                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this alert.'
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
