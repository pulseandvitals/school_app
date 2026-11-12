<script setup>
import Modal from '@/Components/Modal.vue';
import AppButton from './AppButton.vue';

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    message: { type: String, default: '' },
    confirmLabel: { type: String, default: 'Confirm' },
    processing: { type: Boolean, default: false },
    danger: { type: Boolean, default: true },
});

defineEmits(['close', 'confirm']);
</script>

<template>
    <Modal :show="show" max-width="md" @close="$emit('close')">
        <div class="p-6">
            <h2 class="font-display text-xl font-semibold text-slate-800 dark:text-slate-100">{{ title }}</h2>
            <p v-if="message" class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ message }}</p>
            <slot />
            <div class="mt-6 flex justify-end gap-2">
                <AppButton variant="secondary" @click="$emit('close')">Cancel</AppButton>
                <AppButton :variant="danger ? 'danger' : 'primary'" :disabled="processing" @click="$emit('confirm')">
                    {{ confirmLabel }}
                </AppButton>
            </div>
        </div>
    </Modal>
</template>
