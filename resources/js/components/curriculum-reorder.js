import Sortable from 'sortablejs';

export default function curriculumReorder({ csrfToken }) {
    return {
        csrfToken,
        saving: {},
        messages: {},
        statusMessage: null,
        statusType: 'success',
        sortables: [],

        init() {
            if (this.$refs.modules.dataset.reorderUrl) {
                this.sortables.push(new Sortable(this.$refs.modules, {
                    handle: '.handle',
                    draggable: '[data-module-id]',
                    animation: 150,
                    onStart: (event) => this.rememberOrder(event.from),
                    onEnd: (event) => this.saveOrder(event.from, 'module_ids', event.from.dataset.reorderUrl),
                }));
            }

            this.$refs.modules.querySelectorAll('[data-chapter-list][data-reorder-url]').forEach((list) => {
                this.sortables.push(new Sortable(list, {
                    handle: '.handle',
                    draggable: '[data-chapter-id]',
                    animation: 150,
                    onStart: (event) => this.rememberOrder(event.from),
                    onEnd: (event) => this.saveOrder(event.from, 'chapter_ids', event.from.dataset.reorderUrl),
                }));
            });

            this.$refs.modules.querySelectorAll('[data-material-list][data-reorder-url]').forEach((list) => {
                this.sortables.push(new Sortable(list, {
                    handle: '.handle',
                    draggable: '[data-material-id]',
                    animation: 150,
                    onStart: (event) => this.rememberOrder(event.from),
                    onEnd: (event) => this.saveOrder(event.from, 'material_ids', event.from.dataset.reorderUrl),
                }));
            });
        },

        rememberOrder(list) {
            list.dataset.previousOrder = JSON.stringify(this.itemIds(list));
        },

        itemIds(list) {
            const attribute = list.hasAttribute('data-module-list')
                ? 'moduleId'
                : list.hasAttribute('data-chapter-list')
                    ? 'chapterId'
                    : 'materialId';

            return [...list.querySelectorAll(`[data-${attribute.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}]`)]
                .map((item) => Number(item.dataset[attribute]));
        },

        async saveOrder(list, field, url) {
            const ids = this.itemIds(list);
            const key = `${field}:${url}`;
            this.saving = { ...this.saving, [key]: true };
            this.messages = { ...this.messages, [key]: null };
            this.statusMessage = 'Saving order…';
            this.statusType = 'saving';

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ [field]: ids }),
                });

                if (!response.ok) {
                    throw new Error('The order could not be saved.');
                }

                this.renumber();
                this.messages = { ...this.messages, [key]: 'Saved' };
                this.statusMessage = 'Order saved.';
                this.statusType = 'success';
            } catch (error) {
                this.restoreOrder(list);
                this.renumber();
                this.messages = { ...this.messages, [key]: 'Could not save. Order restored.' };
                this.statusMessage = 'Could not save. Order restored.';
                this.statusType = 'error';
            } finally {
                this.saving = { ...this.saving, [key]: false };
            }
        },

        restoreOrder(list) {
            const previous = JSON.parse(list.dataset.previousOrder || '[]');
            const items = new Map(this.itemElements(list).map((item) => [this.itemId(item), item]));
            previous.forEach((id) => {
                const item = items.get(id);
                if (item) list.appendChild(item);
            });
        },

        itemElements(list) {
            return [...list.children].filter((item) => item.matches('[data-module-id], [data-chapter-id], [data-material-id]'));
        },

        itemId(item) {
            return Number(item.dataset.moduleId || item.dataset.chapterId || item.dataset.materialId);
        },

        renumber() {
            this.$refs.modules.querySelectorAll('[data-module-number]').forEach((number, index) => {
                number.textContent = `Module ${index + 1}`;
            });
            this.$refs.modules.querySelectorAll('[data-chapter-list]').forEach((list) => {
                list.querySelectorAll('[data-chapter-number]').forEach((number, index) => {
                    number.textContent = `Chapter ${index + 1}`;
                });
            });
            this.$refs.modules.querySelectorAll('[data-material-list]').forEach((list) => {
                list.querySelectorAll('[data-material-number]').forEach((number, index) => {
                    number.textContent = `Page ${index + 1}`;
                });
            });
        },
    };
}
