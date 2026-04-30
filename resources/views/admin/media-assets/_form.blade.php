@php
    $isEdit = $record->exists;
    $currentUrl = $record->path ? site_asset_url($record->path) : null;
    $currentMime = (string) ($record->mime_type ?? '');
    $isImage = str_starts_with($currentMime, 'image/');
    $isVideo = str_starts_with($currentMime, 'video/');
    $isAudio = str_starts_with($currentMime, 'audio/');
@endphp
<form action="{{ $isEdit ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" method="POST" data-ajax-form enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Diretório</label><input type="text" name="directory" class="form-control" value="{{ old('directory', $record->directory) }}"></div>
        <div class="col-md-6">
            <label class="form-label">Arquivo</label>
            <input
                type="file"
                name="file"
                class="form-control"
                data-filepond
                data-current-url="{{ $currentUrl ?: '' }}"
                data-current-name="{{ $record->original_name ?: $record->file_name }}"
                data-current-type="{{ $record->mime_type }}"
                data-current-size="{{ $record->size }}"
            >
            @if($currentUrl)
                <div class="admin-brand-preview-card mt-3">
                    <span>Pré-visualização atual</span>
                    <div class="admin-brand-preview-image mt-2" style="min-height: 180px;">
                        @if($isImage)
                            <img src="{{ $currentUrl }}" alt="{{ $record->original_name ?: $record->file_name }}" style="max-height: 160px; border-radius: 0.8rem;">
                        @elseif($isVideo)
                            <video src="{{ $currentUrl }}" controls style="max-width: 100%; max-height: 160px; border-radius: 0.8rem;"></video>
                        @elseif($isAudio)
                            <audio src="{{ $currentUrl }}" controls style="width: 100%;"></audio>
                        @else
                            <div class="text-center">
                                <i class="bi bi-file-earmark fs-1 d-block mb-2"></i>
                                <strong>{{ $record->original_name ?: $record->file_name }}</strong>
                            </div>
                        @endif
                    </div>
                    <div class="mt-2 small text-muted">Arquivo atual: <a href="{{ $currentUrl }}" target="_blank" rel="noopener">{{ $record->path }}</a></div>
                </div>
            @endif
        </div>
        <div class="col-md-6"><label class="form-label">Texto alternativo</label><input type="text" name="alt_text" class="form-control" value="{{ old('alt_text', $record->alt_text) }}"></div>
        <div class="col-md-6 form-check mt-5"><input type="checkbox" class="form-check-input" id="media_public" name="is_public" value="1" @checked(old('is_public', $record->is_public ?? true))><label class="form-check-label" for="media_public">Pública</label></div>
        <div class="col-12"><label class="form-label">Legenda</label><textarea name="caption" class="form-control" rows="3" data-editor="summernote" data-editor-height="220">{{ old('caption', $record->caption) }}</textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form>
