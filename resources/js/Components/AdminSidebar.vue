<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Menu, X } from '@lucide/vue';
import { setLocale } from '../i18n';

const { t, locale } = useI18n();
const open = ref(false);

const navGroups = [
    { title: null, links: [{ href: '/admin/dashboard', label: 'dashboard.title' }] },
    {
        title: 'sidebar.production',
        links: [
            { href: '/admin/projects', label: 'admin_projects.title' },
            { href: '/admin/order-fabrications', label: 'admin_order_fabrications.title' },
        ],
    },
    {
        title: 'sidebar.questionnaires',
        links: [
            { href: '/admin/sections', label: 'admin_sections.title' },
            { href: '/admin/question-bank', label: 'admin_bank.title' },
            { href: '/admin/question-categories', label: 'admin_categories.title' },
        ],
    },
    { title: 'sidebar.administration', links: [{ href: '/admin/users', label: 'admin_users.title' }] },
];

function logout() {
    router.post('/logout');
}
</script>

<template>
    <button
        type="button"
        class="fixed left-4 top-4 z-40 rounded-full bg-white p-2 text-gray-600 shadow transition duration-100 ease-out hover:brightness-110 active:scale-90"
        :aria-label="t('common.menu')"
        @click="open = true"
    >
        <Menu class="h-5 w-5" />
    </button>

    <div v-if="open" class="fixed inset-0 z-50 bg-black/40" @click="open = false"></div>

    <aside
        class="fixed left-0 top-0 z-[60] flex h-full w-64 flex-col bg-white shadow-lg transition-transform duration-200 ease-out"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="flex items-center justify-between border-b px-4 py-3">
            <span class="text-sm font-semibold text-gray-800">{{ t('common.menu') }}</span>
            <button
                type="button"
                class="text-gray-400 transition duration-100 ease-out active:scale-90"
                :aria-label="t('common.close')"
                @click="open = false"
            >
                <X class="h-5 w-5" />
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-3">
            <div v-for="(group, index) in navGroups" :key="index" :class="index > 0 ? 'mt-3 border-t pt-3' : ''">
                <p v-if="group.title" class="px-3 pb-1 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    {{ t(group.title) }}
                </p>
                <div class="space-y-1">
                    <Link
                        v-for="link in group.links"
                        :key="link.href"
                        :href="link.href"
                        class="block rounded px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                        @click="open = false"
                    >
                        {{ t(link.label) }}
                    </Link>
                </div>
            </div>
        </nav>

        <div class="space-y-2 border-t p-2">
            <div class="flex gap-1 rounded-full bg-gray-100 p-1">
                <button
                    type="button"
                    class="flex-1 rounded-full px-3 py-1 text-sm transition duration-100 ease-out active:scale-95"
                    :class="locale === 'ca' ? 'bg-gray-800 text-white' : 'text-gray-600'"
                    @click="setLocale('ca')"
                >
                    {{ t('language.ca') }}
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-full px-3 py-1 text-sm transition duration-100 ease-out active:scale-95"
                    :class="locale === 'es' ? 'bg-gray-800 text-white' : 'text-gray-600'"
                    @click="setLocale('es')"
                >
                    {{ t('language.es') }}
                </button>
            </div>

            <Link
                href="/operari"
                class="block rounded px-3 py-2 text-sm text-gray-600 hover:bg-gray-50"
                @click="open = false"
            >
                {{ t('operari.login_title') }}
            </Link>

            <button
                type="button"
                class="block w-full rounded px-3 py-2 text-left text-sm text-gray-500 transition duration-100 ease-out hover:bg-gray-50 hover:text-gray-700 active:scale-[0.98]"
                @click="logout"
            >
                {{ t('auth.logout') }}
            </button>
        </div>
    </aside>
</template>
