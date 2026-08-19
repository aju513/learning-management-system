import Quill from 'quill';

function syncEditorContent(quill, textarea, wrapper) {
    const html = quill.getSemanticHTML();

    textarea.value = ['<p><br></p>', '<p></p>'].includes(html) ? '' : html;
    textarea.dispatchEvent(new Event('input', { bubbles: true }));
    wrapper.dispatchEvent(new CustomEvent('editor-content-changed', {
        bubbles: true,
        detail: { html: textarea.value },
    }));
}

function showUploadError(wrapper, message = '') {
    const error = wrapper.querySelector('[data-quill-upload-error]');
    if (!error) return;

    error.textContent = message;
    error.classList.toggle('hidden', !message);
}

function uploadImage(quill, wrapper, textarea) {
    const uploadUrl = wrapper.dataset.imageUploadUrl;
    if (!uploadUrl || textarea.disabled) return;

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/jpeg,image/png,image/webp';
    input.hidden = true;
    document.body.appendChild(input);

    input.addEventListener('change', async () => {
        const file = input.files?.[0];
        input.remove();
        if (!file) return;

        showUploadError(wrapper);
        const formData = new FormData();
        formData.append('image', file);

        try {
            const response = await fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const validationMessage = Object.values(payload.errors || {}).flat()[0];
                throw new Error(validationMessage || payload.message || 'The image could not be uploaded.');
            }

            const range = quill.getSelection(true) || { index: quill.getLength(), length: 0 };
            quill.insertEmbed(range.index, 'image', payload.url, 'user');
            quill.setSelection(range.index + 1, 0, 'user');
        } catch (error) {
            showUploadError(wrapper, error.message || 'The image could not be uploaded.');
        }
    }, { once: true });

    input.click();
}

function initializeEditor(wrapper) {
    if (wrapper.dataset.quillInitialized === 'true') return;

    const editorElement = wrapper.querySelector('.ql-container');
    const toolbarElement = wrapper.querySelector('.ql-toolbar');
    const textarea = wrapper.querySelector('textarea');

    if (!editorElement || !toolbarElement || !textarea) return;

    const quill = new Quill(editorElement, {
        modules: {
            toolbar: {
                container: toolbarElement,
                handlers: {
                    image: () => uploadImage(quill, wrapper, textarea),
                },
            },
        },
        placeholder: editorElement.dataset.placeholder || 'Write something...',
        readOnly: textarea.disabled,
        theme: 'snow',
    });

    if (textarea.value) {
        quill.clipboard.dangerouslyPasteHTML(textarea.value);
    }

    quill.on('text-change', () => syncEditorContent(quill, textarea, wrapper));
    syncEditorContent(quill, textarea, wrapper);
    wrapper.dataset.quillInitialized = 'true';
}

export function initializeQuillEditors() {
    document.querySelectorAll('[data-quill-editor]').forEach(initializeEditor);
}
