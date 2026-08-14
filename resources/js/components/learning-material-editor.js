export default function learningMaterialEditor(initial, assessments) {
    return {
        ...initial,
        assessments,
        selectedFileName: null,

        typeLabels: {
            article: 'Article',
            video: 'Video',
            file: 'File',
            link: 'Link',
            assessment: 'Assessment',
        },

        get typeLabel() {
            return this.typeLabels[this.type] || 'Learning material';
        },

        get selectedAssessment() {
            return this.assessments.find(item => String(item.id) === String(this.assessmentId)) || null;
        },

        get previewFileName() {
            if (this.selectedFileName) return this.selectedFileName;
            return this.type === this.initialType ? this.currentFileName : null;
        },

        get safeContentHtml() {
            const template = document.createElement('template');
            template.innerHTML = this.content || '';
            template.content.querySelectorAll('script,style,iframe,object,embed').forEach(node => node.remove());
            const allowed = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'UL', 'OL', 'LI', 'H2', 'H3', 'BLOCKQUOTE']);

            Array.from(template.content.querySelectorAll('*')).forEach(node => {
                if (!allowed.has(node.tagName)) {
                    node.replaceWith(...node.childNodes);
                    return;
                }

                Array.from(node.attributes).forEach(attribute => node.removeAttribute(attribute.name));
            });

            return template.innerHTML;
        },

        syncContentBeforeSubmit(event) {
            const editor = event.target.querySelector('[contenteditable="true"]');
            const textarea = event.target.querySelector('textarea[name="content"]');
            if (editor && textarea) {
                textarea.value = editor.innerHTML;
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            }
        },
    };
}
