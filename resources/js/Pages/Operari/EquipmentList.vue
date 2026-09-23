<script setup>
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LanguageSelector from '../../Components/LanguageSelector.vue';
import OperariNav from '../../Components/OperariNav.vue';
import { api } from '../../api';

const props = defineProps({
    orderFabricationId: { type: [Number, String], required: true },
});

const { t } = useI18n();

const orderFabrication = ref(null);
const equipment = ref([]);

const statusClasses = {
    pending: 'bg-gray-200 text-gray-700',
    missing_answers: 'bg-amber-100 text-amber-800',
    pending_defect: 'bg-red-100 text-red-800',
    ok: 'bg-green-100 text-green-800',
    defect: 'bg-emerald-100 text-emerald-800',
};

async function load() {
    const data = await api.get(`/operari/api/order-fabrications/${props.orderFabricationId}/equipment`);
    orderFabrication.value = data.order_fabrication;
    equipment.value = data.equipment;
}

function openCheck(item) {
    router.visit(`/operari/equipment/${item.id}/check`);
}

onMounted(load);
</script>

<template>
    <LanguageSelector />

    <div class="min-h-screen bg-gray-50 px-4 pb-10 pt-16">
        <div class="mx-auto max-w-5xl space-y-4 rounded-lg bg-white p-6 shadow">
            <OperariNav back-href="/operari" />

            <div v-if="orderFabrication">
                <h1 class="text-lg font-semibold text-gray-800">
                    {{ orderFabrication.project.number }} — {{ orderFabrication.project.family.name }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ orderFabrication.project.sections.map((s) => s.name).join(' ') }}
                </p>
                <p class="text-sm text-gray-500">{{ t('selector.order_number') }}: {{ orderFabrication.number }}</p>
            </div>

            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase text-gray-500">{{ t('list.title') }}</h2>
                <span class="text-sm text-gray-400">{{ t('list.equipment_count', { count: equipment.length }) }}</span>
            </div>

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-gray-500">
                        <th class="py-2">{{ t('list.serie_number') }}</th>
                        <th class="py-2">{{ t('list.status') }}</th>
                        <th class="py-2">{{ t('list.defects') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="item in equipment"
                        :key="item.id"
                        class="cursor-pointer border-b hover:bg-gray-50"
                        @click="openCheck(item)"
                    >
                        <td class="py-2 font-medium">{{ item.serie_number }}</td>
                        <td class="py-2">
                            <span class="rounded-full px-2 py-1 text-xs" :class="statusClasses[item.status]">
                                {{ t(`status.${item.status}`) }}
                            </span>
                        </td>
                        <td class="py-2">{{ item.defects_count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
