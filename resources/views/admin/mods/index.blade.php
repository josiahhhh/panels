@extends('layouts.admin')

@section('title')
    Manage Mods
@endsection

@section('content-header')
    <h1>Mod Manager<small>You can add and remove server mods.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Mod Manager</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8 col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Mod List</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.mods.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Eggs</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($mods as $mod)
                                <tr>
                                    <td>{{$mod['id']}}</td>
                                    <td>{{$mod['name']}}</td>
                                    <td>{{$mod['description']}}</td>
                                    <td>
                                        @foreach(explode(',', $mod['egg_ids']) as $egg_id)
                                            @foreach($eggs as $egg)
                                                @if ($egg->id == $egg_id)
                                                    {{ $egg->name }}
                                                @endif
                                            @endforeach
                                        @endforeach
                                    <td>
                                        <a title="Edit" class="btn btn-xs btn-primary" href="{{ route('admin.mods.edit', $mod['id']) }}"><i class="fa fa-pencil"></i></a>
                                        <a title="Delete" class="btn btn-xs btn-danger" data-action="delete" data-id="{{ $mod['id'] }}"><i class="fa fa-trash"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Mod Categories</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.mods.categories.new') }}"><button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button></a>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                        </tr>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{$category['id']}}</td>
                                <td>{{$category['name']}}</td>
                                <td>
                                    <a title="Edit" class="btn btn-xs btn-primary" href="{{ route('admin.mods.categories.edit', $category['id']) }}"><i class="fa fa-pencil"></i></a>
                                    <a title="Delete" class="btn btn-xs btn-danger" data-action="delete-category" data-id="{{ $category['id'] }}"><i class="fa fa-trash"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('footer-scripts')
    @parent
    <script>
        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this mod?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/mods/delete',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done((data) => {
                    if (data.success === true) {
                        swal({
                            type: 'success',
                            title: 'Success!',
                            text: 'You have successfully deleted this mod.'
                        });

                        self.parent().parent().slideUp();
                    } else {
                        swal({
                            type: 'error',
                            title: 'Ooops!',
                            text: (typeof data.error !== 'undefined') ? data.error : 'Failed to delete this mod! Please try again later...'
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

        $('[data-action="delete-category"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this category?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/mods/categories/delete',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done((data) => {
                    if (data.success === true) {
                        swal({
                            type: 'success',
                            title: 'Success!',
                            text: 'You have successfully deleted this category.'
                        });

                        self.parent().parent().slideUp();
                    } else {
                        swal({
                            type: 'error',
                            title: 'Ooops!',
                            text: (typeof data.error !== 'undefined') ? data.error : 'Failed to delete this category! Please try again later...'
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
