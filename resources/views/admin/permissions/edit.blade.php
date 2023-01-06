@extends('layouts.admin')

@section('title')
    Permission Managment
@endsection

@section('content-header')
    <h1>Edit Role</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Permission Managment</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <form method="POST" action="{{ route('admin.permissions.edit', $role->id) }}">
            <div class="col-sm-8 col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Select Permissions</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <tr>
                                <td class="col-sm-3 strong">Panel Settings</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r1" name="settings" value="1" @if($settings == 1) checked @endif>
                                    <label for="r1">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw1" name="settings" value="2" @if($settings == 2) checked @endif>
                                    <label for="rw1">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n1" name="settings" value="0" @if($settings == 0) checked @endif>
                                    <label for="n1">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Application API</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r2" name="api" value="1" @if($api == 1) checked @endif>
                                    <label for="r2">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw2" name="api" value="2" @if($api == 2) checked @endif>
                                    <label for="rw2">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n2" name="api" value="0" @if($api == 0) checked @endif>
                                    <label for="n2">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Permission Managment</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r3" name="permissions" value="1" @if($permissions == 1) checked @endif>
                                    <label for="r3">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw3" name="permissions" value="2" @if($permissions == 2) checked @endif>
                                    <label for="rw3">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n3" name="permissions" value="0" @if($permissions == 0) checked @endif>
                                    <label for="n3">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Databases</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r4" name="databases" value="1" @if($databases == 1) checked @endif>
                                    <label for="r4">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw4" name="databases" value="2" @if($databases == 2) checked @endif>
                                    <label for="rw4">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n4" name="databases" value="0" @if($databases == 0) checked @endif>
                                    <label for="n4">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Locations</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r5" name="locations" value="1" @if($locations == 1) checked @endif>
                                    <label for="r5">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw5" name="locations" value="2" @if($locations == 2) checked @endif>
                                    <label for="rw5">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n5" name="locations" value="0" @if($locations == 0) checked @endif>
                                    <label for="n5">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Nodes</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r6" name="nodes" value="1" @if($nodes == 1) checked @endif>
                                    <label for="r6">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw6" name="nodes" value="2" @if($nodes == 2) checked @endif>
                                    <label for="rw6">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n6" name="nodes" value="0" @if($nodes == 0) checked @endif>
                                    <label for="n6">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Servers</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r7" name="servers" value="1" @if($servers == 1) checked @endif>
                                    <label for="r7">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw7" name="servers" value="2" @if($servers == 2) checked @endif>
                                    <label for="rw7">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n7" name="servers" value="0" @if($servers == 0) checked @endif>
                                    <label for="n7">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Users</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r8" name="users" value="1" @if($users == 1) checked @endif>
                                    <label for="r8">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw8" name="users" value="2" @if($users == 2) checked @endif>
                                    <label for="rw8">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n8" name="users" value="0" @if($users == 0) checked @endif>
                                    <label for="n8">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Mounts</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r9" name="mounts" value="1" @if($mounts == 1) checked @endif>
                                    <label for="r9">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw9" name="mounts" value="2" @if($mounts == 2) checked @endif>
                                    <label for="rw9">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n9" name="mounts" value="0" @if($mounts == 0) checked @endif>
                                    <label for="n9">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Nests</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r10" name="nests" value="1" @if($nests == 1) checked @endif>
                                    <label for="r10">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw10" name="nests" value="2" @if($nests == 2) checked @endif>
                                    <label for="rw10">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n10" name="nests" value="0" @if($nests == 0) checked @endif>
                                    <label for="n10">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">IP Address Manager</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r11" name="addresses" value="1" @if($addresses == 1) checked @endif>
                                    <label for="r11">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw11" name="addresses" value="2" @if($addresses == 2) checked @endif>
                                    <label for="rw11">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n11" name="addresses" value="0" @if($addresses == 0) checked @endif>
                                    <label for="n11">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Auto allocation Adder</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r12" name="auto_allocation_adder" value="1" @if($auto_allocation_adder == 1) checked @endif>
                                    <label for="r12">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw12" name="auto_allocation_adder" value="2" @if($auto_allocation_adder == 2) checked @endif>
                                    <label for="rw12">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n12" name="auto_allocation_adder" value="0" @if($auto_allocation_adder == 0) checked @endif>
                                    <label for="n12">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Subdomain Manager</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r13" name="subdomains" value="1" @if($subdomains == 1) checked @endif>
                                    <label for="r13">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw13" name="subdomains" value="2" @if($subdomains == 2) checked @endif>
                                    <label for="rw13">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n13" name="subdomains" value="0" @if($subdomains == 0) checked @endif>
                                    <label for="n13">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Mod Manager</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r14" name="mods" value="1" @if($mods == 1) checked @endif>
                                    <label for="r14">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw14" name="mods" value="2" @if($mods == 2) checked @endif>
                                    <label for="rw14">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n14" name="mods" value="0" @if($mods == 0) checked @endif>
                                    <label for="n14">None</label>
                                </td>
                            </tr>
                            <tr>
                                <td class="col-sm-3 strong">Backup Manager</td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="r15" name="backups" value="1" @if($backups == 1) checked @endif>
                                    <label for="r15">Read</label>
                                </td>
                                <td class="col-sm-3 radio radio-primary text-center">
                                    <input type="radio" id="rw15" name="backups" value="2" @if($backups == 2) checked @endif>
                                    <label for="rw15">Read &amp; Write</label>
                                </td>
                                <td class="col-sm-3 radio text-center">
                                    <input type="radio" id="n15" name="backups" value="0" @if($backups == 0) checked @endif>
                                    <label for="n15">None</label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-sm-4 col-xs-12">
                <div class="box box-primary">
                    <div class="box-body">
                        <div class="form-group">
                            <label class="control-label" for="name">Name <span class="field-required"></span></label>
                            <input id="name" type="text" name="name" class="form-control" value="{{ $role->name }}" required>
                        </div>
                        <div class="form-group">
                            <input id="color" type="color" name="color" value="{{ $role->color }}">
                            <label class="control-label" for="color">Role Color</label>
                        </div>
                    </div>
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-success btn-sm pull-right">Edit Role</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    </script>
@endsection
