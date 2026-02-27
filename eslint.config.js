import js from "@eslint/js";

export default [
    {
        ignores: [
            "node_modules/**",
            "vendor/**",
            "public/build/**",
            "bootstrap/cache/**",
            "storage/**",
        ],
    },
    {
        languageOptions: {
            globals: {
                document: "readonly",
                window: "readonly",
            },
        },
    },
    js.configs.recommended,
];
