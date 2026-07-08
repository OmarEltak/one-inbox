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
                // Get the Livewire input and append the emoji
                const input = this.$refs.textInput;
                if (input) {
                    // For Flux input components, find the actual input element inside
                    const actualInput = input.tagName === 'INPUT' ? input : input.querySelector('input');
                    if (actualInput) {
                        const start = actualInput.selectionStart ?? actualInput.value.length;
                        const end = actualInput.selectionEnd ?? actualInput.value.length;
                        const value = actualInput.value;
                        actualInput.value = value.slice(0, start) + event.emoji + value.slice(end);
                        actualInput.selectionStart = actualInput.selectionEnd = start + event.emoji.length;
                        actualInput.dispatchEvent(new Event('input', { bubbles: true }));
                        actualInput.focus();
                    }
                }
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
