<script setup>
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import Button from '../../Components/Button.vue';
import { api } from '../../api';

const props = defineProps({
    open: { type: Boolean, default: false },
    equipmentId: { type: [Number, String], required: true },
    answerId: { type: [Number, String], default: null },
    editingDefect: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);

const { t } = useI18n();

const types = ['visual', 'dimensional', 'funcional'];
const responsibilities = ['produccio', 'proveidor', 'disseny'];

function blankForm() {
    return {
        tipo: types[0],
        observation: '',
        responsibility: responsibilities[0],
        responsible_user_id: null,
        actions: '',
    };
}

const form = reactive(blankForm());
const saving = reactive({ value: false });
const error = ref('');
const productionOperators = ref([]);

watch(
    () => props.open,
    async (isOpen) => {
        if (!isOpen) {
            return;
        }

        error.value = '';

        Object.assign(
            form,
            props.editingDefect
                ? {
                      tipo: props.editingDefect.tipo,
                      observation: props.editingDefect.observation ?? '',
                      responsibility: props.editingDefect.responsibility ?? responsibilities[0],
                      responsible_user_id: props.editingDefect.responsible_user_id ?? null,
                      actions: props.editingDefect.actions ?? '',
                  }
                : blankForm(),
        );

        productionOperators.value = await api.get('/operari/api/production-operators');
    },
);

watch(
    () => form.responsibility,
    (value) => {
        if (value !== 'produccio') {
            form.responsible_user_id = null;
        }
    },
);

async function save(keepOpen) {
    saving.value = true;
    error.value = '';

    try {
        const defect = props.editingDefect
            ? await api.put(`/operari/api/defects/${props.editingDefect.id}`, form)
            : await api.post('/operari/api/defects', {
                  equipment_id: props.equipmentId,
                  answer_id: props.answerId,
                  ...form,
              });

        emit('saved', defect);

        if (keepOpen && !props.editingDefect) {
            Object.assign(form, blankForm());
        } else {
            emit('close');
        }
    } catch (err) {
        error.value = err.data?.errors?.responsible_user_id?.[0] ?? err.data?.message ?? t('defect.error');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center bg-black/40 px-4">
        <div class="w-full max-w-lg space-y-3 rounded-lg bg-white p-6 shadow-lg">
            <h2 class="text-base font-semibold text-gray-800">
                {{ editingDefect ? t('defect.edit_title') : t('defect.title') }}
            </h2>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.type') }}</label>
                <select v-model="form.tipo" class="w-full rounded border border-gray-300 px-3 py-2">
                    <option v-for="type in types" :key="type" :value="type">{{ t(`defect.type_${type}`) }}</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.observation') }}</label>
                <textarea v-model="form.observation" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.responsibility') }}</label>
                <select v-model="form.responsibility" class="w-full rounded border border-gray-300 px-3 py-2">
                    <option v-for="item in responsibilities" :key="item" :value="item">
                        {{ t(`defect.responsibility_${item}`) }}
                    </option>
                </select>
            </div>

            <div v-if="form.responsibility === 'produccio'">
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.responsible_user') }}</label>
                <select v-model="form.responsible_user_id" class="w-full rounded border border-gray-300 px-3 py-2">
                    <option :value="null" disabled>{{ t('defect.responsible_user_placeholder') }}</option>
                    <option v-for="operator in productionOperators" :key="operator.id" :value="operator.id">
                        {{ operator.name }}
                    </option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('defect.actions') }}</label>
                <textarea v-model="form.actions" class="w-full rounded border border-gray-300 px-3 py-2" rows="2" />
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

            <div class="flex flex-col gap-2 pt-2">
                <Button variant="danger" class="w-full" :disabled="saving.value" @click="save(false)">
                    {{ t('defect.save') }}
                </Button>
                <Button v-if="!editingDefect" variant="outline" class="w-full" :disabled="saving.value" @click="save(true)">
                    {{ t('defect.add_another') }}
                </Button>
                <Button variant="ghost" @click="emit('close')">
                    {{ t('defect.cancel') }}
                </Button>
            </div>
        </div>
    </div>
</template>
