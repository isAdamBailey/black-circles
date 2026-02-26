import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from 'eslint-config-prettier/flat';
import globals from 'globals';

export default [
    { ignores: ['node_modules', 'vendor', 'public/build', 'storage', 'bootstrap/cache', '*.min.js'] },
    js.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['**/*.{js,vue}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                route: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
    eslintConfigPrettier,
];
