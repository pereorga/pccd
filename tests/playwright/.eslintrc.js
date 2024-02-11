module.exports = {
    env: {
        browser: false,
        node: true,
    },
    rules: {
        "no-magic-numbers": "off",
    },
    extends: ["plugin:playwright/playwright-test"],
};
