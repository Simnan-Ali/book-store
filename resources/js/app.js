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

document.querySelectorAll('[data-toast]').forEach((toast) => {
    const dismiss = () => toast.remove();
    toast.querySelector('[data-toast-close]')?.addEventListener('click', dismiss);
    window.setTimeout(dismiss, 5200);
});

document.querySelectorAll('[data-image-preview]').forEach((input) => {
    input.addEventListener('change', () => {
        const preview = document.querySelector(input.dataset.imagePreview);
        const file = input.files?.[0];
        if (!preview || !file) return;

        preview.src = URL.createObjectURL(file);
        preview.hidden = false;
    });
});

document.querySelectorAll('[data-image-gallery]').forEach((input) => {
    input.addEventListener('change', () => {
        const gallery = document.querySelector(input.dataset.imageGallery);
        if (!gallery) return;

        gallery.replaceChildren();
        [...(input.files || [])].forEach((file) => {
            const image = document.createElement('img');
            image.className = 'existing-image';
            image.src = URL.createObjectURL(file);
            image.alt = file.name;
            gallery.append(image);
        });
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
