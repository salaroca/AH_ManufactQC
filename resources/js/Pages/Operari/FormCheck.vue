<script setup>
import { onMounted, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import OperariNav from '../../Components/OperariNav.vue';
import ButtonGroup from '../../Components/ButtonGroup.vue';
import Button from '../../Components/Button.vue';
import DefectModal from './DefectModal.vue';
import PhotosModal from './PhotosModal.vue';
import { api } from '../../api';

const props = defineProps({
    equipmentId: { type: [Number, String], required: true },
});

const { t, locale } = useI18n();

// Agrupa les preguntes per categoria, seguint l'ordre del catàleg de categories.
function questionsByCategory(section) {
    const groups = new Map();
    for (const question of section.questions) {
        const category = question.category;
        if (!groups.has(category.id)) {
            groups.set(category.id, { category, questions: [] });
        }
        groups.get(category.id).questions.push(question);
    }

    return [...groups.values()].sort((a, b) => a.category.order - b.category.order || a.category.id - b.category.id);
}

const equipment = ref(null);
const project = ref(null);
const sections = ref([]);
const answers = reactive({});
const savedAnswerIds = reactive({});
const questionDefects = reactive({});
const observations = ref('');

const defectModal = reactive({ open: false, questionId: null, answerId: null, editingDefect: null });
const photosModal = reactive({ open: false });
const pendingMutations = ref(0);

let observationsTimer = null;

async function load() {
    const data = await api.get(`/operari/api/equipment/${props.equipmentId}`);

    equipment.value = data.equipment;
    project.value = data.equipment.project;
    sections.value = data.sections;
    observations.value = data.equipment.observations ?? '';

    for (const section of data.sections) {
        for (const question of section.questions) {
            if (question.answer) {
                answers[question.id] = question.answer.response;
                savedAnswerIds[question.id] = question.answer.id;
                questionDefects[question.id] = question.answer.defects ?? [];
            }
        }
    }
}

async function answerQuestion(question, response) {
    pendingMutations.value++;

    try {
        await answerQuestionRequest(question, response);
    } finally {
        pendingMutations.value--;
    }
}

async function answerQuestionRequest(question, response) {
    const previousResponse = answers[question.id];
    answers[question.id] = response;

    if (response === null) {
        const answerId = savedAnswerIds[question.id];

        if (answerId) {
            await api.delete(`/operari/api/answers/${answerId}`);
            delete savedAnswerIds[question.id];
        }

        return;
    }

    const answer = await api.post('/operari/api/answers', {
        equipment_id: props.equipmentId,
        question_id: question.id,
        response,
        language_chosen: locale.value,
    });

    savedAnswerIds[question.id] = answer.id;

    if (response === 'defect' && previousResponse !== 'defect') {
        openDefectModal(question);
    }
}

function openDefectModal(question) {
    defectModal.questionId = question.id;
    defectModal.answerId = savedAnswerIds[question.id];
    defectModal.editingDefect = null;
    defectModal.open = true;
}

function editDefect(question, defect) {
    defectModal.questionId = question.id;
    defectModal.answerId = savedAnswerIds[question.id];
    defectModal.editingDefect = defect;
    defectModal.open = true;
}

function onDefectSaved(defect) {
    const questionId = defectModal.questionId;
    const existing = questionDefects[questionId] ?? [];
    const index = existing.findIndex((item) => item.id === defect.id);

    questionDefects[questionId] = index === -1 ? [...existing, defect] : existing.map((item, i) => (i === index ? defect : item));
}

async function deleteDefect(question, defect) {
    if (!confirm(t('defect.delete_confirm'))) {
        return;
    }

    pendingMutations.value++;

    try {
        await api.delete(`/operari/api/defects/${defect.id}`);

        questionDefects[question.id] = (questionDefects[question.id] ?? []).filter((item) => item.id !== defect.id);
    } finally {
        pendingMutations.value--;
    }
}

function saveObservations() {
    clearTimeout(observationsTimer);
    observationsTimer = setTimeout(() => {
        api.patch(`/operari/api/equipment/${props.equipmentId}`, {
            observations: observations.value,
        });
    }, 500);
}

function openFinish() {
    photosModal.open = true;
}

function exitWithoutFinishing() {
    router.visit(`/operari/order-fabrications/${equipment.value.order_fabrication_id}/equipment-list`);
}

function onFinished() {
    router.visit(`/operari/order-fabrications/${equipment.value.order_fabrication_id}/equipment-list`);
}

onMounted(load);
</script>

<template>
    <LanguageSelector />

    <div v-if="equipment" class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-5xl space-y-6 rounded-lg bg-white p-6 shadow">
            <OperariNav :back-href="`/operari/order-fabrications/${equipment.order_fabrication_id}/equipment-list`" />

            <div>
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ project.sections.map((s) => s.name).join(' ') }}
                </h1>
                <p v-if="project.observations" class="mt-1 text-sm text-gray-500">
                    {{ t('form.global_observations') }}: {{ project.observations }}
                </p>
                <p class="mt-1 text-sm font-medium text-gray-600">{{ equipment.serie_number }}</p>
            </div>

            <div v-for="section in sections" :key="section.id" class="space-y-4">
                <h2 class="border-b pb-1 text-sm font-semibold uppercase text-gray-500">{{ section.name }}</h2>

                <div v-for="group in questionsByCategory(section)" :key="group.category.id" class="space-y-3">
                    <h3 class="text-xs font-semibold uppercase text-gray-400">{{ group.category.name }}</h3>

                    <div v-for="question in group.questions" :key="question.id" class="space-y-2">
                        <p class="text-sm text-gray-700">{{ question.text }}</p>
                        <ButtonGroup
                            :model-value="answers[question.id] ?? null"
                            @update:model-value="(value) => answerQuestion(question, value)"
                        />

                        <div
                            v-if="questionDefects[question.id]?.length"
                            class="space-y-2 rounded border border-red-200 bg-red-50 p-3"
                        >
                            <div
                                v-for="defect in questionDefects[question.id]"
                                :key="defect.id"
                                class="space-y-0.5 text-sm text-gray-700"
                            >
                                <div class="flex items-center justify-between">
                                    <p class="font-medium">{{ t(`defect.type_${defect.tipo}`) }}</p>
                                    <div class="flex gap-1">
                                        <Button variant="ghost" @click="editDefect(question, defect)">
                                            {{ t('defect.edit') }}
                                        </Button>
                                        <Button variant="ghost-danger" @click="deleteDefect(question, defect)">
                                            {{ t('defect.delete') }}
                                        </Button>
                                    </div>
                                </div>
                                <p v-if="defect.observation">{{ defect.observation }}</p>
                                <p v-if="defect.responsibility" class="text-gray-500">
                                    {{ t('defect.responsibility') }}: {{ t(`defect.responsibility_${defect.responsibility}`) }}
                                </p>
                                <p v-if="defect.responsible_user" class="text-gray-500">
                                    {{ t('defect.responsible_user') }}: {{ defect.responsible_user.name }}
                                </p>
                                <p v-if="defect.actions" class="font-medium text-green-700">
                                    {{ t('defect.actions') }}: {{ defect.actions }}
                                </p>
                            </div>
                            <Button v-if="answers[question.id] === 'defect'" variant="outline" @click="openDefectModal(question)">
                                {{ t('defect.add_another') }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">
                    {{ t('form.equipment_observations') }}
                </label>
                <textarea
                    v-model="observations"
                    :placeholder="t('form.equipment_observations_placeholder')"
                    class="w-full rounded border border-gray-300 px-3 py-2"
                    rows="3"
                    @input="saveObservations"
                />
            </div>

            <Button class="w-full" :disabled="pendingMutations > 0" @click="openFinish">
                {{ t('form.finish') }}
            </Button>
            <Button class="w-full" variant="outline" @click="exitWithoutFinishing">
                {{ t('form.exit_without_finishing') }}
            </Button>
        </div>
    </div>

    <DefectModal
        :open="defectModal.open"
        :equipment-id="equipmentId"
        :answer-id="defectModal.answerId"
        :editing-defect="defectModal.editingDefect"
        @close="defectModal.open = false"
        @saved="onDefectSaved"
    />

    <PhotosModal
        :open="photosModal.open"
        :equipment-id="equipmentId"
        @close="photosModal.open = false"
        @finished="onFinished"
    />
</template>
