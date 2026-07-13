@props([
    'name' => 'image',
    'label' => 'Image',
    'existingImage' => null,
])

<div class="col-lg-12 col-sm-12 col-12" 
     x-data="imageUpload('{{ $existingImage }}')">
    
    <div class="form-group">
        <label for="{{ $name }}">{{ $label }}</label>
        
        <div class="image-upload">
            <!-- Hidden File Input -->
            <input type="file" 
                   id="{{ $name }}"
                   name="{{ $name }}"
                   accept="image/*"
                   @change="previewImage"
                   style="display: none;">
            
            <!-- Upload/Preview Area -->
            <div class="upload-area" 
                 @click="document.getElementById('{{ $name }}').click()"
                 :class="{ 'has-preview': imagePreview }">
                
                <!-- Preview -->
                <template x-if="imagePreview">
                    <div class="preview-container">
                        <img :src="imagePreview" alt="Preview" class="preview-image">
                        <button type="button" 
                                class="btn-remove" 
                                @click="removeImage"
                                @click.stop>
                            ×
                        </button>
                    </div>
                </template>
                
                <!-- Upload Prompt -->
                <div class="image-uploads" x-show="!imagePreview">
                    <img src="{{ asset('assets/img/icons/upload.svg') }}" alt="upload">
                    <h4>Click to upload an image</h4>
                </div>
            </div>
        </div>
        
        <!-- Error Display -->
        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>

@push('styles')
<style>
.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    background: #f8f9fa;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-area.has-preview {
    border-style: solid;
    border-color: #28a745;
    padding: 10px;
}

.image-uploads img {
    max-width: 100px;
    margin-bottom: 15px;
}

.preview-container {
    position: relative;
    width: 100%;
    max-width: 300px;
    margin: 0 auto;
}

.preview-image {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 4px;
}

.btn-remove {
    position: absolute;
    top: -10px;
    right: -10px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #dc3545;
    color: white;
    border: none;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-remove:hover {
    background: #c82333;
}
</style>
@endpush

@push('scripts')
<script>
function imageUpload(existingImage = null) {
    return {
        imagePreview: existingImage,
        
        previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        
        removeImage() {
            this.imagePreview = null;
            document.getElementById('{{ $name }}').value = '';
        }
    };
}
</script>
@endpush