module.exports = {
    env: {
        browser: false,
        node: true,
    },
    parser: "@typescript-eslint/parser",
    rules: {
        "no-magic-numbers": "off",
    },
    extends: [
        "plugin:@typescript-eslint/eslint-recommended",
        "plugin:@typescript-eslint/recommended",
        "plugin:playwright/playwright-test",
    ],
};
