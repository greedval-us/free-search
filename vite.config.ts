import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { existsSync } from 'node:fs';
import { defineConfig } from 'vite';

const resolveWayfinderCommand = () => {
    if (process.env.WAYFINDER_COMMAND) {
        return process.env.WAYFINDER_COMMAND;
    }

    if (process.env.WAYFINDER_PHP) {
        return `"${process.env.WAYFINDER_PHP}" artisan wayfinder:generate`;
    }

    const osPanelPhp83 = 'D:/Program/OSPanel/modules/PHP-8.3/php.exe';

    if (existsSync(osPanelPhp83)) {
        return `"${osPanelPhp83}" artisan wayfinder:generate`;
    }

    return 'php artisan wayfinder:generate';
};

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
            command: resolveWayfinderCommand(),
        }),
    ],
});
