const sidebar = document.querySelector('#admin-sidebar');
const scrim = document.querySelector('.sidebar-scrim');

function setSidebarOpen(open) {
    sidebar?.classList.toggle('is-open', open);
    scrim?.classList.toggle('is-open', open);
}

document.querySelectorAll('[data-sidebar-open]').forEach((button) => {
    button.addEventListener('click', () => setSidebarOpen(true));
});
document.querySelectorAll('[data-sidebar-close]').forEach((button) => {
    button.addEventListener('click', () => setSidebarOpen(false));
});

const accountMenu = document.querySelector('[data-account-menu]');

if (accountMenu) {
    document.addEventListener('click', (event) => {
        if (!accountMenu.contains(event.target)) {
            accountMenu.open = false;
        }
    });

    accountMenu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            accountMenu.open = false;
            accountMenu.querySelector('summary')?.focus();
        }
    });
}

const confirmDialog = document.querySelector('#confirm-dialog');
let pendingForm = null;

document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (form.dataset.confirmed === 'true') {
            form.dataset.confirmed = 'false';
            return;
        }

        event.preventDefault();
        pendingForm = form;
        const message = confirmDialog?.querySelector('[data-confirm-message]');

        if (message) {
            message.textContent = form.dataset.confirm || 'This action cannot be undone.';
        }

        confirmDialog?.showModal();
    });
});

confirmDialog?.querySelector('[data-confirm-submit]')?.addEventListener('click', () => {
    if (!pendingForm) return;

    pendingForm.dataset.confirmed = 'true';
    confirmDialog.close();
    pendingForm.requestSubmit();
    pendingForm = null;
});

confirmDialog?.addEventListener('close', () => {
    pendingForm = null;
});

document.querySelectorAll('[data-permissions-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const checkboxes = [...document.querySelectorAll('[data-permission-group]')]
            .filter((checkbox) => checkbox.dataset.permissionGroup === button.dataset.permissionsToggle);
        const shouldCheck = checkboxes.some((checkbox) => !checkbox.checked);

        checkboxes.forEach((checkbox) => {
            checkbox.checked = shouldCheck;
        });

        button.textContent = shouldCheck ? 'Deselect all' : 'Select all';
    });
});

document.querySelectorAll('[data-toast]').forEach((toast) => {
    const dismiss = () => toast.remove();
    toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 5200);
});

const isSafeGoogleMapsEmbedUrl = (value) => {
    try {
        const url = new URL(value);
        const isEmbedPath = url.pathname === '/maps/embed' || url.pathname.startsWith('/maps/embed/');

        return url.protocol === 'https:'
            && url.hostname.toLowerCase() === 'www.google.com'
            && isEmbedPath
            && url.search.length > 1
            && url.username === ''
            && url.password === ''
            && (url.port === '' || url.port === '443');
    } catch {
        return false;
    }
};

document.querySelectorAll('[data-google-map-preview]').forEach((preview) => {
    const urlInput = document.querySelector(preview.dataset.mapUrlInput);
    const locationNameInput = document.querySelector(preview.dataset.locationNameInput);
    const emptyState = preview.querySelector('[data-map-empty]');
    const frameContainer = preview.querySelector('[data-map-frame]');
    const frame = frameContainer?.querySelector('iframe');

    if (!urlInput || !emptyState || !frameContainer || !frame) return;

    const updatePreview = () => {
        const embedUrl = urlInput.value.trim();
        const isSafeUrl = isSafeGoogleMapsEmbedUrl(embedUrl);

        emptyState.hidden = isSafeUrl;
        frameContainer.hidden = !isSafeUrl;

        if (isSafeUrl) {
            frame.src = embedUrl;
            frame.title = locationNameInput?.value.trim() || 'Google Maps location preview';
        } else {
            frame.removeAttribute('src');
        }
    };

    urlInput.addEventListener('input', updatePreview);
    locationNameInput?.addEventListener('input', updatePreview);
    updatePreview();
});

document.querySelectorAll('[data-image-preview]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = document.querySelector(input.dataset.imagePreview);
        const file = input.files?.[0];
        if (!preview || !file) return;

        const placeholder = preview.closest('[data-image-preview-frame]')?.querySelector('[data-image-placeholder]');
        const removeInputSelector = input.dataset.imageRemoveInput;
        if (removeInputSelector) {
            const removeInput = document.querySelector(removeInputSelector);
            if (removeInput) removeInput.checked = false;
        }

        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
        if (placeholder) placeholder.hidden = true;
    });
});

document.querySelectorAll('[data-image-remove]').forEach((input) => {
    const preview = document.querySelector(input.dataset.imageRemove);
    const fileInput = document.querySelector(input.dataset.imageFile);
    if (!preview) return;
    const placeholder = preview.closest('[data-image-preview-frame]')?.querySelector('[data-image-placeholder]');

    input.addEventListener('change', () => {
        if (input.checked) {
            if (fileInput) fileInput.value = '';
            preview.hidden = true;
            if (placeholder) placeholder.hidden = false;
            return;
        }

        if (preview.dataset.originalSrc) {
            preview.src = preview.dataset.originalSrc;
            preview.hidden = false;
            if (placeholder) placeholder.hidden = true;
        }
    });
});

document.querySelectorAll('[data-image-gallery]').forEach((input) => {
    input.addEventListener('change', () => {
        const gallery = document.querySelector(input.dataset.imageGallery);
        if (!gallery) return;

        const placeholder = gallery.querySelector('[data-gallery-placeholder]');
        gallery.querySelectorAll('img[data-gallery-preview-image]').forEach((image) => image.remove());
        const files = [...(input.files || [])];

        if (!files.length) {
            if (placeholder) placeholder.hidden = false;
            return;
        }

        if (placeholder) placeholder.hidden = true;
        files.forEach((file) => {
            const image = document.createElement('img');
            image.className = 'banner-upload-preview-image';
            image.dataset.galleryPreviewImage = 'true';
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            gallery.append(image);
        });
    });
});

document.querySelectorAll('[data-dropzone]').forEach((dropzone) => {
    const input = dropzone.querySelector('input[type="file"]');
    if (!input) return;

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragging');
        });
    });

    ['dragleave', 'dragend'].forEach((eventName) => {
        dropzone.addEventListener(eventName, () => dropzone.classList.remove('is-dragging'));
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');

        const files = [...(event.dataTransfer?.files || [])];
        const transfer = new DataTransfer();
        (input.multiple ? files : files.slice(0, 1)).forEach((file) => transfer.items.add(file));
        input.files = transfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
});

document.querySelectorAll('[data-slug-source]').forEach((source) => {
    const target = document.querySelector(source.dataset.slugSource);
    if (!target) return;

    source.addEventListener('input', () => {
        target.value = source.value
            .toLowerCase()
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'banner';
    });
});

document.querySelectorAll('[data-category-select]').forEach((categorySelect) => {
    const subcategorySelect = document.querySelector(categorySelect.dataset.categorySelect);
    if (!subcategorySelect) return;

    const updateSubcategories = () => {
        [...subcategorySelect.options].forEach((option) => {
            if (!option.value) return;
            option.hidden = option.dataset.categoryId !== categorySelect.value;
            if (option.hidden && option.selected) subcategorySelect.value = '';
        });
    };

    categorySelect.addEventListener('change', updateSubcategories);
    updateSubcategories();
});

const regularPrice = document.querySelector('[name="regular_price"]');
const sellingPrice = document.querySelector('[name="selling_price"]');
const discountPreview = document.querySelector('[data-discount-preview]');

function updateDiscount() {
    const regular = Number.parseFloat(regularPrice?.value || '0');
    const selling = Number.parseFloat(sellingPrice?.value || '0');
    const percent = regular > 0 && selling < regular ? Math.round(((regular - selling) / regular) * 100) : 0;

    if (discountPreview) {
        discountPreview.textContent = percent > 0 ? `${percent}% discount from regular price` : 'No discount applied';
    }
}

regularPrice?.addEventListener('input', updateDiscount);
sellingPrice?.addEventListener('input', updateDiscount);
updateDiscount();

document.querySelectorAll('form[data-loading]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-loading-button]');
        if (!button) return;

        button.disabled = true;
        button.dataset.originalLabel = button.textContent.trim();
        button.textContent = 'Saving...';
    });
});
