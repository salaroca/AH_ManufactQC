import { describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { i18n } from '../../../i18n';

const apiGet = vi.fn();

vi.mock('../../../api', () => ({
    api: { get: (...args) => apiGet(...args) },
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { visit: vi.fn(), post: vi.fn() },
    usePage: () => ({ props: { auth: { user: { role: 'operari' } } } }),
    Link: { template: '<a><slot /></a>' },
}));

import ProjectSelector from '../../../Pages/Operari/ProjectSelector.vue';

function orderFabrication(overrides) {
    return {
        id: 1,
        number: '2026/01/0000001',
        equipment_count: 3,
        pending_equipment_count: 0,
        started_equipment_count: 1,
        project: { number: '1400C0000.00', family: { name: 'DB2' }, sections: [{ name: 'QUALITAT' }] },
        ...overrides,
    };
}

describe('ProjectSelector page', () => {
    it('lists order fabrications on load, each with whether it is finished or how many equipment are left', async () => {
        i18n.global.locale.value = 'ca';
        apiGet.mockResolvedValueOnce([
            orderFabrication({ id: 1, number: 'OF-PENDENT', pending_equipment_count: 2 }),
            orderFabrication({ id: 2, number: 'OF-UN', pending_equipment_count: 1 }),
            orderFabrication({ id: 3, number: 'OF-ACABADA', pending_equipment_count: 0 }),
            orderFabrication({ id: 4, number: 'OF-BUIDA', equipment_count: 0, pending_equipment_count: 0 }),
            orderFabrication({ id: 5, number: 'OF-NOVA', pending_equipment_count: 3, started_equipment_count: 0 }),
        ]);

        const wrapper = mount(ProjectSelector, { global: { plugins: [i18n] } });
        await flushPromises();

        expect(apiGet).toHaveBeenCalledWith('/operari/api/order-fabrications?q=');
        const rows = wrapper.findAll('li').map((li) => li.text());
        expect(rows[0]).toContain('OF-PENDENT');
        expect(rows[0]).toContain('Falten 2 equips per revisar');
        expect(rows[1]).toContain('Falta 1 equip per revisar');
        expect(rows[2]).toContain('Acabada');
        expect(rows[3]).toContain('Sense equips');
        expect(rows[4]).toContain('Per començar');
        expect(rows[4]).not.toContain('Falten');
    });
});
