@extends('layouts.admin')

@section('title')
    Manage Backups
@endsection

@section('content-header')
    <h1>Backups Manager<small>You can manage and delete backups.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Backup Manager</li>
    </ol>
@endsection

@section('content')
    @include('admin.backups.partials.navigation')
    <div class="row">
        <div class="col-md-12 col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Backups Overview</h3>
                </div>
                <div class="box-body table-responsive no-padding">

                </div>
            </div>
        </div>
    </div>
@endsection

