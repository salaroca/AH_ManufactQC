import { describe, expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { i18n } from '../../../i18n';

const apiGet = vi.fn();
const apiPost = vi.fn();

vi.mock('../../../api', () => ({
    api: {
        get: (...args) => apiGet(...args),
        post: (...args) => apiPost(...args),
        patch: vi.fn(),
        postForm: vi.fn(),
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    router: { visit: vi.fn(), post: vi.fn() },
    Link: { template: '<a><slot /></a>' },
    usePage: () => ({ props: { auth: { user: null } } }),
}));

const { default: FormCheck } = await import('../../../Pages/Operari/FormCheck.vue');

function equipmentResponse() {
    return {
        equipment: {
            id: 1,
            serie_number: 'SN-0001',
            observations: null,
            order_fabrication_id: 5,
            project: {
                observations: null,
                sections: [{ id: 1, name: 'QUALITAT' }],
            },
        },
        sections: [
            {
                id: 1,
                name: 'QUALITAT',
                questions: [
                    { id: 10, text: 'Acabat correcte?', category: { id: 1, name: 'Estètica', order: 0 }, order: 1, is_required: true, answer: null },
                ],
            },
        ],
    };
}

describe('FormCheck page', () => {
    it('loads equipment data and renders sections with questions', async () => {
        apiGet.mockResolvedValueOnce(equipmentResponse());

        const wrapper = mount(FormCheck, {
            props: { equipmentId: 1 },
            global: { plugins: [i18n] },
        });

        await flushPromises();

        expect(wrapper.text()).toContain('QUALITAT');
        expect(wrapper.text()).toContain('Acabat correcte?');
        expect(wrapper.text()).toContain('Estètica');
    });

    it('saves an answer when a response button is clicked', async () => {
        apiGet.mockResolvedValueOnce(equipmentResponse());
        apiPost.mockResolvedValueOnce({ id: 99 });

        const wrapper = mount(FormCheck, {
            props: { equipmentId: 1 },
            global: { plugins: [i18n] },
        });

        await flushPromises();

        const yesButton = wrapper.findAll('button').find((btn) => btn.text() === 'Sí');
        await yesButton.trigger('click');
        await flushPromises();

        expect(apiPost).toHaveBeenCalledWith('/operari/api/answers', expect.objectContaining({
            equipment_id: 1,
            question_id: 10,
            response: 'yes',
        }));
    });
});
