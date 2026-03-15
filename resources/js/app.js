import './echo';
import { createPopup } from '@picmo/popup-picker';

document.addEventListener('alpine:init', () => {
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
