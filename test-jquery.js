#!/usr/bin/env node

/**
 * jQuery Loading Test Script
 * 
 * This script tests jQuery and its plugins loading in the Node.js environment
 * to simulate browser loading issues.
 */

const fs = require('fs');
const path = require('path');

console.log('=== jQuery Loading Test ===\n');

// Check if jQuery is available in package.json
console.log('1. Checking package.json for jQuery dependencies...');
try {
    const packageJson = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    
    console.log(' jQuery dependencies found:');
    if (packageJson.dependencies.jquery) {
        console.log(`   - jquery: ${packageJson.dependencies.jquery}`);
    }
    if (packageJson.dependencies['jquery-ui']) {
        console.log(`   - jquery-ui: ${packageJson.dependencies['jquery-ui']}`);
    }
    
    console.log('\n📁 jQuery-related assets found in assets/js/lib/:');
    const libPath = path.join(__dirname, 'assets', 'js', 'lib');
    if (fs.existsSync(libPath)) {
        const libContents = fs.readdirSync(libPath);
        libContents.forEach(item => {
            if (item.toLowerCase().includes('jquery')) {
                console.log(`   - ${item}/`);
                const itemPath = path.join(libPath, item);
                if (fs.statSync(itemPath).isDirectory()) {
                    const subItems = fs.readdirSync(itemPath);
                    subItems.forEach(subItem => {
                        console.log(`     - ${subItem}`);
                    });
                }
            }
        });
    }
} catch (error) {
    console.error(' Error reading package.json:', error.message);
}

console.log('\n2. Checking webpack configuration for jQuery...');
try {
    const webpackConfig = fs.readFileSync('webpack.config.js', 'utf8');
    
    // Check if jQuery is configured in webpack
    if (webpackConfig.includes('autoProvidejQuery')) {
        if (webpackConfig.includes('//.autoProvidejQuery()') || webpackConfig.includes('// .autoProvidejQuery()')) {
            console.log('⚠️  jQuery auto-provide is COMMENTED OUT in webpack config');
            console.log('   This is likely the cause of your jQuery plugin issues!');
        } else {
            console.log(' jQuery auto-provide is enabled in webpack config');
        }
    } else {
        console.log(' jQuery auto-provide not found in webpack config');
    }
    
    // Check for other jQuery configurations
    if (webpackConfig.includes('ProvidePlugin')) {
        console.log('📝 ProvidePlugin found - checking for jQuery configuration...');
        // Would need more detailed parsing to check ProvidePlugin config
    }
} catch (error) {
    console.error(' Error reading webpack.config.js:', error.message);
}

console.log('\n3. Analyzing the error from regle.js...');
console.log('📍 Error location: regle.js:84 - $("#tabs").tabs(onglets)');
console.log('🔍 This indicates jQuery UI tabs plugin is not loaded');

console.log('\n4. Recommended solutions:');
console.log('   a) Uncomment .autoProvidejQuery() in webpack.config.js');
console.log('   b) Add explicit ProvidePlugin configuration for jQuery');
console.log('   c) Import jQuery and jQuery UI manually in your entry files');

console.log('\n5. Quick webpack fix:');
console.log('   Edit webpack.config.js and change:');
console.log('   //.autoProvidejQuery()');
console.log('   to:');
console.log('   .autoProvidejQuery()');

console.log('\n=== Test Complete ===');