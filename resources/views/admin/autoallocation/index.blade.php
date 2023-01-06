@extends('layouts.admin')

@section('title')
    List Allocations
@endsection

@section('content-header')
    <h1>Auto Allocation Adder<small>Add allocations when servers will be created</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Manage Allocations</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Allocation List</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.autoallocation.new') }}">
                            <button type="button" class="btn btn-sm btn-primary" style="border-radius: 0 3px 3px 0;margin-left:-1px;">Create New</button>
                        </a>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>#</th>
                                <th>Egg</th>
                                <th>Actions</th>
                            </tr>
                            @foreach ($locations as $location)
                                <tr>
                                    <td>{{$location->id}}</td>
                                    <td><label class="label label-primary">{{ $location->eggName }}</label></td>
                                    <td>
                                        <button class="btn btn-warning btn-xs" data-action="apply" data-id="{{ $location->id }}"><i class="fa fa-plus"></i></button>
                                        <a class="btn btn-xs btn-primary" href="{{ route('admin.autoallocation.edit', $location->id) }}"><i class="fa fa-pencil"></i></a>
                                        <a class="btn btn-xs btn-danger" data-action="delete" data-id="{{ $location->id }}"><i class="fa fa-trash"></i></a>
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
        $('[data-action="apply"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want apply this allocation rule to all existing server in this egg?',
                showCancelButton: true,
                confirmButtonText: 'Apply',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'POST',
                    url: '{{ route('admin.autoallocation.apply') }}',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id'),
                    },
                    timeout: 600000,
                }).done((data) => {
                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully applied this allocation rule for all servers.'
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

        $('[data-action="delete"]').click(function (event) {
            event.preventDefault();
            let self = $(this);
            swal({
                title: '',
                type: 'warning',
                text: 'Are you sure you want to delete this allocation?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d9534f',
                closeOnConfirm: false,
                showLoaderOnConfirm: true,
                cancelButtonText: 'Cancel',
            }, function () {
                $.ajax({
                    method: 'DELETE',
                    url: '/admin/autoallocation/delete',
                    headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')},
                    data: {
                        id: self.data('id')
                    }
                }).done((data) => {
                    swal({
                        type: 'success',
                        title: 'Success!',
                        text: 'You have successfully deleted this allocatiom.'
                    });
                    self.parent().parent().slideUp();
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
