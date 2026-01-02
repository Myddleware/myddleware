/**
 * Shared URL utilities for document-detail module
 * Handles proper base URL calculation for both regular and index.php URLs
 */

/**
 * Gets the base URL for API calls, properly handling:
 * - /public/ paths
 * - /public/index.php/ paths
 *
 * @returns {string} The base URL for API calls
 */
export function getBaseUrl() {
    const pathname = window.location.pathname;
    const publicIndex = pathname.indexOf('/public/');

    if (publicIndex !== -1) {
        // Extract base path including /public (without trailing slash to avoid double slashes)
        let basePath = pathname.substring(0, publicIndex + '/public'.length);

        // Check if index.php follows /public/
        const afterPublic = pathname.substring(publicIndex + '/public/'.length);
        if (afterPublic.startsWith('index.php')) {
            basePath += '/index.php';
        }

        return window.location.origin + basePath;
    }

    // Fallback for root installations
    return window.location.origin;
}

/**
 * Gets the path to solution images
 * @returns {string} Path to solution images folder
 */
export function getSolutionImagePath() {
    const pathname = window.location.pathname;
    const publicIndex = pathname.indexOf('/public/');

    if (publicIndex !== -1) {
        const basePath = pathname.substring(0, publicIndex + '/public/'.length);
        return `${basePath}build/images/solution/`;
    }

    return '/build/images/solution/';
}
