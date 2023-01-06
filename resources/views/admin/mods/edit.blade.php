@extends('layouts.admin')

@section('title')
    Edit Mod
@endsection

@section('content-header')
    <h1>Edit Mod
        <small>Edit an existing mod.</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.mods') }}">Mod Manager</a></li>
        <li class="active">Edit Mod</li>
    </ol>
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Edit Mod</h3>
                    <div class="box-tools">
                        <a href="{{ route('admin.mods') }}">
                            <button type="button" class="btn btn-sm btn-primary"
                                    style="border-radius: 0 3px 3px 0;margin-left:-1px;">Go Back
                            </button>
                        </a>
                    </div>
                </div>
                <form method="post" action="{{ route('admin.mods.update', $mod->id)  }}">
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" class="form-control"
                                   placeholder="ZAP Ad Remover" value="{{ $mod->name }}" />
                        </div>
                        <div class="form-group">
                            <label for="egg_ids" class="form-label">Eggs this mod supports</label>
                            <select id="egg_ids" name="egg_ids[]" class="form-control" multiple>
                                @foreach($eggs as $egg)
                                    @if (in_array($egg->id, explode(',', $mod->egg_ids)))
                                        <option value="{{ $egg->id }}" selected>{{ $egg->name }}</option>
                                    @else
                                        <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <input type="text" name="description" id="description" class="form-control"
                                   placeholder="Removes ZAP-Hosting ads from FiveM" value="{{ $mod->description }}" />
                        </div>
                        <div class="form-group">
                            <label for="categories" class="form-label">Mod Categories</label>
                            <select id="categories" name="categories[]" class="form-control" multiple>
                                @foreach($categories as $category)
                                    @if (in_array($category->id, explode(',', $mod->categories)))
                                        <option value="{{ $category->id }}" selected>{{ $category->name }}</option>
                                    @else
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="image" class="form-label">Description</label>
                            <input type="text" name="image" id="image" class="form-control"
                                   placeholder="Image URL for the mod icon." value="{{ $mod->image }}" />
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group col-md-6 col-xs-12">
                                <label for="mod_zip" class="form-label">Mod Files URL</label>
                                <input type="text" name="mod_zip" id="mod_zip" class="form-control" placeholder="https://raw.dropbox.com/icelinehosting/mods/zap-ad-remover.zip" value="{{$mod->mod_zip}}"/>
                                <p class="small text-muted no-margin">Enter a URL pointing to a zip file containing the contents of the mod to be unzipped into server's files, or specify a url to a different file and it will be placed in the installation directory.</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <label for="mod_sql" class="form-label">Mod SQL File</label>
                                <input type="text" name="mod_sql" id="mod_sql" class="form-control" placeholder="https://raw.dropbox.com/icelinehosting/mods/zap-ad-remover.sql" value="{{$mod->mod_sql}}"/>
                                <p class="small text-muted no-margin">Enter a URL pointing to an sql file containing the sql for the mod that should be added to the server database. (WIP does not work yet)</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <label for="install_folder" class="form-label">Install Folder</label>
                                <input type="text" name="install_folder" id="install_folder" class="form-control" value="{{$mod->install_folder}}" />
                                <p class="small text-muted no-margin">The directory where the mod zip will be uncompressed to.</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <input type="checkbox" name="restart_on_install" id="restart_on_install" {{ \Pterodactyl\Helpers\Utilities::checked('restart_on_install', $mod->restart_on_install) }} />
                                <label for="restart_on_install" class="form-label">Restart on Install</label>
                                <p class="small text-muted no-margin">Restarts the server when the mod is installed.</p>
                            </div>
                            <div class="form-group col-md-6 col-xs-12">
                                <input type="checkbox" name="disable_cache" id="disable_cache" {{ \Pterodactyl\Helpers\Utilities::checked('disable_cache', $mod->disable_cache) }} />
                                <label for="disable_cache" class="form-label">Disable Cache</label>
                                <p class="small text-muted no-margin">Disables the panel cache for mod installs.</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="form-group col-md-12 col-xs-12">
                                <label for="uninstall_paths" class="form-label">Uninstall Files or Folders</label>
                                <textarea type="text" name="uninstall_paths" id="uninstall_paths" class="form-control"
                                          placeholder="/plugins/ZapAdRemover1.0" rows="8">{{$mod->uninstall_paths}}</textarea>
                                <p class="small text-muted no-margin">Enter a newline, comma, or semicolon separated list of files or folders that should be deleted from the server when the mod is uninstalled. The paths specified here are relative to the install folder specified above.</p>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        <button class="btn btn-success pull-right" type="submit">Update</button>
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
        let protocol_settings = $('#protocol_settings');

        let eggs = @json($eggs, JSON_PRETTY_PRINT);

        egg_ids_select.select2({
            placeholder: 'Select Eggs',
        });

        let categories_select = $('#categories');
        let categories = @json($categories, JSON_PRETTY_PRINT);
        categories_select.select2({
            placeholder: 'Select Categories',
        });
    </script>
@endsection
