@extends('layouts.admin')

@section('title')
    IP Checker
@endsection

@section('content-header')
    <h1>IP Checker<small>Test blocked ips.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">IP Checker</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">IP Table Checker</h3>
                </div>
                <form method="post" action="{{ route('admin.ipchecker.checkIPs') }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="port">Port starts with</label>
                            <input type="number" id="port" name="port" class="form-control" placeholder="123">
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button type="submit" class="btn btn-success pull-right">Check</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
