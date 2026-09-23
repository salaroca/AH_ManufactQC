<script setup>
import { onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import OperariNav from '../../Components/OperariNav.vue';
import { api } from '../../api';

const { t } = useI18n();

const query = ref('');
const orderFabrications = ref([]);
const searching = ref(false);

let debounceHandle = null;

async function load(value) {
    searching.value = true;
    orderFabrications.value = await api.get(`/operari/api/order-fabrications?q=${encodeURIComponent(value)}`);
    searching.value = false;
}

watch(query, (value) => {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => load(value), 250);
});

function statusOf(orderFabrication) {
    if (orderFabrication.equipment_count === 0) {
        return { label: t('selector.no_equipment'), classes: 'bg-gray-100 text-gray-500' };
    }

    if (orderFabrication.pending_equipment_count === 0) {
        return { label: t('selector.finished'), classes: 'bg-green-100 text-green-700' };
    }

    if (orderFabrication.started_equipment_count === 0) {
        return { label: t('selector.not_started'), classes: 'bg-sky-100 text-sky-800' };
    }

    return {
        label: t('selector.pending_count', { count: orderFabrication.pending_equipment_count }, orderFabrication.pending_equipment_count),
        classes: 'bg-amber-100 text-amber-800',
    };
}

function selectOrderFabrication(orderFabrication) {
    router.visit(`/operari/order-fabrications/${orderFabrication.id}/equipment-list`);
}

onMounted(() => load(''));
</script>

<template>
    <LanguageSelector />

    <div class="flex h-dvh flex-col bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto flex max-h-full w-full max-w-5xl flex-col gap-4 rounded-lg bg-white p-6 shadow">
            <OperariNav />

            <h1 class="text-lg font-semibold text-gray-800">{{ t('selector.title') }}</h1>

            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ t('selector.order_number') }}</label>
                <input v-model="query" type="text" class="w-full rounded border border-gray-300 px-3 py-2" />
            </div>

            <ul v-if="orderFabrications.length" class="min-h-0 divide-y overflow-y-auto rounded border border-gray-200">
                <li
                    v-for="of in orderFabrications"
                    :key="of.id"
                    class="cursor-pointer space-y-0.5 px-3 py-2 hover:bg-gray-100"
                    @click="selectOrderFabrication(of)"
                >
                    <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
                        <span class="font-medium">{{ of.number }}</span>
                        <span
                            class="shrink-0 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="statusOf(of).classes"
                        >
                            {{ statusOf(of).label }}
                        </span>
                    </div>
                    <div class="text-sm">
                        <span class="text-gray-500">{{ of.project.number }} ({{ of.project.family.name }})</span>
                        <span class="text-gray-400"> · {{ t('selector.equipment_count', { count: of.equipment_count }) }}</span>
                    </div>
                    <div class="truncate text-xs text-gray-500">
                        {{ of.project.sections.map((s) => s.name).join(' ') }}
                    </div>
                </li>
            </ul>
            <p v-else-if="!searching" class="text-sm text-gray-400">
                {{ t('selector.no_results') }}
            </p>
        </div>
    </div>
</template>
