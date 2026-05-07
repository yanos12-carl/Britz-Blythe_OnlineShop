/**
 * ============================================================================
 * ADD PRODUCT FORM FUNCTIONALITY
 * ============================================================================
 */

class AddProductForm {
    constructor() {
        this.mediaFiles = [];
        this.variants = [];
        this.variationGroups = []; // Stores {name: string, options: string[]}
        this.form = document.getElementById('addProductForm');
        this.mediaContainer = document.getElementById('mediaPreviewContainer');
        this.variantsContainer = document.getElementById('variantsContainer');
        this.init();
    }

    init() {
        // World-class safety: Don't initialize if the main form isn't present
        if (!this.form) return;
        this.setupMediaUpload();
        this.setupFormActions();
        this.setupEventListeners(); // Renamed to resolve ReferenceError in legacy callers
        this.setupVariantManagement(); // Initialize variant management
        this.setupFormValidation();
        this.setupAutoSave();
        this.restoreFromStorage();
        this.loadExistingMedia();
    }

    /**
     * Media Upload Setup
     */
    setupMediaUpload() {
        const dropZone = document.getElementById('mediaDropZone');
        const input = document.getElementById('mediaInput');

        if (!dropZone || !input) return;

        dropZone.addEventListener('click', () => input.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            this.handleFileUpload(e.dataTransfer.files);
        });

        input.addEventListener('change', (e) => {
            this.handleFileUpload(e.target.files);
        });
    }

    async handleFileUpload(files) {
        if (files.length === 0) return;
        this.showLoading('Uploading images...');

        const formData = new FormData();
        for (let file of files) {
            formData.append('images[]', file);
        }

        try {
            const response = await fetch('api/upload-product-images.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                data.files.forEach(file => {
                    this.mediaFiles.push(file);
                    this.addMediaPreview(file);
                });
                this.updateMediaData();
                this.showAlert(data.message, 'success');
            } else {
                this.showAlert(data.error || 'Upload failed', 'error');
            }
        } catch (error) {
            this.showAlert('Upload error: ' + error.message, 'error');
        } finally {
            this.hideLoading();
        }
    }

    addMediaPreview(file) {
        const item = document.createElement('div');
        item.className = 'media-preview-item';
        item.dataset.url = file.url;
        item.dataset.type = file.type;
        item.draggable = true;

        // Determine if this is the first image to be set as primary
        const isFirstImage = this.mediaFiles.filter(f => f.type === 'image').length === 1;
        if (file.type === 'image') {
            item.innerHTML = `
                <img src="${file.url}" alt="${file.name}">
                ${isFirstImage ? '<span class="media-item-badge primary">Primary</span>' : ''}
                <div class="media-item-actions">
                    <button type="button" class="make-primary-btn" title="Set as primary image">Primary</button>
                    <button type="button" class="remove-media-btn" title="Remove image">Remove</button>
                </div>
            `;
        } else {
            item.innerHTML = `
                <video src="${file.url}" controls style="width: 100%; height: 100%;"></video>
                <div class="media-item-actions">
                    <button type="button" class="remove-media-btn" title="Remove video">Remove</button>
                </div>
            `;
        }

        this.mediaContainer.appendChild(item);

        // Drag handlers
        item.addEventListener('dragstart', (e) => {
            item.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('dragging');
        });

        item.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
        });

        item.addEventListener('drop', (e) => {
            e.preventDefault();
            const draggedItem = document.querySelector('.media-preview-item.dragging');
            if (draggedItem && draggedItem !== item) {
                const items = Array.from(this.mediaContainer.children);
                const draggedIndex = items.indexOf(draggedItem);
                const targetIndex = items.indexOf(item);

                // Reorder the DOM elements
                if (draggedIndex < targetIndex) {
                    this.mediaContainer.insertBefore(draggedItem, item.nextSibling);
                } else {
                    this.mediaContainer.insertBefore(draggedItem, item);
                }
                this.updateMediaDataFromDOM(); // Update internal array and hidden input based on new DOM order
            }
        });
    }

    removeMedia(url) {
        this.mediaFiles = this.mediaFiles.filter(f => f.url !== url); // Update internal array
        document.querySelector(`.media-preview-item[data-url="${url}"]`).remove(); // Remove from DOM
        this.updateMediaData();
    }

    setAsPrimary(itemElement) {
        document.querySelectorAll('.media-preview-item').forEach(i => {
            const badge = i.querySelector('.media-item-badge.primary');
            if (badge) badge.remove();
        });

        const newBadge = document.createElement('span');
        newBadge.className = 'media-item-badge primary';
        newBadge.textContent = 'Primary';
        itemElement.appendChild(newBadge);

        // Update internal array: move the primary item to the front
        const primaryUrl = itemElement.dataset.url;
        const primaryFile = this.mediaFiles.find(f => f.url === primaryUrl);
        if (primaryFile) {
            this.mediaFiles = this.mediaFiles.filter(f => f.url !== primaryUrl);
            this.mediaFiles.unshift(primaryFile); // Add to the beginning
        }

        // Reorder the DOM elements to reflect the new primary
        this.mediaContainer.prepend(itemElement);
        this.updateMediaData();
    }

    updateMediaData() {
        const el = document.getElementById('mediaData');
        if (el) el.value = JSON.stringify(this.mediaFiles);
    }

    // New function to update internal mediaFiles array from DOM order after drag/drop
    updateMediaDataFromDOM() {
        if (!this.mediaContainer) return;
        const mediaArray = Array.from(this.mediaContainer.querySelectorAll('.media-preview-item')).map(item => ({
            url: item.dataset.url,
            type: item.dataset.type,
        }));
        this.mediaFiles = mediaArray;
        this.updateMediaData(); // Update hidden input
    }

    loadExistingMedia() {
        const el = document.getElementById('mediaData');
        const mediaData = el ? el.value : null;
        if (mediaData) {
            try {
                const media = JSON.parse(mediaData);
                // If there's a video URL in draft, add it to mediaFiles if not already present
                const videoUrl = document.getElementById('videoUrl').value;
                if (videoUrl && !media.some(m => m.url === videoUrl && m.type === 'video')) {
                    media.push({
                        url: videoUrl,
                        type: 'video',
                        name: 'Product Video',
                        size: 0 // Placeholder
                    });
                }

                this.mediaFiles = []; // Clear existing to prevent duplicates

                media.forEach((m, index) => {
                    this.mediaFiles.push(m);
                    this.addMediaPreview(m);
                });
            } catch (e) {
                console.error('Error loading media:', e);
            }
        }
    }

    /**
     * Variant Management
     */
    setupVariantManagement() {
        const addGroupBtn = document.getElementById('addVariationGroupBtn');
        if (addGroupBtn) {
            addGroupBtn.addEventListener('click', () => this.addVariationGroup());
        }

        this.loadExistingVariants(); // Load variants from draft or existing product
    }

    addVariationGroup() {
        if (this.variationGroups.length >= 2) {
            this.showAlert('Maximum 2 variation types allowed (e.g., Color and Size)', 'warning');
            return;
        }

        const groupIndex = this.variationGroups.length;
        const group = { name: '', options: [] };
        this.variationGroups.push(group);

        const container = document.getElementById('variationGroupsContainer');
        const groupDiv = document.createElement('div');
        groupDiv.className = 'form-section variation-group-box';
        groupDiv.dataset.index = groupIndex;
        groupDiv.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label>Variation Type</label>
                    <input type="text" class="form-control group-name" placeholder="e.g., Color" oninput="this.formProduct.updateGroupName(${groupIndex}, this.value)">
                </div>
                <button type="button" class="btn btn-outline btn-sm remove-group-btn" onclick="this.formProduct.removeGroup(${groupIndex})">Remove Type</button>
            </div>
            <div class="options-container">
                <label>Options</label>
                <div class="options-list" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;"></div>
                <input type="text" class="form-control option-input" placeholder="Press Enter to add option (e.g., Red)">
            </div>
        `;

        // Hack to link the instance to the DOM elements for these inline onclicks
        groupDiv.querySelector('.group-name').formProduct = this;
        groupDiv.querySelector('.remove-group-btn').formProduct = this;

        const optionInput = groupDiv.querySelector('.option-input');
        optionInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.addOption(groupIndex, optionInput.value);
                optionInput.value = '';
            }
        });

        container.appendChild(groupDiv);
        this.generateCombinations();
    }

    updateGroupName(index, name) {
        this.variationGroups[index].name = name;
        this.generateCombinations();
    }

    removeGroup(index) {
        this.variationGroups.splice(index, 1);
        document.getElementById('variationGroupsContainer').innerHTML = '';
        // Re-render remaining groups to fix indices
        const oldGroups = [...this.variationGroups];
        this.variationGroups = [];
        oldGroups.forEach(g => {
            this.addVariationGroup();
            const last = this.variationGroups.length - 1;
            this.variationGroups[last].name = g.name;
            this.variationGroups[last].options = g.options;
            const box = document.querySelector(`.variation-group-box[data-index="${last}"]`);
            if (box) {
                box.querySelector('.group-name').value = g.name;
                g.options.forEach(opt => this.renderOptionTag(last, opt));
            }
        });
        this.generateCombinations();
    }

    addOption(groupIndex, value) {
        value = value.trim();
        if (!value || this.variationGroups[groupIndex].options.includes(value)) return;

        this.variationGroups[groupIndex].options.push(value);
        this.renderOptionTag(groupIndex, value);
        this.generateCombinations();
    }

    renderOptionTag(groupIndex, value) {
        const list = document.querySelector(`.variation-group-box[data-index="${groupIndex}"] .options-list`);
        if (!list) return;
        const tag = document.createElement('span');
        tag.className = 'badge';
        tag.style.cssText = 'background: var(--primary); color: white; padding: 5px 10px; border-radius: 4px; display: flex; align-items: center; gap: 5px;';
        tag.innerHTML = `${value} <span style="cursor:pointer" onclick="this.parentElement.formProduct.removeOption(${groupIndex}, '${value}')">&times;</span>`;
        tag.formProduct = this; // Link instance for inline onclick
        list.appendChild(tag);
    }

    removeOption(groupIndex, value) {
        this.variationGroups[groupIndex].options = this.variationGroups[groupIndex].options.filter(o => o !== value);
        const box = document.querySelector(`.variation-group-box[data-index="${groupIndex}"]`);
        if (box) {
            const list = box.querySelector('.options-list');
            if (list) list.innerHTML = '';
            this.variationGroups[groupIndex].options.forEach(opt => this.renderOptionTag(groupIndex, opt));
        }
        this.generateCombinations();
    }

    generateCombinations() {
        const tableContainer = document.getElementById('combinationsTableContainer');
        const table = document.getElementById('combinationsTable');
        const bulkBar = document.getElementById('bulkEditBar');

        // Cartesian product logic
        const validGroups = this.variationGroups.filter(g => g.name && g.options.length > 0);

        if (validGroups.length === 0) {
            tableContainer.style.display = 'none';
            if (bulkBar) bulkBar.style.display = 'none';
            table.innerHTML = '';
            this.variants = []; // Clear variants if no groups
            this.updateVariantData();
            return;
        }

        tableContainer.style.display = 'block';
        if (bulkBar) bulkBar.style.display = 'flex';

        let combinations = [[]];
        validGroups.forEach(group => {
            const next = [];
            combinations.forEach(combo => {
                group.options.forEach(opt => {
                    next.push([...combo, { type: group.name, value: opt }]);
                });
            });
            combinations = next;
        });

        // Render table header
        let header = '<thead><tr>';
        validGroups.forEach(g => header += `<th>${g.name}</th>`);
        header += '<th>Price</th><th>Stock</th><th>SKU</th></tr></thead>';

        // Render rows
        let body = '<tbody>';
        combinations.forEach((combo, i) => {
            const comboName = combo.map(c => c.value).join(' - ');
            const comboAttributes = {};
            combo.forEach(c => comboAttributes[c.type] = c.value);

            // Try to find existing variant data to pre-fill
            const existingVariant = this.variants.find(v => {
                // Compare attributes of the existing variant with the generated combo
                const existingAttrs = v.attributes || {};
                const generatedAttrs = comboAttributes;

                // Check if all generated attributes exist in existingAttrs and match
                return Object.keys(generatedAttrs).every(key =>
                    existingAttrs.hasOwnProperty(key) && existingAttrs[key] === generatedAttrs[key]
                ) && Object.keys(existingAttrs).every(key => // Also check the other way around
                    generatedAttrs.hasOwnProperty(key) && generatedAttrs[key] === existingAttrs[key]
                );
            });

            const price = existingVariant?.price || '';
            const stock = existingVariant?.stock || '';
            const sku = existingVariant?.sku || '';

            body += `<tr class="combination-row" data-name="${comboName}" data-attributes='${JSON.stringify(comboAttributes)}'>`;
            combo.forEach(c => body += `<td>${c.value}</td>`);
            body += `
                <td><input type="number" class="form-control combo-price" step="0.01" placeholder="0.00" value="${price}" oninput="this.formProduct.updateVariantData()"></td>
                <td><input type="number" class="form-control combo-stock" placeholder="0" value="${stock}" oninput="this.formProduct.updateVariantData()"></td>
                <td><input type="text" class="form-control combo-sku" placeholder="Auto" value="${sku}" oninput="this.formProduct.updateVariantData()"></td>
            </tr>`;
        });
        body += '</tbody>';

        table.innerHTML = header + body;
        table.querySelectorAll('input').forEach(input => input.formProduct = this); // Link instance for inline oninput
        this.updateVariantData(); // Update hidden input
    }

    updateVariantData() {
        const rows = document.querySelectorAll('.combination-row');
        this.variants = Array.from(rows).map(row => {
            return {
                name: row.dataset.name,
                sku: row.querySelector('.combo-sku')?.value || '',
                price: parseFloat(row.querySelector('.combo-price')?.value || 0) || 0,
                stock: parseInt(row.querySelector('.combo-stock')?.value || 0) || 0,
                attributes: JSON.parse(row.dataset.attributes || '{}'),
            };
        });

        const el = document.getElementById('variantData');
        if (el) el.value = JSON.stringify(this.variants);
    }

    loadExistingVariants() {
        const el = document.getElementById('variantData');
        const variantData = el ? el.value : null;
        const existingVariants = JSON.parse(variantData || '[]');

        // Reconstruct variationGroups from existingVariants
        const tempGroups = {};
        existingVariants.forEach(v => {
            if (v.attributes) {
                Object.entries(v.attributes).forEach(([type, value]) => {
                    if (!tempGroups[type]) {
                        tempGroups[type] = { name: type, options: [] };
                    }
                    if (!tempGroups[type].options.includes(value)) {
                        tempGroups[type].options.push(value);
                    }
                });
            }
        });

        // Clear and re-add variation groups
        this.variationGroups = [];
        document.getElementById('variationGroupsContainer').innerHTML = '';
        Object.values(tempGroups).forEach(group => {
            this.addVariationGroup();
            const lastGroupIndex = this.variationGroups.length - 1;
            this.variationGroups[lastGroupIndex].name = group.name;
            this.variationGroups[lastGroupIndex].options = group.options;

            const box = document.querySelector(`.variation-group-box[data-index="${lastGroupIndex}"]`);
            const groupInput = box?.querySelector('.group-name');
            if (groupInput) {
                groupInput.value = group.name;
                group.options.forEach(opt => this.renderOptionTag(lastGroupIndex, opt));
            }
        });

        // Store the loaded variants for pre-filling the combinations table
        this.variants = existingVariants;
        this.generateCombinations(); // This will use this.variants to pre-fill
    }

    /**
     * Centralized Event Listeners / Delegation
     */
    setupEventListeners() {
        // Section Toggle Delegation
        if (!this.form) return;

        this.form.addEventListener('click', (e) => {
            const header = e.target.closest('.section-header');
            if (header) {
                const container = header.closest('.form-section-container');
                container.classList.toggle('collapsed');
            }
        });

        // Media Container Delegation
        this.mediaContainer?.addEventListener('click', (e) => {
            const item = e.target.closest('.media-preview-item');
            if (!item) return;
            if (e.target.classList.contains('remove-media-btn')) this.removeMedia(item.dataset.url);
            if (e.target.classList.contains('make-primary-btn')) this.setAsPrimary(item);
        });

        // Variation Groups Container Delegation (for dynamic elements)
        const variationGroupsContainer = document.getElementById('variationGroupsContainer');
        if (variationGroupsContainer) {
            variationGroupsContainer.addEventListener('input', (e) => {
                if (e.target.classList.contains('group-name')) {
                    const groupIndex = e.target.closest('.variation-group-box').dataset.index;
                    this.updateGroupName(groupIndex, e.target.value);
                }
            });
            // No need for click listeners here, as they are handled by inline onclicks for now
        }

        // Combinations Table Delegation (for dynamic inputs)
        const combinationsTable = document.getElementById('combinationsTable');
        if (combinationsTable) {
            combinationsTable.addEventListener('input', (e) => {
                if (e.target.classList.contains('combo-price') || e.target.classList.contains('combo-stock') || e.target.classList.contains('combo-sku')) {
                    this.updateVariantData();
                }
            });
        }

        // Bulk Edit Actions
        document.getElementById('applyBulkPriceBtn')?.addEventListener('click', () => {
            const price = document.getElementById('bulkPriceInput').value;
            if (price) this.applyBulkPrice(price);
        });

        // Form-wide validation on blur using delegation
        this.form.addEventListener('blur', (e) => {
            if (e.target.classList.contains('form-control')) {
                this.validateField(e.target);
            }
        }, true); // Use capture phase for blur events
    }

    applyBulkPrice(price) {
        const priceInputs = document.querySelectorAll('.combo-price');
        priceInputs.forEach(input => {
            input.value = price;
        });
        this.updateVariantData();
        this.showAlert('Bulk price applied to all variants', 'success');
    }

    /**
     * Form Validation
     */
    setupFormValidation() {
        // Validation is now handled by event delegation in setupEventListeners()
    }

    validateField(field) {
        if (!field.value.trim() && field.hasAttribute('required')) {
            field.classList.add('error');
            return false;
        }

        if (field.type === 'number' && field.value) {
            if (field.min && parseFloat(field.value) < parseFloat(field.min)) {
                field.classList.add('error');
                return false;
            }
        }

        if (field.type === 'email' && field.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(field.value)) {
                field.classList.add('error');
                return false;
            }
        }

        field.classList.remove('error');
        return true;
    }

    /**
     * Form Actions
     */
    setupFormActions() {
        document.getElementById('saveDraftBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.saveDraft();
        });

        this.form?.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showAlert('Please fix all errors before publishing', 'error');
            } else {
                // Clear local storage draft on successful validation before submission
                localStorage.removeItem('product_draft');
            }
        });
    }

    validateForm() {
        // Check required fields
        const nameEl = document.getElementById('name');
        const catEl = document.getElementById('category');
        if (!nameEl || !catEl) return true;

        const name = nameEl.value.trim();
        const category = catEl.value;
        const price = parseFloat(document.getElementById('price')?.value || 0) || 0;
        const stock = parseInt(document.getElementById('stock')?.value || 0) || 0;

        if (!name) {
            this.showAlert('Product name is required', 'error');
            return false;
        }

        if (!category) {
            this.showAlert('Please select a category', 'error');
            return false;
        }

        if (price <= 0) {
            this.showAlert('Price must be greater than 0', 'error');
            return false;
        }

        if (stock < 0) {
            this.showAlert('Stock cannot be negative', 'error');
            return false;
        }

        if (this.mediaFiles.length === 0) {
            this.showAlert('Please upload at least one image', 'error');
            return false;
        }

        // If variations are present, ensure all combination rows have price and stock
        if (this.variationGroups.some(g => g.options.length > 0)) {
            const combinationRows = document.querySelectorAll('.combination-row');
            if (combinationRows.length === 0) {
                this.showAlert('Please add at least one variation option.', 'error');
                return false;
            }
            for (const row of combinationRows) {
                const priceIn = row.querySelector('.combo-price');
                const stockIn = row.querySelector('.combo-stock');
                if (!priceIn || !stockIn) continue;

                const priceVal = parseFloat(priceIn.value);
                const stockVal = parseInt(stockIn.value);
                if (isNaN(priceVal) || priceVal <= 0) {
                    this.showAlert(`Price is required and must be greater than 0 for combination: ${row.dataset.name}`, 'error');
                    return false;
                }
                if (isNaN(stockVal) || stockVal < 0) {
                    this.showAlert(`Stock is required and cannot be negative for combination: ${row.dataset.name}`, 'error');
                    return false;
                }
            }
        }

        return true;
    }

    saveDraft() {
        const nameEl = document.getElementById('name');
        if (!nameEl) return;

        const draftData = {
            name: nameEl.value,
            category: document.getElementById('category')?.value || '',
            excerpt: document.getElementById('excerpt')?.value || '',
            description: document.getElementById('description')?.value || '',
            price: parseFloat(document.getElementById('price')?.value || 0) || 0,
            discount: parseFloat(document.getElementById('discount')?.value) || null,
            stock: parseInt(document.getElementById('stock')?.value || 0) || 0,
            sku: document.getElementById('sku')?.value || '',
            weight: document.getElementById('weight')?.value || '',
            length: document.getElementById('length')?.value || '',
            width: document.getElementById('width')?.value || '',
            height: document.getElementById('height')?.value || '',
            video_url: document.getElementById('videoUrl')?.value || '',
            media: this.mediaFiles,
            variants: this.variants, // This will contain the combinations data
            variationGroups: this.variationGroups // Save variation groups structure
        };

        localStorage.setItem('product_draft', JSON.stringify(draftData));
        this.showAlert('Draft saved locally', 'success');
    }

    restoreFromStorage() {
        const storedDraft = localStorage.getItem('product_draft');
        if (storedDraft) {
            try {
                const data = JSON.parse(storedDraft);
                const fields = ['name', 'category', 'excerpt', 'description', 'price', 'discount', 'stock', 'sku', 'weight', 'length', 'width', 'height', 'videoUrl'];
                fields.forEach(f => {
                    const el = document.getElementById(f === 'videoUrl' ? 'videoUrl' : f);
                    if (el) {
                        if (f === 'videoUrl') el.value = data.video_url || '';
                        else el.value = data[f] || '';
                    }
                });

                // Restore media files
                this.mediaFiles = data.media || [];
                this.mediaFiles.forEach(m => this.addMediaPreview(m));
                this.updateMediaData();

                // Restore variation groups and combinations
                this.variationGroups = data.variationGroups || [];
                this.variants = data.variants || []; // Store loaded variants for pre-filling

                // Re-render variation groups and combinations
                const variationGroupsContainer = document.getElementById('variationGroupsContainer');
                if (variationGroupsContainer) variationGroupsContainer.innerHTML = '';
                this.variationGroups.forEach((group, index) => {
                    this.addVariationGroup(); // This will add the HTML structure
                    const box = document.querySelector(`.variation-group-box[data-index="${index}"]`);
                    if (box) {
                        box.querySelector('.group-name').value = group.name;
                        group.options.forEach(opt => this.renderOptionTag(index, opt));
                    }
                });
                this.generateCombinations(); // This will use this.variants to pre-fill

                this.showAlert('Draft restored from local storage.', 'info');
            } catch (e) {
                console.error('Error restoring draft:', e);
                localStorage.removeItem('product_draft'); // Clear corrupted draft
            }
        }
    }

    /**
     * UI Utilities
     */
    showAlert(message, type = 'info') {
        const container = document.querySelector('.admin-main');
        if (!container) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <span class="alert-text">${message}</span>
            <button class="alert-close">&times;</button>
        `;
        const existing = container.querySelector('.alert');
        if (existing) existing.remove();

        container.insertBefore(alert, container.firstChild);

        alert.querySelector('.alert-close').addEventListener('click', () => {
            alert.remove();
        });

        setTimeout(() => {
            if (alert.parentNode) alert.remove();
        }, 5000);
    }

    showLoading(message = 'Loading...') {
        const div = document.createElement('div');
        div.id = 'loadingOverlay';
        div.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;
        div.innerHTML = `
            <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
                <div class="loading-spinner" style="margin-bottom: 15px;"></div>
                <p>${message}</p>
            </div>
        `;
        document.body.appendChild(div);
    }

    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.remove();
    }

    /**
     * Auto-save
     */
    setupAutoSave() {
        setInterval(() => {
            this.saveDraft();
        }, 30000); // Every 30 seconds
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Ensure the formProduct instance is globally accessible for inline onclicks
    window.formProduct = new AddProductForm();

    // Global bridge to resolve ReferenceErrors from legacy scripts
    window.setupEventListeners = () => window.formProduct.setupEventListeners();
});
