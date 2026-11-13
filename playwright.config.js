import { defineConfig } from '@playwright/test';
export default defineConfig({testDir:'./tests',testMatch:'*.spec.js',workers:1,use:{baseURL:process.env.WP_URL || 'http://localhost:8888',headless:true},reporter:[['list'],['html',{open:'never'}]],outputDir:'test-results'});
