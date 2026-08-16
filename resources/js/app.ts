import { createInertiaApp } from '@inertiajs/svelte';
import AppLayout from '@/layouts/AppLayout.svelte';
import AuthLayout from '@/layouts/AuthLayout.svelte';
import PublicLayout from '@/layouts/PublicLayout.svelte';
import SettingsLayout from '@/layouts/settings/Layout.svelte';
import { initializeFlashToast } from '@/lib/flash-toast';
import { initializeTheme } from '@/lib/theme.svelte';

const appName = import.meta.env.VITE_APP_NAME || 'CashSense';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Home':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case ['Pricing', 'Contact', 'Docs'].includes(name):
                return PublicLayout;
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#6B1A2A',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
