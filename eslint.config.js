import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
    { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'bootstrap/ssr/**'] },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    prettier,
    {
        languageOptions: { globals: { ...globals.browser } },
        rules: {
            // Inertia page names like "Dashboard" are single words by design.
            'vue/multi-word-component-names': 'off',
        },
    },
];
