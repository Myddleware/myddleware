import { getBaseUrl } from './document-detail-url-utils.js';

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
            // Priority is now API first, then fallbacks
            
            // 1. Try from API endpoint (primary source)
            const apiPermissions = await this.getPermissionsFromAPI();
            if (apiPermissions) {
                return apiPermissions;
            }
            
            // 2. Try from HTML meta tags (fallback)
            const metaPermissions = this.getPermissionsFromMeta();
            if (metaPermissions) {
                return metaPermissions;
            }
            
            // 3. Try from global JavaScript variables (fallback)
            const globalPermissions = this.getPermissionsFromGlobals();
            if (globalPermissions) {
                return globalPermissions;
            }
            
            // 4. Final fallback to checking URL patterns or other indicators
            const urlPermissions = this.getPermissionsFromContext();
            return urlPermissions;
            
        } catch (error) {
            console.error(' Error getting user permissions:', error);
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
                }
            }
            
            return null;
        } catch (error) {
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
            return null;
        }
    }
    
    /**
     * Gets permissions from API endpoint using the FluxController
     * @returns {Promise<Object|null>} Permissions object or null
     */
    static async getPermissionsFromAPI() {
        try {
            // Construct API URL for the new FluxController endpoint
            const baseUrl = this.getBaseUrl();
            const apiUrl = `${baseUrl}/rule/api/flux/user-permissions`;
            
            
            const response = await fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                return null;
            }
            
            const data = await response.json();
            
            if (!data.success) {
                return null;
            }
            
            return data.permissions;
            
        } catch (error) {
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
            return { role: 'ROLE_USER', is_super_admin: false, roles: ['ROLE_USER'] };
        }
    }
    
    /**
     * Gets the base URL for API calls
     * @returns {string} Base URL
     */
    static getBaseUrl() {
        return getBaseUrl();
    }
}