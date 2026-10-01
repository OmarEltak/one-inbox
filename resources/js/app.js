import './echo';
import { createPopup } from '@picmo/popup-picker';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Color } from '@tiptap/extension-color';
import { TextStyle } from '@tiptap/extension-text-style';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

document.addEventListener('alpine:init', () => {
    Alpine.data('tiptap', (initialContent = '', language = 'en') => ({
        editor: null,
        content: initialContent,
        isRtl: ['ar', 'he', 'fa', 'ur'].includes(language),

        init() {
            const self = this;

            this.editor = new Editor({
                element: this.$refs.editorEl,
                extensions: [
                    StarterKit,
                    TextStyle,
                    Color,
                    Link.configure({ openOnClick: false }),
                    Image,
                ],
                content: this.content,
                editorProps: {
                    attributes: {
                        class: 'prose prose-zinc max-w-none min-h-[400px] p-4 focus:outline-none',
                        dir: this.isRtl ? 'rtl' : 'ltr',
                    },
                },
                onUpdate({ editor }) {
                    self.content = editor.getHTML();
                    self.$dispatch('tiptap-updated', { content: editor.getHTML() });
                },
            });

            this.$watch('isRtl', (val) => {
                this.editor?.view.dom.setAttribute('dir', val ? 'rtl' : 'ltr');
            });
        },

        destroy() {
            this.editor?.destroy();
        },

        setLink() {
            const url = prompt('URL:');
            if (!url) return;
            this.editor.chain().focus().setLink({ href: url, target: '_blank', rel: 'noopener noreferrer' }).run();
        },

        insertImageUrl() {
            const url = prompt('Image URL:');
            if (url) this.editor.chain().focus().setImage({ src: url }).run();
        },

        setColor(color) {
            this.editor.chain().focus().setColor(color).run();
        },
    }));

    Alpine.data('emojiPicker', (wireModel) => ({
        picker: null,

        initPicker() {
            const trigger = this.$refs.emojiBtn;

            this.picker = createPopup({}, {
                referenceElement: trigger,
                triggerElement: trigger,
                position: 'top-start',
            });

            this.picker.addEventListener('emoji:select', (event) => {
                // Composers are <flux:textarea>, so look for the textarea first
                // (an earlier text input in the form may be a search box). The
                // textInput ref can sit in a nested x-data scope that $refs
                // can't see (inbox), so fall back to the component root.
                const scope = this.$refs.textInput ?? this.$root;
                const field = scope?.matches?.('textarea, input')
                    ? scope
                    : scope?.querySelector('textarea') ?? scope?.querySelector('input[type="text"], input:not([type])');
                if (!field) return;

                const start = field.selectionStart ?? field.value.length;
                const end = field.selectionEnd ?? field.value.length;
                field.value = field.value.slice(0, start) + event.emoji + field.value.slice(end);
                field.selectionStart = field.selectionEnd = start + event.emoji.length;
                field.dispatchEvent(new Event('input', { bubbles: true }));
                field.focus();
            });
        },

        togglePicker() {
            if (!this.picker) {
                this.initPicker();
            }
            this.picker.toggle();
        },
    }));
});
