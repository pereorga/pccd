import { defineConfig } from "@playwright/test";

require("dotenv").config();

export default defineConfig({
    use: {
        baseURL: process.env.BASE_URL,
    },
});
