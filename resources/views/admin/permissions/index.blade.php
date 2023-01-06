@extends('layouts.admin')

@section('title')
    Permission Managment
@endsection

@section('content-header')
    <h1>Permission Managment</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Permission Managment</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="alert alert-info">
        You can assign a role via the <a href="{{route('admin.users')}}">Users</a> page. When a role is assigned the user will be displayed as Administrator, despite this the affected person will only have the permissions of the given role.
        </div>
    </div>
</div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Role List</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.permissions.new') }}" class="btn btn-sm btn-primary">Create New</a>
                    </div>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Color</th>
                            <th>Created</th>
                            <th></th>
                        </tr>
                        @foreach($roles as $role)
                            <tr>
                                <td>{{ $role->id }}</td>
                                <td>{{ $role->name }}</td>
                                <td><input id="color" type="color" name="color" value="{{$role->color}}" disabled></td>
                                <td>{{ $role->created_at}}</td>
                                <td>
                                    <a href="{{ route('admin.permissions.edit', $role->id) }}" title="Edit">
                                        <button class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></button>
                                    </a>
                                    <a href="/admin/permissions/delete/{{ $role->id }}" title="Delete">
                                        <button class="btn btn-sm btn-danger"><i class="fa fa-trash"></i></button>
                                    </a>
                                </td>
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
    </script>
@endsection
