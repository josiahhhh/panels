@extends('layouts.admin')

@section('title')
    Change IP
@endsection

@section('content-header')
    <h1>Change IP
        <small>Change allocation ip with one click.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Change IP</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Change IP</h3>
                </div>
                <form method="post" action="{{ route('admin.ipchange.change') }}">
                    <div class="box-body">
                        <div class="row">
                            <div class="col-xs-12 col-md-8">
                                <div class="form-group">
                                    <label for="node">Node</label>
                                    <select id="node" name="node" class="form-control">
                                        <option selected disabled value="0">- Please Choose -</option>
                                        @foreach ($nodes as $node)
                                            <option value="{{ $node->id }}" {{ old('node', 0) == $node->id ? 'selected' : '' }}>{{ $node->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-xs-12 col-md-4" style="padding-top: 2.5rem;">
                                <button class="btn btn-success btn-block" onclick="addNewLine();" type="button">Add New
                                    Line
                                </button>
                            </div>
                        </div>
                        <div id="rows">
                            <div class="row" id="row-1">
                                <hr>
                                <div class="col-xs-12 col-md-4">
                                    <div class="form-group">
                                        <label for="allocation-1">Allocation</label>
                                        <select id="allocation-1" name="allocation-1" class="form-control allocations" onchange="changeAllocation(1);"></select>
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-4">
                                    <div class="form-group">
                                        <label for="ip-1">New IP</label>
                                        <input type="text" id="ip-1" name="ip-1" class="form-control" placeholder="0.0.0.0">
                                    </div>
                                </div>
                                <div class="col-xs-12 col-md-4">
                                    <div class="form-group">
                                        <label for="alias-1">New Alias</label>
                                        <input type="text" id="alias-1" name="alias-1" class="form-control" placeholder="node01.mydomain.tld">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="hidden" value="1" id="lines" name="lines">
                        <button class="btn btn-success pull-right" type="submit">Change</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Change Every IP</h3>
                </div>
                <form method="post" action="{{ route('admin.ipchange.global') }}">
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-xs-12 col-lg-4">
                                <label for="old_ip">Old IP</label>
                                <input type="text" name="old_ip" id="old_ip" placeholder="1.1.1.1" class="form-control" value="{{ old('old_ip') }}">
                            </div>
                            <div class="form-group col-xs-12 col-lg-4">
                                <label for="new_ip">New IP</label>
                                <input type="text" name="new_ip" id="new_ip" placeholder="2.2.2.2" class="form-control" value="{{ old('new_ip') }}">
                            </div>
                            <div class="form-group col-xs-12 col-lg-4">
                                <label for="new_alias">New Alias</label>
                                <input type="text" name="new_alias" id="new_alias" placeholder="node1.mydomain.tld" class="form-control" value="{{ old('new_alias') }}">
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Change</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent

    <script>
        let lines = [1];
        let allocations = {!! json_encode($allocations) !!};

        $('#node').select2({
            placeholder: '- Please Choose -'
        });

        function select2() {
            $('.allocations').select2({
                placeholder: '- Please Choose -'
            });
        }

        function addNewLine() {
            let next;

            if (lines.length < 1) {
                next = 1;
            } else {
                let last = lines.pop();
                next = last + 1;
                lines.push(last);
            }

            lines.push(next);
            $('#lines').val(lines);

            $('#rows').append(
                '<div class="row" id="row-' + next + '">' +
                '   <hr>' +
                '   <div class="col-xs-12 col-md-3">' +
                '       <div class="form-group">' +
                '           <label for="allocation-' + next + '">Allocation</label>' +
                '           <select id="allocation-' + next + '" name="allocation-' + next + '" class="form-control allocations" onchange="changeAllocation(' + next + ');"></select>' +
                '       </div>' +
                '   </div>' +
                '   <div class="col-xs-12 col-md-3">' +
                '       <div class="form-group">' +
                '           <label for="i-' + next + 'p">New IP</label>' +
                '           <input type="text" id="ip-' + next + '" name="ip-' + next + '" class="form-control" placeholder="0.0.0.0">' +
                '       </div>' +
                '   </div>' +
                '   <div class="col-xs-12 col-md-3">' +
                '       <div class="form-group">' +
                '           <label for="alias-' + next + '">New Alias</label>' +
                '           <input type="text" id="alias-' + next + '" name="alias-' + next + '" class="form-control" placeholder="node01.mydomain.tld">' +
                '       </div>' +
                '   </div>' +
                '   <div class="col-xs-12 col-md-3" style="padding-top: 2.5rem;">' +
                '       <button class="btn btn-danger btn-block" type="button" onclick="removeLine(' + next + ');"><i class="fa fa-trash"></i></button>' +
                '   </div>' +
                '</div>'
            );

            select2();
            changeNode();
        }

        function removeLine(id) {
            $('#row-' + id).slideUp(1000, () => {
                $('#row-' + id).remove();
            });

            for (let i = 0; i < lines.length; i++) {
                if (lines[i] === id) {
                    lines.splice(i, 1);
                }
            }

            $('#lines').val(lines);
        }

        function changeNode() {
            $('.allocations').html('<option selected disabled value="0">- Please Choose -</option>');

            allocations.forEach((item) => {
                if (item.node_id == $('#node').val()) {
                    $('.allocations').append(`<option value="${item.id}">${item.ip}:${item.port} - ${item.ip_alias}</option>`);
                }
            });
        }

        $('#node').on('change', () => {
            changeNode();
        });

        function changeAllocation(id) {
            allocations.forEach((item) => {
                if ($('#allocation-' + id).val() == item.id) {
                    $('#ip-' + id).val(item.ip);
                    $('#alias-' + id).val(item.ip_alias);
                }
            });
        }

        $(document).ready(() => {
            select2();
            changeNode();
        });
    </script>
@endsection
