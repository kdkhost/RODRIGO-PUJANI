@php($isEdit = $record->exists)
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Nome</label><input type="text" name="name" class="form-control" value="{{ old('name', $record->name) }}"></div>
        <div class="col-md-6"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="{{ old('slug', $record->slug) }}"></div>
        <div class="col-md-6"><label class="form-label">Cargo</label><input type="text" name="role" class="form-control" value="{{ old('role', $record->role) }}"></div>
        <div class="col-md-6"><label class="form-label">OAB</label><input type="text" name="oab_number" class="form-control" value="{{ old('oab_number', $record->oab_number) }}"></div>
        <div class="col-md-4"><label class="form-label">E-mail</label><input type="email" name="email" class="form-control" value="{{ old('email', $record->email) }}"></div>
        <div class="col-md-4"><label class="form-label">Telefone</label><input type="text" name="phone" class="form-control" data-mask="phone" value="{{ old('phone', $record->phone) }}"></div>
        <div class="col-md-4"><label class="form-label">WhatsApp</label><input type="text" name="whatsapp" class="form-control" data-mask="phone" value="{{ old('whatsapp', $record->whatsapp) }}"></div>
        <div class="col-md-6"><label class="form-label">LinkedIn</label><input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $record->linkedin_url) }}"></div>
        <div class="col-md-6"><label class="form-label">Instagram</label><input type="url" name="instagram_url" class="form-control" value="{{ old('instagram_url', $record->instagram_url) }}"></div>
        <div class="col-md-6"><label class="form-label">Especialidades</label><input type="text" name="specialties_text" class="form-control" value="{{ old('specialties_text', collect($record->specialties)->implode(', ')) }}" placeholder="Civil, Empresarial"></div>
        <div class="col-md-3"><label class="form-label">Ordem</label><input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $record->sort_order ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Foto</label><input type="file" name="image" class="form-control" data-filepond></div>
        <div class="col-12"><label class="form-label">Bio</label><textarea name="bio" class="form-control" data-editor="summernote">{{ old('bio', $record->bio) }}</textarea></div>
        <div class="col-12 d-flex gap-4"><div class="form-check"><input type="checkbox" class="form-check-input" name="is_partner" id="is_partner" value="1" @checked(old('is_partner', $record->is_partner))><label class="form-check-label" for="is_partner">Sócio</label></div><div class="form-check"><input type="checkbox" class="form-check-input" name="is_active" id="is_member_active" value="1" @checked(old('is_active', $record->is_active ?? true))><label class="form-check-label" for="is_member_active">Ativo</label></div></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
