/**
 * AURA Atelier - x02.me Client-side WebP Compressor & Direct API Uploader
 * Compresses images to WebP format, optimizes file size, and uploads to https://x02.me/
 */

(function(window) {
    'use strict';

    /**
     * Format bytes to human-readable string (e.g. 1.2 MB, 340 KB)
     */
    function formatBytes(bytes, decimals = 1) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const dm = decimals < 0 ? 0 : decimals;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }

    /**
     * Compress and convert any image file to WebP format using HTML5 Canvas
     * @param {File} file - Original image file
     * @param {Object} options - Compression options
     * @returns {Promise<Object>} Compressed WebP file & metadata
     */
    async function compressAndConvertToWebP(file, options = {}) {
        const maxDimension = options.maxDimension || 1920;
        const quality = options.quality !== undefined ? options.quality : 0.82;

        return new Promise((resolve, reject) => {
            if (!file || !file.type.startsWith('image/')) {
                reject(new Error('Selected file is not an image'));
                return;
            }

            const reader = new FileReader();
            reader.onerror = () => reject(new Error('Failed to read image file'));
            reader.onload = (e) => {
                const img = new Image();
                img.onerror = () => reject(new Error('Failed to decode image data'));
                img.onload = () => {
                    let width = img.naturalWidth || img.width;
                    let height = img.naturalHeight || img.height;

                    // Calculate proportional aspect ratio
                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d', { alpha: true });

                    // High-quality image smoothing
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    ctx.drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (!blob) {
                            reject(new Error('Browser failed to encode image to WebP'));
                            return;
                        }

                        // Clean file name with .webp extension
                        const rawName = (file.name || 'image').replace(/\.[^/.]+$/, '');
                        const cleanName = rawName.replace(/[^a-zA-Z0-9_-]/g, '_') + '.webp';
                        const webpFile = new File([blob], cleanName, { type: 'image/webp' });

                        const originalSize = file.size;
                        const compressedSize = blob.size;
                        const savingsPercent = originalSize > 0 
                            ? Math.max(0, Math.round((1 - (compressedSize / originalSize)) * 100)) 
                            : 0;

                        resolve({
                            file: webpFile,
                            blob: blob,
                            name: cleanName,
                            originalSize: originalSize,
                            compressedSize: compressedSize,
                            savingsPercent: savingsPercent,
                            width: width,
                            height: height,
                            previewUrl: URL.createObjectURL(blob)
                        });
                    }, 'image/webp', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    /**
     * Upload a WebP file to https://up.x02.me/api/upload via server API proxy or direct ShareX-compatible API
     * @param {File} webpFile - The compressed WebP File
     * @param {Function} onProgress - Optional progress callback
     * @returns {Promise<Object>} Upload response with x02.me file URL
     */
    async function uploadToX02(webpFile, onProgress) {
        const apiKey = window.X02_API_KEY || '36f36ce6fa844e93bda76bb9255070b4';

        // 1. Try local server-side handler first (handles server-to-server curl with correct headers)
        try {
            const formData = new FormData();
            formData.append('file', webpFile, webpFile.name || 'image.webp');
            formData.append('expiry', '');
            formData.append('x02_api_key', apiKey);

            const res = await fetch('/admin/api_upload_x02.php', {
                method: 'POST',
                body: formData
            });

            if (res.ok) {
                const data = await res.json();
                if (data.success && data.url) {
                    return data;
                }
                if (data.error) {
                    console.warn('API proxy returned error, trying direct up.x02.me upload:', data.error);
                }
            }
        } catch (err) {
            console.warn('Server proxy failed, trying direct up.x02.me upload:', err);
        }

        // 2. Direct upload to https://up.x02.me/api/upload?format=json using JSON API method
        try {
            const directForm = new FormData();
            directForm.append('file', webpFile, webpFile.name || 'image.webp');
            directForm.append('expiry', '');

            const directRes = await fetch('https://up.x02.me/api/upload?format=json', {
                method: 'POST',
                headers: {
                    'x-api-key': apiKey
                },
                body: directForm
            });

            const directJson = await directRes.json();
            const parsedUrl = directJson.url ||
                (directJson.file && (typeof directJson.file === 'string' ? directJson.file : directJson.file.url)) ||
                directJson.direct_url ||
                directJson.link ||
                (directJson.data && directJson.data.url) ||
                (directJson.files && directJson.files[0] && (typeof directJson.files[0] === 'string' ? directJson.files[0] : directJson.files[0].url));

            if (parsedUrl) {
                return {
                    success: true,
                    url: parsedUrl,
                    format: 'webp',
                    compressed_size: webpFile.size
                };
            }

            if (directJson.error) {
                throw new Error(directJson.error + (directJson.message ? ': ' + directJson.message : ''));
            }

            throw new Error('x02 upload response did not contain a valid URL');
        } catch (directErr) {
            throw new Error('Upload to x02 failed: ' + directErr.message);
        }
    }

    /**
     * Initializes a drag-and-drop WebP + x02.me uploader zone for a single image field
     */
    function initX02SingleUploader(config) {
        const {
            containerId,
            inputName,
            initialUrl = '',
            label = 'Main Product Cover Photo',
            onChange = null
        } = config;

        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = `
            <div class="x02-uploader-wrapper" style="border:1px dashed var(--border-color); border-radius:10px; padding:18px; background:var(--bg-card); transition:all 0.2s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                    <label style="font-weight:700; font-size:13.5px; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                        <span>📷</span> ${label} <span style="color:#ef4444;">*</span>
                    </label>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <span class="badge-tag" style="background:rgba(212,175,55,0.12); color:var(--accent-gold); font-size:11px; font-weight:700; padding:3px 8px; border-radius:4px; border:none;">
                            ⚡ WebP Compressed
                        </span>
                        <span class="badge-tag" style="background:rgba(59,130,246,0.12); color:#60a5fa; font-size:11px; font-weight:700; padding:3px 8px; border-radius:4px; border:none;">
                            🌐 x02.me API
                        </span>
                    </div>
                </div>

                <!-- Hidden / Real Form Input -->
                <input type="hidden" name="${inputName}" id="${containerId}_realInput" value="${initialUrl}">

                <!-- Upload Dropzone -->
                <div id="${containerId}_dropzone" style="cursor:pointer; border:2px dashed var(--border-color); border-radius:8px; padding:22px 16px; text-align:center; background:var(--bg-subtle); transition:all 0.2s ease; position:relative; ${initialUrl ? 'display:none;' : 'display:block;'}">
                    <input type="file" id="${containerId}_fileInput" accept="image/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                    <div id="${containerId}_idleContent">
                        <div style="font-size:32px; margin-bottom:8px;">📤</div>
                        <div style="font-weight:700; font-size:14px; color:var(--text-primary); margin-bottom:4px;">
                            Drag & drop luxury product photo or <span style="color:var(--accent-gold); text-decoration:underline;">Browse</span>
                        </div>
                        <div style="font-size:12px; color:var(--text-secondary);">
                            Automatically converts to <strong>.webp</strong>, reduces file size, and uploads to <strong>https://x02.me/</strong>
                        </div>
                    </div>

                    <!-- Progress / Uploading State -->
                    <div id="${containerId}_uploadingContent" style="display:none; padding:10px 0;">
                        <div class="spinner" style="width:28px; height:28px; border:3px solid rgba(212,175,55,0.2); border-top-color:var(--accent-gold); border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto 10px;"></div>
                        <div id="${containerId}_progressStatus" style="font-weight:700; font-size:13px; color:var(--text-primary); margin-bottom:4px;">Compressing & Converting to WebP...</div>
                        <div id="${containerId}_progressDetail" style="font-size:11.5px; color:var(--text-secondary);">Reducing image weight while retaining luxury clarity...</div>
                    </div>
                </div>

                <!-- Preview Area when Image is Selected / Uploaded -->
                <div id="${containerId}_previewArea" style="${initialUrl ? 'display:flex;' : 'display:none;'} gap:14px; align-items:center; background:var(--bg-subtle); padding:12px 14px; border-radius:8px; border:1px solid var(--border-color); margin-top:8px;">
                    <div style="position:relative; width:80px; height:80px; flex-shrink:0; border-radius:6px; overflow:hidden; border:1px solid var(--border-color); background:#000;">
                        <img id="${containerId}_previewImg" src="${initialUrl}" alt="Preview" style="width:100%; height:100%; object-fit:cover;">
                    </div>

                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px; flex-wrap:wrap;">
                            <span style="font-weight:700; font-size:13px; color:var(--text-primary); word-break:break-all;" id="${containerId}_fileName">Uploaded Photo</span>
                            <span id="${containerId}_statBadge" class="badge-tag" style="background:#10b98120; color:#10b981; font-size:11px; font-weight:700; padding:2px 6px; border-radius:4px;">✓ Hosted on x02.me</span>
                        </div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <a id="${containerId}_urlLink" href="${initialUrl}" target="_blank" style="color:var(--accent-gold); text-decoration:none; font-family:monospace; font-size:11.5px;">${initialUrl}</a>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <button type="button" class="btn btn-sm btn-outline" id="${containerId}_changeBtn" style="font-size:11.5px; padding:4px 10px;">
                                🔄 Replace Photo
                            </button>
                            <button type="button" class="btn btn-sm btn-outline" id="${containerId}_copyBtn" style="font-size:11.5px; padding:4px 10px;">
                                📋 Copy Link
                            </button>
                            <button type="button" class="btn btn-sm" id="${containerId}_removeBtn" style="font-size:11.5px; padding:4px 10px; color:#ef4444; background:transparent; border:none; cursor:pointer;">
                                🗑️ Remove
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Manual URL Toggle -->
                <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center; font-size:12px;">
                    <a href="javascript:void(0)" id="${containerId}_toggleManualUrl" style="color:var(--text-secondary); text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                        <span>🔗</span> Or enter/edit URL manually
                    </a>
                </div>
                <div id="${containerId}_manualUrlWrapper" style="display:none; margin-top:8px;">
                    <input type="url" id="${containerId}_manualUrlInput" class="form-control" placeholder="https://x02.me/i/... or https://..." value="${initialUrl}" style="font-size:12.5px;">
                </div>
            </div>
        `;

        const dropzone = document.getElementById(`${containerId}_dropzone`);
        const fileInput = document.getElementById(`${containerId}_fileInput`);
        const realInput = document.getElementById(`${containerId}_realInput`);
        const idleContent = document.getElementById(`${containerId}_idleContent`);
        const uploadingContent = document.getElementById(`${containerId}_uploadingContent`);
        const progressStatus = document.getElementById(`${containerId}_progressStatus`);
        const progressDetail = document.getElementById(`${containerId}_progressDetail`);
        const previewArea = document.getElementById(`${containerId}_previewArea`);
        const previewImg = document.getElementById(`${containerId}_previewImg`);
        const fileNameEl = document.getElementById(`${containerId}_fileName`);
        const statBadge = document.getElementById(`${containerId}_statBadge`);
        const urlLink = document.getElementById(`${containerId}_urlLink`);
        const changeBtn = document.getElementById(`${containerId}_changeBtn`);
        const copyBtn = document.getElementById(`${containerId}_copyBtn`);
        const removeBtn = document.getElementById(`${containerId}_removeBtn`);
        const toggleManualUrl = document.getElementById(`${containerId}_toggleManualUrl`);
        const manualUrlWrapper = document.getElementById(`${containerId}_manualUrlWrapper`);
        const manualUrlInput = document.getElementById(`${containerId}_manualUrlInput`);

        // Drag & drop styling
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.borderColor = 'var(--accent-gold)';
                dropzone.style.background = 'rgba(212,175,55,0.06)';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.borderColor = 'var(--border-color)';
                dropzone.style.background = 'var(--bg-subtle)';
            }, false);
        });

        // Handle file selection
        async function handleSelectedFile(file) {
            if (!file) return;

            idleContent.style.display = 'none';
            uploadingContent.style.display = 'block';
            progressStatus.textContent = 'Compressing & Converting to WebP...';
            progressDetail.textContent = `Processing original (${formatBytes(file.size)})...`;

            try {
                // Step 1: Compress & Convert to WebP via Canvas
                const compressed = await compressAndConvertToWebP(file, {
                    maxDimension: 1920,
                    quality: 0.82
                });

                progressStatus.textContent = 'Uploading to https://x02.me/ via API...';
                progressDetail.textContent = `WebP Size: ${formatBytes(compressed.compressedSize)} (${compressed.savingsPercent}% reduced) ➔ Uploading...`;

                // Step 2: Upload WebP to x02.me
                const uploadRes = await uploadToX02(compressed.file);

                const finalUrl = uploadRes.url;

                // Step 3: Populate Form & Preview
                realInput.value = finalUrl;
                manualUrlInput.value = finalUrl;
                previewImg.src = finalUrl;
                fileNameEl.textContent = compressed.name;
                urlLink.textContent = finalUrl;
                urlLink.href = finalUrl;
                statBadge.textContent = `✓ WebP (${formatBytes(compressed.compressedSize)}, -${compressed.savingsPercent}%) ➔ x02.me`;

                dropzone.style.display = 'none';
                previewArea.style.display = 'flex';

                if (typeof onChange === 'function') {
                    onChange(finalUrl);
                }
            } catch (err) {
                alert('Upload Error: ' + err.message);
                console.error(err);
            } finally {
                idleContent.style.display = 'block';
                uploadingContent.style.display = 'none';
                fileInput.value = '';
            }
        }

        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files[0]) {
                handleSelectedFile(e.target.files[0]);
            }
        });

        changeBtn.addEventListener('click', () => {
            fileInput.click();
        });

        copyBtn.addEventListener('click', () => {
            if (realInput.value) {
                navigator.clipboard.writeText(realInput.value);
                copyBtn.textContent = '✓ Copied!';
                setTimeout(() => { copyBtn.textContent = '📋 Copy Link'; }, 2000);
            }
        });

        removeBtn.addEventListener('click', () => {
            realInput.value = '';
            manualUrlInput.value = '';
            previewArea.style.display = 'none';
            dropzone.style.display = 'block';
            if (typeof onChange === 'function') {
                onChange('');
            }
        });

        toggleManualUrl.addEventListener('click', () => {
            const isShown = manualUrlWrapper.style.display === 'block';
            manualUrlWrapper.style.display = isShown ? 'none' : 'block';
        });

        manualUrlInput.addEventListener('input', (e) => {
            const val = e.target.value.trim();
            realInput.value = val;
            if (val) {
                previewImg.src = val;
                urlLink.textContent = val;
                urlLink.href = val;
                fileNameEl.textContent = 'External Image URL';
                statBadge.textContent = 'Custom URL';
                previewArea.style.display = 'flex';
                dropzone.style.display = 'none';
            }
            if (typeof onChange === 'function') {
                onChange(val);
            }
        });

        return {
            setUrl: function(url) {
                realInput.value = url || '';
                manualUrlInput.value = url || '';
                if (url) {
                    previewImg.src = url;
                    urlLink.textContent = url;
                    urlLink.href = url;
                    previewArea.style.display = 'flex';
                    dropzone.style.display = 'none';
                } else {
                    previewArea.style.display = 'none';
                    dropzone.style.display = 'block';
                }
            },
            getUrl: function() {
                return realInput.value;
            }
        };
    }

    /**
     * Initializes multi-image gallery uploader for additional product photos
     */
    function initX02GalleryUploader(config) {
        const {
            containerId,
            textareaId,
            initialUrls = []
        } = config;

        const container = document.getElementById(containerId);
        const textarea = document.getElementById(textareaId);
        if (!container || !textarea) return;

        let galleryUrls = [...initialUrls];

        function renderGallery() {
            textarea.value = galleryUrls.join(', ');

            const grid = document.getElementById(`${containerId}_grid`);
            if (!grid) return;

            if (galleryUrls.length === 0) {
                grid.innerHTML = '<div style="font-size:12px; color:var(--text-muted); padding:6px 0;">No additional gallery images added yet.</div>';
                return;
            }

            grid.innerHTML = galleryUrls.map((url, idx) => `
                <div style="position:relative; width:80px; height:80px; border-radius:6px; overflow:hidden; border:1px solid var(--border-color); background:#000;">
                    <img src="${url}" alt="Gallery item" style="width:100%; height:100%; object-fit:cover;">
                    <button type="button" onclick="window.removeX02GalleryImage('${containerId}', ${idx})" title="Remove photo" style="position:absolute; top:3px; right:3px; background:rgba(0,0,0,0.7); color:#ef4444; border:none; border-radius:50%; width:20px; height:20px; font-size:11px; display:flex; align-items:center; justify-content:center; cursor:pointer; line-height:1;">✕</button>
                </div>
            `).join('');
        }

        container.innerHTML = `
            <div style="border:1px dashed var(--border-color); border-radius:10px; padding:16px; background:var(--bg-card);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                    <label style="font-weight:700; font-size:13.5px; color:var(--text-primary); display:flex; align-items:center; gap:8px;">
                        <span>🖼️</span> Additional Gallery Images (x02.me WebP Upload)
                    </label>
                    <span class="badge-tag" style="background:rgba(212,175,55,0.12); color:var(--accent-gold); font-size:11px; font-weight:700; padding:2px 8px; border-radius:4px;">
                        Multi-Upload
                    </span>
                </div>

                <!-- Dropzone -->
                <div id="${containerId}_dropzone" style="position:relative; border:2px dashed var(--border-color); border-radius:8px; padding:18px 12px; text-align:center; background:var(--bg-subtle); cursor:pointer; margin-bottom:12px;">
                    <input type="file" id="${containerId}_multiInput" multiple accept="image/*" style="position:absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;">
                    <div id="${containerId}_idle">
                        <span style="font-size:24px; display:block; margin-bottom:4px;">➕</span>
                        <div style="font-weight:700; font-size:13px; color:var(--text-primary);">Click or drag multiple photos to compress & upload to x02.me</div>
                        <div style="font-size:11.5px; color:var(--text-secondary); margin-top:2px;">Select 1 or more images at once</div>
                    </div>
                    <div id="${containerId}_loading" style="display:none; padding:6px 0;">
                        <div class="spinner" style="width:22px; height:22px; border:2px solid rgba(212,175,55,0.2); border-top-color:var(--accent-gold); border-radius:50%; animation:spin 0.8s linear infinite; margin:0 auto 6px;"></div>
                        <div id="${containerId}_statusText" style="font-weight:700; font-size:12px; color:var(--text-primary);">Processing & Uploading...</div>
                    </div>
                </div>

                <!-- Gallery Chips Grid -->
                <div id="${containerId}_grid" style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px;"></div>
            </div>
        `;

        const multiInput = document.getElementById(`${containerId}_multiInput`);
        const idle = document.getElementById(`${containerId}_idle`);
        const loading = document.getElementById(`${containerId}_loading`);
        const statusText = document.getElementById(`${containerId}_statusText`);

        multiInput.addEventListener('change', async (e) => {
            const files = Array.from(e.target.files || []);
            if (!files.length) return;

            idle.style.display = 'none';
            loading.style.display = 'block';

            let doneCount = 0;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                statusText.textContent = `Compressing & Uploading image ${i + 1} of ${files.length} (${file.name})...`;
                try {
                    const compressed = await compressAndConvertToWebP(file, { maxDimension: 1920, quality: 0.82 });
                    const res = await uploadToX02(compressed.file);
                    if (res && res.url) {
                        galleryUrls.push(res.url);
                        doneCount++;
                    }
                } catch (err) {
                    console.error('Failed uploading gallery photo:', err);
                }
            }

            idle.style.display = 'block';
            loading.style.display = 'none';
            multiInput.value = '';
            renderGallery();
        });

        // Attach global helper for deleting gallery images
        window[`_x02_gallery_${containerId}`] = {
            remove: function(idx) {
                galleryUrls.splice(idx, 1);
                renderGallery();
            },
            setUrls: function(urls) {
                galleryUrls = [...urls];
                renderGallery();
            }
        };

        window.removeX02GalleryImage = function(cId, idx) {
            if (window[`_x02_gallery_${cId}`]) {
                window[`_x02_gallery_${cId}`].remove(idx);
            }
        };

        renderGallery();

        // Also watch for manual edits in textarea
        textarea.addEventListener('input', () => {
            galleryUrls = textarea.value.split(',').map(s => s.trim()).filter(Boolean);
            renderGallery();
        });
    }

    /**
     * Single variant color photo uploader helper
     */
    async function uploadVariantPhoto(btnElement, targetInputSelector) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.style.display = 'none';
        document.body.appendChild(input);

        input.onchange = async () => {
            const file = input.files[0];
            if (!file) return;

            const originalBtnHtml = btnElement.innerHTML;
            btnElement.disabled = true;
            btnElement.innerHTML = '⏳ WebP...';

            try {
                // Compress & Convert
                const compressed = await compressAndConvertToWebP(file, { maxDimension: 1600, quality: 0.82 });
                btnElement.innerHTML = '☁️ x02.me...';

                // Upload
                const uploadRes = await uploadToX02(compressed.file);
                if (uploadRes && uploadRes.url) {
                    const row = btnElement.closest('.color-variant-row') || btnElement.parentElement;
                    const urlInput = row.querySelector(targetInputSelector || 'input[name="prod_variant_image[]"], input[name="edit_variant_image[]"]');
                    if (urlInput) {
                        urlInput.value = uploadRes.url;
                        urlInput.dispatchEvent(new Event('input'));
                    }
                    btnElement.innerHTML = '✓ Done!';
                    setTimeout(() => { btnElement.innerHTML = originalBtnHtml; btnElement.disabled = false; }, 2000);
                }
            } catch (err) {
                alert('Variant photo upload failed: ' + err.message);
                btnElement.innerHTML = originalBtnHtml;
                btnElement.disabled = false;
            } finally {
                document.body.removeChild(input);
            }
        };

        input.click();
    }

    // Export to global window
    window.X02Uploader = {
        compressAndConvertToWebP,
        uploadToX02,
        initSingleUploader: initX02SingleUploader,
        initGalleryUploader: initX02GalleryUploader,
        uploadVariantPhoto
    };

})(window);
