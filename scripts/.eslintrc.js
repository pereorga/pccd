module.exports = {
    env: {
        browser: false,
        node: true,
    },
    extends: ["plugin:playwright/playwright-test"],
    rules: {
        "no-magic-numbers": "off",
        "unicorn/prefer-top-level-await": "off",
    },
};
