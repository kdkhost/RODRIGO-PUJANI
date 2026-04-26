import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin.css',
                'resources/js/admin.js',
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return;
                    }

                    if (id.includes('@fullcalendar') || id.includes('chart.js')) {
                        return 'vendor-analytics';
                    }

                    if (id.includes('filepond') || id.includes('inputmask')) {
                        return 'vendor-forms';
                    }

                    if (
                        id.includes('summernote')
                        || id.includes('jquery')
                        || id.includes('bootstrap')
                        || id.includes('admin-lte')
                        || id.includes('@popperjs')
                        || id.includes('toastr')
                        || id.includes('sweetalert2')
                    ) {
                        return 'vendor-admin-ui';
                    }

                    return 'vendor-core';
                },
            },
        },
    },
});
