const { defineConfig } = require('cypress');

module.exports = defineConfig({
  e2e: {
    baseUrl: process.env.WP_BASE_URL || 'http://localhost:8888',
    supportFile: false,
  },
});
