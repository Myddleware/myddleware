/**
 * DocumentDetailPermissions - Handles user permission detection
 */
export class DocumentDetailPermissions {
    
    /**
     * Gets current user permissions from various sources
     * @returns {Promise<Object>} User permissions object
     */
    static async getCurrentUserPermissions() {
        try {
            // Try to get permissions from multiple sources
            
            // 1. Try from HTML meta tags (if your app injects them)
            const metaPermissions = this.getPermissionsFromMeta();
            if (metaPermissions) {
                console.log('✅ Got permissions from meta tags');
                return metaPermissions;
            }
            
            // 2. Try from global JavaScript variables (if your app sets them)
            const globalPermissions = this.getPermissionsFromGlobals();
            if (globalPermissions) {
                console.log('✅ Got permissions from global variables');
                return globalPermissions;
            }
            
            // 3. Try from API endpoint
            const apiPermissions = await this.getPermissionsFromAPI();
            if (apiPermissions) {
                console.log('✅ Got permissions from API');
                return apiPermissions;
            }
            
            // 4. Fallback to checking URL patterns or other indicators
            const urlPermissions = this.getPermissionsFromContext();
            console.log('⚠️ Using fallback permission detection');
            return urlPermissions;
            
        } catch (error) {
            console.error('❌ Error getting user permissions:', error);
            return { role: 'ROLE_USER', is_super_admin: false };
        }
    }
    
    /**
     * Gets permissions from HTML meta tags
     * @returns {Object|null} Permissions object or null
     */
    static getPermissionsFromMeta() {
        try {
            const userRoleMeta = document.querySelector('meta[name="user-role"]');
            const userPermsMeta = document.querySelector('meta[name="user-permissions"]');
            
            if (userRoleMeta) {
                const role = userRoleMeta.getAttribute('content');
                return {
                    role: role,
                    is_super_admin: role === 'ROLE_SUPER_ADMIN',
                    roles: [role]
                };
            }
            
            if (userPermsMeta) {
                try {
                    return JSON.parse(userPermsMeta.getAttribute('content'));
                } catch (e) {
                    console.warn('⚠️ Could not parse permissions from meta tag');
                }
            }
            
            return null;
        } catch (error) {
            console.warn('⚠️ Error getting permissions from meta tags:', error.message);
            return null;
        }
    }
    
    /**
     * Gets permissions from global JavaScript variables
     * @returns {Object|null} Permissions object or null
     */
    static getPermissionsFromGlobals() {
        try {
            // Check common global variable names
            if (typeof window.userPermissions !== 'undefined') {
                return window.userPermissions;
            }
            
            if (typeof window.currentUser !== 'undefined' && window.currentUser.permissions) {
                return window.currentUser.permissions;
            }
            
            if (typeof window.USER_ROLE !== 'undefined') {
                return {
                    role: window.USER_ROLE,
                    is_super_admin: window.USER_ROLE === 'ROLE_SUPER_ADMIN',
                    roles: [window.USER_ROLE]
                };
            }
            
            return null;
        } catch (error) {
            console.warn('⚠️ Error getting permissions from globals:', error.message);
            return null;
        }
    }
    
    /**
     * Gets permissions from API endpoint
     * @returns {Promise<Object|null>} Permissions object or null
     */
    static async getPermissionsFromAPI() {
        try {
            // Construct API URL
            const baseUrl = this.getBaseUrl();
            const apiUrl = `${baseUrl}/api/user/permissions`; // Adjust this URL to match your API
            
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                console.warn(`⚠️ API permissions request failed: ${response.status}`);
                return null;
            }
            
            const data = await response.json();
            return data.permissions || data;
            
        } catch (error) {
            console.warn('⚠️ Error getting permissions from API:', error.message);
            return null;
        }
    }
    
    /**
     * Gets permissions from context clues (URL, etc.)
     * @returns {Object} Basic permissions object
     */
    static getPermissionsFromContext() {
        try {
            // This is a fallback - you might want to check URL patterns,
            // cookie values, or other indicators of user role
            
            // Basic permission object as fallback
            const basicPermissions = {
                role: 'ROLE_USER',
                is_super_admin: false,
                roles: ['ROLE_USER']
            };
            
            // You could add logic here to detect admin users from URL patterns
            // For example, if URL contains 'admin' or certain patterns
            const currentPath = window.location.pathname;
            if (currentPath.includes('/admin/')) {
                basicPermissions.role = 'ROLE_ADMIN';
                basicPermissions.roles = ['ROLE_USER', 'ROLE_ADMIN'];
            }
            
            return basicPermissions;
            
        } catch (error) {
            console.warn('⚠️ Error getting permissions from context:', error.message);
            return { role: 'ROLE_USER', is_super_admin: false, roles: ['ROLE_USER'] };
        }
    }
    
    /**
     * Gets the base URL for API calls
     * @returns {string} Base URL
     */
    static getBaseUrl() {
        const pathParts = window.location.pathname.split('/');
        const publicIndex = pathParts.indexOf('public');
        
        if (publicIndex !== -1) {
            const baseParts = pathParts.slice(0, publicIndex + 1);
            return window.location.origin + baseParts.join('/');
        }
        
        return window.location.origin;
    }
}