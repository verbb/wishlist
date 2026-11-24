import { resolve } from 'node:path';

import { defineConfig } from 'vite';

const root = resolve(import.meta.dirname, 'src/web/assets/cp');

// Each entry is its own IIFE build, so Plugin Kit only loads on the pages that show its components.
const entries = {
    production: 'wishlist',
    'plugin-kit': 'plugin-kit',
};

export default defineConfig(({ mode }) => {
    const entry = entries[mode];

    if (!entry) {
        throw new Error(`Unknown asset build mode: ${mode}`);
    }

    return {
        root,
        build: {
            outDir: resolve(root, 'dist'),
            // The first build clears dist and the Plugin Kit build adds to it.
            emptyOutDir: mode === 'production',
            assetsDir: '',
            minify: 'oxc',
            // One stylesheet per entry, registered by its asset bundle, instead of styles injected from the script.
            cssCodeSplit: false,
            sourcemap: true,
            // Plugin Kit's web components need native class fields, so its bundle keeps Vite's modern default target.
            target: mode === 'production' ? 'es2015' : undefined,
            rolldownOptions: {
                input: resolve(root, `src/${entry}.js`),
                output: {
                    // Vite names the single stylesheet style.css, so it takes the entry's name instead.
                    assetFileNames: (asset) => (asset.names.some((name) => name.endsWith('.css')) ? `${entry}.css` : '[name][extname]'),
                    codeSplitting: false,
                    entryFileNames: '[name].js',
                    format: 'iife',
                },
            },
        },
    };
});
