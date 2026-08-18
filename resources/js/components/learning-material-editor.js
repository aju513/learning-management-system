export default function learningMaterialEditor(initial) {
    return {
        ...initial,
        selectedFileName: null,
        selectedFilePreview: null,

        typeLabels: {
            article: 'Article',
            video: 'Video',
            file: 'File',
            link: 'Link',
            course_assessment: 'Course assessment',
        },

        get typeLabel() {
            return this.typeLabels[this.type] || 'Learning material';
        },

        get previewFileName() {
            if (this.selectedFileName) return this.selectedFileName;
            return this.type === this.initialType ? this.currentFileName : null;
        },

        get videoPreviewUrl() {
            return this.videoSource === 'upload' ? this.selectedFilePreview || '' : this.videoUrl || '';
        },

        get videoEmbedUrl() {
            if (this.videoSource !== 'url' || !this.videoUrl) return '';

            try {
                const url = new URL(this.videoUrl);
                const host = url.hostname.toLowerCase().replace(/^www\./, '');
                let videoId = '';

                if (host === 'youtu.be') {
                    videoId = url.pathname.split('/').filter(Boolean)[0] || '';
                } else if (host === 'youtube.com' || host === 'm.youtube.com') {
                    videoId = url.searchParams.get('v') || url.pathname.match(/^\/(?:embed|shorts|live)\/([^/?]+)/)?.[1] || '';
                } else if (host === 'vimeo.com' || host === 'player.vimeo.com') {
                    videoId = url.pathname.match(/\/(\d+)(?:$|\/)/)?.[1] || '';
                    if (host === 'player.vimeo.com' && url.pathname.startsWith('/video/')) {
                        videoId = url.pathname.match(/^\/video\/(\d+)/)?.[1] || '';
                    }
                }

                if (host.includes('youtube.com') && videoId) return `https://www.youtube.com/embed/${encodeURIComponent(videoId)}`;
                if ((host === 'vimeo.com' || host === 'player.vimeo.com') && videoId) return `https://player.vimeo.com/video/${encodeURIComponent(videoId)}`;
            } catch (error) {
                return '';
            }

            return '';
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
