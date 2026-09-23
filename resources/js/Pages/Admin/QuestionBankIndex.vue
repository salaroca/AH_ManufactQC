<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const { t } = useI18n();

const templates = ref([]);
const categories = ref([]);
const search = ref('');
const categoryFilter = ref('');

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return { text: '', question_category_id: categories.value[0]?.id ?? null, is_required: true };
}

const form = reactive(blankForm());

const filteredTemplates = computed(() => {
    const needle = search.value.trim().toLowerCase();

    return templates.value.filter(
        (template) =>
            (!categoryFilter.value || template.question_category_id === categoryFilter.value) &&
            (!needle || template.text.toLowerCase().includes(needle)),
    );
});

async function load() {
    [templates.value, categories.value] = await Promise.all([
        api.get('/api/question-templates'),
        api.get('/api/question-categories'),
    ]);
}

function openCreate() {
    editingId.value = null;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(template) {
    editingId.value = template.id;
    errors.value = {};
    Object.assign(form, {
        text: template.text,
        question_category_id: template.question_category_id,
        is_required: template.is_required,
    });
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
            await api.put(`/api/question-templates/${editingId.value}`, form);
        } else {
            await api.post('/api/question-templates', form);
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(template) {
    if (!confirm(t('admin_bank.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/question-templates/${template.id}`);
    await load();
}

onMounted(load);
</script>

<template>
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">{{ t('admin_bank.title') }}</h1>
                <Button @click="openCreate">
                    {{ t('admin_bank.add') }}
                </Button>
            </div>

            <p class="text-sm text-gray-500">{{ t('admin_bank.hint') }}</p>

            <div class="flex flex-wrap gap-2">
                <input
                    v-model="search"
                    type="search"
                    :placeholder="t('admin_bank.search')"
                    class="min-w-0 flex-1 rounded border border-gray-300 px-3 py-2 text-sm"
                />
                <select v-model="categoryFilter" class="rounded border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ t('admin_bank.all_categories') }}</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="px-4 py-2 font-normal">{{ t('admin_bank.text') }}</th>
                            <th class="w-44 px-4 py-2 font-normal">{{ t('admin_bank.category') }}</th>
                            <th class="w-28 px-4 py-2 font-normal">{{ t('admin_bank.required') }}</th>
                            <th class="w-24 px-4 py-2 text-right font-normal">{{ t('admin_bank.used_in') }}</th>
                            <th class="w-56 px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="template in filteredTemplates" :key="template.id" class="border-b">
                            <td class="px-4 py-2 font-medium">{{ template.text }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ template.category?.name }}</td>
                            <td class="px-4 py-2 text-gray-600">
                                {{ template.is_required ? t('admin_bank.yes') : t('admin_bank.no') }}
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ template.questions_count }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-right">
                                <span class="inline-flex gap-4">
                                    <Button variant="ghost" @click="openEdit(template)">
                                        {{ t('admin_bank.edit') }}
                                    </Button>
                                    <Button variant="ghost-danger" @click="remove(template)">
                                        {{ t('admin_bank.delete') }}
                                    </Button>
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!filteredTemplates.length">
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                                {{ templates.length ? t('admin_bank.no_matches') : t('admin_bank.empty') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="formOpen" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
            <div class="w-full max-w-md space-y-3 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ editingId ? t('admin_bank.edit') : t('admin_bank.add') }}
                </h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_bank.text') }}</label>
                    <textarea v-model="form.text" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                    <p v-if="errors.text" class="mt-1 text-sm text-red-600">{{ errors.text[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_bank.category') }}</label>
                    <select v-model="form.question_category_id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <p v-if="errors.question_category_id" class="mt-1 text-sm text-red-600">{{ errors.question_category_id[0] }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.is_required" />
                    {{ t('admin_bank.required') }}
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_bank.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_bank.save') }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
