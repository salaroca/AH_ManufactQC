<script setup>
import { onMounted, reactive, ref } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import FormField from '../../Components/FormField.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const { t } = useI18n();

const categories = ref([]);
const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const deleteError = ref('');
const saving = ref(false);

function blankForm() {
    return { name: '' };
}

const form = reactive(blankForm());

async function load() {
    categories.value = await api.get('/api/question-categories');
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(category) {
    editingId.value = category.id;
    errors.value = {};
    Object.assign(form, { name: category.name });
    formOpen.value = true;
}

function closeForm() {
    formOpen.value = false;
}

async function save() {
    saving.value = true;
    errors.value = {};

    try {
        if (editingId.value) {
            await api.put(`/api/question-categories/${editingId.value}`, form);
        } else {
            await api.post('/api/question-categories', form);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(category) {
    if (!confirm(t('admin_categories.delete_confirm'))) {
        return;
    }

    deleteError.value = '';

    try {
        await api.delete(`/api/question-categories/${category.id}`);
        await load();
    } catch (error) {
        deleteError.value = error.data?.errors?.category?.[0] ?? error.data?.message ?? '';
    }
}

async function onReorder() {
    categories.value = await api.post('/api/question-categories/reorder', {
        category_ids: categories.value.map((category) => category.id),
    });
}

onMounted(load);
</script>

<template>
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_categories.title') }}</h1>
                <Button @click="openCreate">
                    {{ t('admin_categories.add') }}
                </Button>
            </div>

            <p class="text-sm text-gray-500">{{ t('admin_categories.reorder_hint') }}</p>
            <p v-if="deleteError" class="text-sm text-red-600">{{ deleteError }}</p>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <div class="min-w-[560px]">
                    <div class="flex items-center gap-3 border-b px-4 py-2 text-sm text-gray-500">
                        <span class="w-4"></span>
                        <span class="flex-1">{{ t('admin_categories.name') }}</span>
                        <span class="w-24 shrink-0 text-right">{{ t('admin_categories.questions_count') }}</span>
                        <span class="ml-8 w-48 shrink-0"></span>
                    </div>
                    <VueDraggable
                        v-model="categories"
                        :animation="200"
                        handle=".drag-handle"
                        ghost-class="opacity-40"
                        @end="onReorder"
                    >
                        <div v-for="category in categories" :key="category.id" class="flex items-center gap-3 border-b px-4 py-2">
                            <span class="drag-handle w-4 cursor-move text-gray-300">⠿</span>
                            <span class="flex-1 text-sm font-medium">{{ category.name }}</span>
                            <span class="w-24 shrink-0 text-right text-sm text-gray-600">{{ category.questions_count }}</span>
                            <span class="ml-8 flex w-48 shrink-0 justify-end gap-4 whitespace-nowrap">
                                <Button variant="ghost" @click="openEdit(category)">
                                    {{ t('admin_categories.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(category)">
                                    {{ t('admin_categories.delete') }}
                                </Button>
                            </span>
                        </div>
                    </VueDraggable>
                </div>
            </div>
        </div>

        <div v-if="formOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md space-y-3 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ editingId ? t('admin_categories.edit') : t('admin_categories.add') }}
                </h2>

                <FormField v-model="form.name" :label="t('admin_categories.name')" :error="errors.name?.[0]" />

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_categories.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_categories.save') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
