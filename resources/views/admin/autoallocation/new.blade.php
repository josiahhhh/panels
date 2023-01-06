@extends('layouts.admin')

@section('title')
    Create Allocation
@endsection

@section('content-header')
    <h1>Create Allocation
        <small>You can create allocation.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.autoallocation') }}">Auto Allocation Adder</a></li>
        <li class="active">Create Allocation</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Create Allocation</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.autoallocation') }}">
                            <button type="button" class="btn btn-sm btn-primary"
                                    style="border-radius: 0 3px 3px 0;margin-left:-1px;">Go Back
                            </button>
                        </a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.autoallocation.create')  }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="egg_ids" class="form-label">Servers (when it's available)</label>
                            <select class="form-control" id="egg_id" name="egg_id">
                                @foreach($eggs as $egg)
                                    <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <hr>
                        <button type="button" class="btn btn-success" onclick="addNewLine();"><i class="fa fa-plus-square"></i> Add New Line</button>
                        <hr>
                        <div id="lines">
                            <div class="row" id="line-1">
                                <div class="col-12 col-md-2">
                                    <div class="form-group">
                                        <label for="allocation_type1">Type</label>
                                        <select id="allocation_type1" name="allocation_type1" class="form-control" onchange="changeType(1);">
                                            <option disabled selected>- Please Choose -</option>
                                            <option value="+">Plus</option>
                                            <option value="-">Minus</option>
                                            <option value="next">Next</option>
                                            <option value="random">Random In Range</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-5">
                                    <div class="form-group">
                                        <label for="allocation_port1">Port (Plus, Minus, Random)</label>
                                        <input type="text" placeholder="123" id="allocation_port1" name="allocation_port1" class="form-control" disabled>
                                        <p class="small text-muted no-margin">Random: you have to write a range for exmaple: 8000-8100</p>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <div class="form-group">
                                        <label for="environment_name1">Environment Name</label>
                                        <input type="text" placeholder="PORT" id="environment_name1" name="environment_name1" class="form-control" disabled>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <button class="btn btn-danger btn-block" style="margin-top: 2.4rem;" type="button" onclick="removeLine(1);">
                                        <i class="fa fa-trash"></i> Remove Line
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <input type="hidden" id="allocation_selectors" name="allocation_selectors" value="1">
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
        $('#egg_id').select2({
            placeholder: 'Select Eggs',
        });
        let lines = [1];
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
            $('#allocation_selectors').val(lines);
            $('#lines').append(
                '<div class="row" id="line-' + next + '">\n' +
                '                                <div class="col-12 col-md-2">\n' +
                '                                    <div class="form-group">\n' +
                '                                        <label for="allocation_type' + next + '">Type</label>\n' +
                '                                        <select id="allocation_type' + next + '" name="allocation_type' + next + '" class="form-control" onchange="changeType(' + next + ');">\n' +
                '                                            <option disabled selected>- Please Choose -</option>\n' +
                '                                            <option value="+">Plus</option>\n' +
                '                                            <option value="-">Minus</option>\n' +
                '                                            <option value="next">Next</option>\n' +
                '                                            <option value="random">Random In Range</option>\n' +
                '                                        </select>\n' +
                '                                    </div>\n' +
                '                                </div>\n' +
                '                                <div class="col-12 col-md-5">\n' +
                '                                    <div class="form-group">\n' +
                '                                        <label for="allocation_port' + next + '">Port (Plus, Minus)</label>\n' +
                '                                        <input type="text" placeholder="123" id="allocation_port' + next + '" name="allocation_port' + next + '" class="form-control" disabled>\n' +
                '                                    </div>\n' +
                '                                </div>\n' +
                '                                <div class="col-12 col-md-3">\n' +
                '                                    <div class="form-group">\n' +
                '                                        <label for="environment_name' + next + '">Environment Name</label>\n' +
                '                                        <input type="text" placeholder="PORT" id="environment_name' + next + '" name="environment_name' + next + '" class="form-control" disabled>\n' +
                '                                    </div>\n' +
                '                                </div>\n' +
                '                                <div class="col-12 col-md-2">\n' +
                '                                    <button class="btn btn-danger btn-block" style="margin-top: 2.4rem;" type="button" onclick="removeLine(' + next + ');"><i class="fa fa-trash"></i> Remove Line</button>\n' +
                '                                </div>\n' +
                '                            </div>'
            );
        }
        function removeLine(id) {
            $('#line-' + id).slideUp(1000);
            for (let i = 0; i < lines.length; i++) {
                if (lines[i] === id) {
                    lines.splice(i, 1);
                }
            }
            $('#allocation_selectors').val(lines);
        }
        function changeType(id) {
            let allocation_type = $('#allocation_type' + id);
            let allocation_port = $('#allocation_port' + id);
            let environment_name = $('#environment_name' + id);

            if (allocation_type.val() === '+' || allocation_type.val() === '-' || allocation_type.val() === 'random') {
                allocation_port.removeAttr('disabled');
                environment_name.removeAttr('disabled');
            } else {
                allocation_port.attr('disabled', '');
                environment_name.attr('disabled', '');
            }
        }
    </script>
@endsection
