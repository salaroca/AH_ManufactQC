<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { useI18n } from 'vue-i18n';
import AdminSidebar from '../../Components/AdminSidebar.vue';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const props = defineProps({
    sectionId: { type: [Number, String], required: true },
});

const { t } = useI18n();

const section = ref(null);
const questions = ref([]);
const categories = ref([]);

const formOpen = ref(false);
const editingId = ref(null);
const errors = ref({});
const saving = ref(false);

function blankForm() {
    return { text: '', question_category_id: categories.value[0]?.id ?? null, is_required: true, save_to_bank: false };
}

const form = reactive(blankForm());
const editingFromBank = ref(false);

const bank = reactive({ open: false, templates: [], selected: [], search: '', category: '', adding: false });

// Plantilles del banc que aquesta secció ja té copiades (no es poden tornar a afegir).
const templateIdsInSection = computed(() => new Set(questions.value.map((question) => question.question_template_id).filter(Boolean)));

const filteredBankTemplates = computed(() => {
    const needle = bank.search.trim().toLowerCase();

    return bank.templates.filter(
        (template) =>
            (!bank.category || template.question_category_id === bank.category) &&
            (!needle || template.text.toLowerCase().includes(needle)),
    );
});

const selectableVisibleIds = computed(() =>
    filteredBankTemplates.value.filter((template) => !templateIdsInSection.value.has(template.id)).map((template) => template.id),
);

async function openBank() {
    Object.assign(bank, { open: true, selected: [], search: '', category: '' });
    bank.templates = await api.get('/api/question-templates');
}

function selectAllVisible() {
    bank.selected = [...new Set([...bank.selected, ...selectableVisibleIds.value])];
}

async function addFromBank() {
    bank.adding = true;

    try {
        questions.value = await api.post(`/api/sections/${props.sectionId}/questions/from-templates`, {
            template_ids: bank.selected,
        });
        bank.open = false;
    } finally {
        bank.adding = false;
    }
}

async function load() {
    [section.value, questions.value, categories.value] = await Promise.all([
        api.get(`/api/sections/${props.sectionId}`),
        api.get(`/api/questions?section_id=${props.sectionId}`),
        api.get('/api/question-categories'),
    ]);
}

function openCreate() {
    editingId.value = null;
    editingFromBank.value = false;
    errors.value = {};
    Object.assign(form, blankForm());
    formOpen.value = true;
}

function openEdit(question) {
    editingId.value = question.id;
    editingFromBank.value = question.question_template_id !== null;
    errors.value = {};
    Object.assign(form, {
        text: question.text,
        question_category_id: question.question_category_id,
        is_required: question.is_required,
        save_to_bank: false,
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
            await api.put(`/api/questions/${editingId.value}`, { ...form, section_id: props.sectionId });
        } else {
            await api.post('/api/questions', { ...form, section_id: props.sectionId, order: questions.value.length });
        }
        formOpen.value = false;
        await load();
    } catch (error) {
        errors.value = error.data?.errors ?? {};
    } finally {
        saving.value = false;
    }
}

async function remove(question) {
    if (!confirm(t('admin_questions.delete_confirm'))) {
        return;
    }

    await api.delete(`/api/questions/${question.id}`);
    await load();
}

async function onReorder() {
    questions.value = await api.post(`/api/sections/${props.sectionId}/questions/reorder`, {
        question_ids: questions.value.map((question) => question.id),
    });
}

onMounted(load);
</script>

<template>
    <AdminSidebar />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-7xl space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ t('admin_questions.title') }} — {{ section?.name }}
                </h1>
                <div class="flex gap-2">
                    <Button variant="outline" @click="openBank">
                        {{ t('admin_questions.add_from_bank') }}
                    </Button>
                    <Button @click="openCreate">
                        {{ t('admin_questions.add') }}
                    </Button>
                </div>
            </div>

            <div class="overflow-x-auto rounded-lg bg-white shadow">
                <div class="min-w-[720px]">
                    <div class="flex items-center gap-3 border-b px-4 py-2 text-sm text-gray-500">
                        <span class="w-4"></span>
                        <span class="flex-1">{{ t('admin_questions.text') }}</span>
                        <span class="w-36 shrink-0">{{ t('admin_questions.category') }}</span>
                        <span class="ml-8 w-48 shrink-0"></span>
                    </div>
                    <VueDraggable
                        v-model="questions"
                        :animation="200"
                        handle=".drag-handle"
                        ghost-class="opacity-40"
                        @end="onReorder"
                    >
                        <div v-for="question in questions" :key="question.id" class="flex items-center gap-3 border-b px-4 py-2">
                            <span class="drag-handle w-4 cursor-move text-gray-300">⠿</span>
                            <span class="flex-1 text-sm font-medium">{{ question.text }}</span>
                            <span class="w-36 shrink-0 text-sm text-gray-600">{{ question.category?.name }}</span>
                            <span class="ml-8 flex w-48 shrink-0 justify-end gap-4 whitespace-nowrap">
                                <Button variant="ghost" @click="openEdit(question)">
                                    {{ t('admin_questions.edit') }}
                                </Button>
                                <Button variant="ghost-danger" @click="remove(question)">
                                    {{ t('admin_questions.delete') }}
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
                    {{ editingId ? t('admin_questions.edit') : t('admin_questions.add') }}
                </h2>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_questions.text') }}</label>
                    <textarea v-model="form.text" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
                    <p v-if="errors.text" class="mt-1 text-sm text-red-600">{{ errors.text[0] }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('admin_questions.category') }}</label>
                    <select v-model="form.question_category_id" class="w-full rounded border border-gray-300 px-3 py-2">
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>
                    <p v-if="errors.question_category_id" class="mt-1 text-sm text-red-600">{{ errors.question_category_id[0] }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.is_required" />
                    {{ t('admin_questions.required') }}
                </label>

                <p v-if="editingFromBank" class="text-sm text-gray-500">{{ t('admin_questions.in_bank') }}</p>
                <label v-else class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" v-model="form.save_to_bank" />
                    {{ t('admin_questions.save_to_bank') }}
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="closeForm">
                        {{ t('admin_questions.cancel') }}
                    </Button>
                    <Button :disabled="saving" @click="save">
                        {{ t('admin_questions.save') }}
                    </Button>
                </div>
            </div>
        </div>
        <div v-if="bank.open" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
            <div class="flex max-h-[85vh] w-full max-w-2xl flex-col gap-3 rounded-lg bg-white p-6 shadow-lg">
                <h2 class="text-base font-semibold text-gray-800">{{ t('admin_questions.bank_modal_title') }}</h2>

                <p v-if="!bank.templates.length" class="text-sm text-gray-500">{{ t('admin_questions.bank_empty') }}</p>

                <template v-else>
                    <div class="flex flex-wrap gap-2">
                        <input
                            v-model="bank.search"
                            type="search"
                            :placeholder="t('admin_bank.search')"
                            class="min-w-0 flex-1 rounded border border-gray-300 px-3 py-2 text-sm"
                        />
                        <select v-model="bank.category" class="rounded border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ t('admin_bank.all_categories') }}</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <Button variant="ghost" :disabled="!selectableVisibleIds.length" @click="selectAllVisible">
                            {{ t('admin_questions.select_all_visible') }}
                        </Button>
                    </div>

                    <ul class="min-h-0 flex-1 divide-y overflow-y-auto rounded border border-gray-200">
                        <li v-for="template in filteredBankTemplates" :key="template.id">
                            <label
                                class="flex items-center gap-3 px-3 py-2 text-sm"
                                :class="templateIdsInSection.has(template.id) ? 'text-gray-400' : 'cursor-pointer hover:bg-gray-50'"
                            >
                                <input
                                    v-if="templateIdsInSection.has(template.id)"
                                    type="checkbox"
                                    checked
                                    disabled
                                />
                                <input v-else v-model="bank.selected" type="checkbox" :value="template.id" />
                                <span class="flex-1">{{ template.text }}</span>
                                <span class="shrink-0 text-xs text-gray-500">
                                    {{ templateIdsInSection.has(template.id) ? t('admin_questions.already_added') : template.category?.name }}
                                </span>
                            </label>
                        </li>
                        <li v-if="!filteredBankTemplates.length" class="px-3 py-4 text-center text-sm text-gray-400">
                            {{ t('admin_bank.no_matches') }}
                        </li>
                    </ul>
                </template>

                <div class="flex justify-end gap-2 pt-2">
                    <Button variant="ghost" @click="bank.open = false">
                        {{ t('admin_questions.cancel') }}
                    </Button>
                    <Button :disabled="!bank.selected.length || bank.adding" @click="addFromBank">
                        {{ t('admin_questions.bank_add', { count: bank.selected.length }) }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
