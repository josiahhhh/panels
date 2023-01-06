@extends('layouts.admin')

@section('title')
    Create Mod
@endsection

@section('content-header')
    <h1>Create Mod
        <small>You can create a new mod.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.mods') }}">Mod Manager</a></li>
        <li class="active">Create Mod</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Create Mod</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.mods') }}">
                            <button type="button" class="btn btn-sm btn-primary"
                                    style="border-radius: 0 3px 3px 0;margin-left:-1px;">Go Back
                            </button>
                        </a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.mods.create')  }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name" class="form-label">Mod Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                   placeholder="Zap Ad Remover" />
                        </div>
                        <div class="form-group">
                            <label for="egg_ids" class="form-label">Eggs this mod supports</label>
                            <select id="egg_ids" name="egg_ids[]" class="form-control" multiple>
                                @foreach($eggs as $egg)
                                    <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" name="description" id="description" class="form-control"
                                   placeholder="Removes all ZAP-Hosting ads from FiveM." />
                        </div>
                        <div class="form-group">
                            <label for="categories" class="form-label">Mod Categories</label>
                            <select id="categories" name="categories[]" class="form-control" multiple>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="image" class="form-label">Image URL</label>
                            <input type="text" name="image" id="image" class="form-control"
                                   placeholder="URL to an image for the mod logo." />
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group col-md-6 col-xs-12">
                                <label for="mod_zip" class="form-label">Mod Files URL</label>
                                <input type="text" name="mod_zip" id="mod_zip" class="form-control" placeholder="https://raw.dropbox.com/icelinehosting/mods/zap-ad-remover.zip" />
                                <p class="small text-muted no-margin">Enter a URL pointing to a zip file containing the contents of the mod to be unzipped into server's files, or specify a url to a different file and it will be placed in the installation directory.</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <label for="mod_sql" class="form-label">Mod SQL File</label>
                                <input type="text" name="mod_sql" id="mod_sql" class="form-control" placeholder="https://raw.dropbox.com/icelinehosting/mods/zap-ad-remover.sql" />
                                <p class="small text-muted no-margin">Enter a URL pointing to an sql file containing the sql for the mod that should be added to the server database. (WIP does not work yet)</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <label for="install_folder" class="form-label">Install Folder</label>
                                <input type="text" name="install_folder" id="install_folder" class="form-control" value="/" />
                                <p class="small text-muted no-margin">The directory where the mod zip will be uncompressed to.</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <input type="checkbox" name="restart_on_install" id="restart_on_install" value="0" />
                                <label for="restart_on_install" class="form-label">Restart on Install</label>
                                <p class="small text-muted no-margin">Restarts the server when the mod is installed.</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <input type="checkbox" name="disable_cache" id="disable_cache" value="0" />
                                <label for="disable_cache" class="form-label">Disable Cache</label>
                                <p class="small text-muted no-margin">Disabled the panel mod cache for this mod.</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group col-md-12 col-xs-12">
                                <label for="uninstall_paths" class="form-label">Uninstall Files or Folders</label>
                                <textarea type="text" name="uninstall_paths" id="uninstall_paths" class="form-control"
                                          placeholder="/plugins/ZapAdRemover1.0" rows="8"></textarea>
                                <p class="small text-muted no-margin">Enter a newline, comma, or semicolon separated list of files or folders that should be deleted from the server when the mod is uninstalled. The paths specified here are relative to the install folder specified above.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
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
        let egg_ids_select = $('#egg_ids');

        let eggs = @json($eggs, JSON_PRETTY_PRINT);

        egg_ids_select.select2({
            placeholder: 'Select Eggs',
        });

        egg_ids_select.on('change', function() {
           let egg_ids = egg_ids_select.val();
        });

        let categories_select = $('#categories');
        let categories = @json($categories, JSON_PRETTY_PRINT);
        categories_select.select2({
            placeholder: 'Select Categories',
        });

    </script>
@endsection
