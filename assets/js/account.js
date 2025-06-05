// console.log('loading account.js in assets/js');
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com	
 
 This file is part of Myddleware.
 
 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/


// Account page JavaScript implementation
import * as THREE from 'three';
import axios from 'axios';
import { ThreeModal } from '../../public/assets/js/three-modal.js';

/**
 * Account Manager Class
 * Handles all account page functionality
 */
class AccountManager {
  constructor() {
    // Add console logs for debugging
    // console.log("AccountManager initializing...");
    
    // Debug location info
    // console.log("Window location:", {
    //   href: window.location.href,
    //   origin: window.location.origin,
    //   pathname: window.location.pathname,
    //   host: window.location.host
    // });
    
    // Get base URL dynamically from current location
    this.baseUrl = window.location.pathname.includes('/public/') 
      ? window.location.pathname.split('/public/')[0] + '/public'
      : '';
    
    // console.log("Using base URL:", this.baseUrl);
    
    // API endpoints with dynamic base URL
    this.apiEndpoints = {
      getUserInfo: `${this.baseUrl}/rule/api/account/info`,
      updateProfile: `${this.baseUrl}/rule/api/account/profile/update`,
      updatePassword: `${this.baseUrl}/rule/api/account/password/update`,
      updateTwoFactor: `${this.baseUrl}/rule/api/account/twofactor/update`,
      changeLocale: `${this.baseUrl}/rule/api/account/locale`,
      downloadLogs: `${this.baseUrl}/rule/api/account/logs/download`,
      emptyLogs: `${this.baseUrl}/rule/api/account/logs/empty`
    };
    
    // Log all endpoints for debugging
    // console.log("API Endpoints:", this.apiEndpoints);
    
    this.user = null;
    this.threeJsContainer = null;
    this.scene = null;
    this.camera = null;
    this.renderer = null;
    
    // Initialize the page
    this.init();
  }
  
  /**
   * Initialize the account page
   */
  async init() {
    // console.log('init in account.js in assets/js');
    // Create the basic UI structure
    this.createUIStructure();
    
    // Load user data
    await this.loadUserData();
    
    // Setup event listeners
    this.setupEventListeners();
    
    // Initialize Three.js visualization
    this.initThreeJs();
    
    // Show notification if from redirects
    this.handleUrlParams();
  }
  
  /**
   * Create the UI structure
   */
  createUIStructure() {
    // console.log('createUIStructure in account.js in assets/js');
    const container = document.querySelector('.account-card');
    if (!container) return;
    
    // We'll populate this with translations once we have them
    container.innerHTML = `
      <div class="account-header">
        <h1 class="text-center mb-4">Loading...</h1>
        <div id="three-container"></div>
      </div>
      
      <div class="flash-messages"></div>
      
      <div id="account-content" style="display: none;">
        <!-- Content will be populated once we have translations -->
      </div>
    `;
    
    this.threeJsContainer = document.getElementById('three-container');
  }
  
  /**
   * Update UI with translations
   */
  updateUIWithTranslations() {
    if (!this.user || !this.user.translations) return;
    
    const t = this.user.translations.account;
    const container = document.getElementById('account-content');
    
    // Update title
    document.querySelector('.account-header h1').textContent = t.title;
    
    // Add logging for debugging
    // console.log('Starting UI update with translations');
    
    container.innerHTML = `
      <!-- Tabbed Navigation -->
      <div class="tab-group">
        <button class="tab active" data-tab="general"><i class="fas fa-user-gear me-2"></i>${t.tabs.general}</button>
        <button class="tab" data-tab="security"><i class="fas fa-shield-halved me-2"></i>${t.tabs.security}</button>
      </div>
      
      <!-- Tab Content -->
      <div class="tab-content-container">
        <!-- General Tab -->
        <div id="general-tab" class="tab-content active">
          <!-- Profile Information -->
          <h3>${t.sections.personal_info}</h3>
          <form id="profile-form" class="account-form">
            <div class="form-group">
              <label for="username">${t.fields.username}</label>
              <input type="text" id="username" name="username" class="form-control" />
            </div>
            
            <div class="form-group">
              <label for="email">${t.fields.email}</label>
              <input type="email" id="email" name="email" class="form-control" />
            </div>
            
            <div class="form-group">
            <label for="language">${t.fields.language}</label>
            <select id="language" name="language" class="form-control">
            <option value="en">English</option>
            <option value="fr">Français</option>
            <option value="de">Deutsch</option>
            <option value="es">Español</option>
            <option value="it">Italiano</option>
            </select>
            </div>
            <h3>${t.sections.preferences || 'Format preferences'}</h3>
            
            <div class="form-group">
              <label for="timezone">${t.fields.timezone}</label>
              <select id="timezone" name="timezone" class="form-control"></select>
            </div>

            <div class="form-group">
              <label for="date-format">${t.fields.date_format}</label>
              <select id="date-format" name="date-format" class="form-control">
                <option value="Y-m-d">YYYY-MM-DD</option>
                <option value="d/m/Y">DD/MM/YYYY</option>
                <option value="m/d/Y">MM/DD/YYYY</option>
                <option value="d.m.Y">DD.MM.YYYY</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="export-separator">${t.fields.export_separator}</label>
              <select id="export-separator" name="export-separator" class="form-control">
                <option value=",">${t.fields.export_separator_comma || 'Comma (,)'}</option>
                <option value=";">${t.fields.export_separator_semicolon || 'Semicolon (;)'}</option>
                <option value="\t">${t.fields.export_separator_tab || 'Tab'}</option>
                <option value="|">${t.fields.export_separator_pipe || 'Pipe (|)'}</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="encoding">${t.fields.charset}</label>
              <select id="encoding" name="encoding" class="form-control">
                <option value="UTF-8">UTF-8</option>
                <option value="ISO-8859-1">ISO-8859-1</option>
                <option value="Windows-1252">Windows-1252</option>
              </select>
            </div>
            
            
            <button type="submit" class="btn btn-primary mt-2">${t.buttons.save}</button>
          </form>
            
          <!-- Log Management -->
          <h3>${t.sections.logs}</h3>
          <div class="row-logs">
            <div class="form-group">
              <label>${t.buttons.download_logs}</label>
              <div>
                <button id="download-logs" class="btn-log btn-download">
                  <i class="fas fa-download me-2"></i>${t.buttons.download_logs}
                </button>
              </div>
            </div>
            
            <div class="form-group">
              <label>${t.buttons.empty_logs}</label>
              <div>
                <button id="empty-logs" class="btn-log btn-delete">
                  <i class="fas fa-trash-alt me-2"></i>${t.buttons.empty_logs}
                </button>
              </div>
            </div>
          </div>
          
        </div>
        
        <!-- Security Tab -->
        <div id="security-tab" class="tab-content" style="display: none;">
          <!-- Password Management -->
          <h3>${t.sections.password}</h3>
          <form id="password-form" class="account-form">
            <div class="form-group">
              <label for="current-password">${t.fields.current_password}</label>
              <input type="password" id="current-password" name="oldPassword" class="form-control" />
            </div>
            
            <div class="form-group">
              <label for="new-password">${t.fields.new_password}</label>
              <input type="password" id="new-password" name="plainPassword" class="form-control" />
              <div id="#password-strength-meter-reduce-width" class="password-strength-meter">
                <div id="password-strength-bar"></div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="confirm-password">${t.fields.confirm_password}</label>
              <input type="password" id="confirm-password" name="confirmPassword" class="form-control" />
            </div>
            
            <button type="submit" class="btn btn-primary mt-2">${t.buttons.update_password}</button>
          </form>
          
          <!-- Two-Factor Authentication -->
          <h3>${t.sections.twofa}</h3>
          <div class="form-group toggle-switch-container">
            <label>${t.fields.enable_twofa || 'Enable Two-Factor Authentication'}</label>
            <div class="toggle-switch small">
              <input type="checkbox" id="twofa-enabled" name="enabled" />
              <span class="toggle-label"></span>
            </div>
          </div>
          <small class="helper-text">
            ${t.messages.twofa_description}
          </small>
          <div id="smtp-warning" class="warning-message hidden mt-2">
            ${t.messages.smtp_warning}
          </div>
        </div>
      </div>
    `;
    
    container.style.display = 'block';
    
    // Set up tab switching
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
      tab.addEventListener('click', function() {
        // Remove active class from all tabs and hide all content
        tabs.forEach(t => t.classList.remove('active'));
        tabContents.forEach(content => {
          content.style.display = 'none';
        });
        
        // Add active class to clicked tab and show its content
        this.classList.add('active');
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId + '-tab').style.display = 'block';
      });
    });

    // Add password strength meter functionality
    const passwordInput = document.getElementById('new-password');
    const strengthBar = document.getElementById('password-strength-bar');
    
    if (passwordInput && strengthBar) {
      passwordInput.addEventListener('input', () => {
        const value = passwordInput.value;
        let strength = 0;
        
        // Calculate password strength
        if (value.length >= 8) strength += 1;
        if (value.match(/[a-z]/)) strength += 1;
        if (value.match(/[A-Z]/)) strength += 1;
        if (value.match(/[0-9]/)) strength += 1;
        if (value.match(/[^a-zA-Z0-9]/)) strength += 1;
        
        // Update strength bar
        strengthBar.className = '';
        if (strength <= 2) {
          strengthBar.classList.add('strength-weak');
        } else if (strength <= 4) {
          strengthBar.classList.add('strength-medium');
        } else {
          strengthBar.classList.add('strength-strong');
        }
      });
    }
  }
  
  /**
   * Load user data from API
   */
  async loadUserData() {
    // console.log("Starting to fetch user data...");
    
    try {
      // console.log("Attempting to fetch from:", this.apiEndpoints.getUserInfo);
      const response = await axios.get(this.apiEndpoints.getUserInfo);
      // console.log("User data received:", response.data);
      
      // Save user data and populate UI
      this.user = response.data;
      this.updateUIWithTranslations();
      this.populateUserData();
      this.populateTimezones();
      this.populateLanguages();
      
      // Configure UI based on user data
      if (!this.user.smtpConfigured) {
        const smtpWarning = document.getElementById('smtp-warning');
        const twofaToggle = document.getElementById('twofa-enabled');
        if (smtpWarning) smtpWarning.classList.remove('hidden');
        if (twofaToggle) twofaToggle.disabled = true;
      }
      
      const emptyLogsBtn = document.getElementById('empty-logs');
      console.log('emptyLogsBtn 1 in account.js in assets/js', emptyLogsBtn);
      if (emptyLogsBtn) {
        console.log('emptyLogsBtn 2 in account.js in assets/js', emptyLogsBtn);
        emptyLogsBtn.style.display = this.user.roles?.includes('ROLE_SUPER_ADMIN') ? 'block' : 'none';
        console.log('emptyLogsBtn 3 in account.js in assets/js', emptyLogsBtn);
      }
      
    } catch (error) {
      console.error("Failed to load user data:", {
        message: error.message,
        status: error.response?.status,
        statusText: error.response?.statusText,
        data: error.response?.data,
        endpoint: this.apiEndpoints.getUserInfo
      });

      // Handle authentication errors
      if (error.response?.status === 401) {
        // console.log("User not authenticated, redirecting to login...");
        // Get the current URL to redirect back after login
        const currentPath = window.location.pathname;
        // Redirect to login page with return URL
        window.location.href = `${this.baseUrl}/login?redirect=${encodeURIComponent(currentPath)}`;
        return;
      }
      
      // Show detailed error information in UI
      const container = document.querySelector('.account-card');
      if (container) {
        container.innerHTML = `
          <div class="alert alert-danger" role="alert">
            <h4>Error Loading Account Data</h4>
            <p>${error.response?.data?.error || error.message || 'Failed to load user data'}</p>
            <p>API Endpoint: ${this.apiEndpoints.getUserInfo}</p>
            <p>Status: ${error.response?.status} ${error.response?.statusText || ''}</p>
            <p>Please try refreshing the page. If the problem persists, contact support.</p>
            <button id="debug-button" class="btn btn-secondary mt-2">Debug API Endpoints</button>
          </div>
        `;
        
        // Add debug button event listener
        setTimeout(() => {
          const debugButton = document.getElementById('debug-button');
          if (debugButton) {
            debugButton.addEventListener('click', () => {
              // console.log('====== API DEBUG INFO ======');
              // console.log('Base URL:', this.baseUrl);
              // console.log('API Endpoints:', this.apiEndpoints);
              // console.log('Window Location:', window.location);
              // console.log('Document URL:', document.URL);
              // console.log('Error Details:', error);
              
              // Try to ping the server to check connectivity
              fetch(window.location.origin)
                .then(response => {
                  // console.log('Server ping result:', {
                  //   ok: response.ok,
                  //   status: response.status,
                  //   statusText: response.statusText
                  // });
                })
                .catch(err => {
                  // console.log('Server ping failed:', err);
                });
              
              alert('Debug information has been logged to the console (F12)');
            });
          }
        }, 100);
      }
    }
  }
  
  /**
   * Populate form fields with user data
   */
  populateUserData() {
    // console.log('populateUserData in account.js in assets/js');
    // console.log('User data for populating:', this.user);
    
    document.getElementById('username').value = this.user.username || '';
    document.getElementById('email').value = this.user.email || '';
    document.getElementById('timezone').value = this.user.timezone || 'UTC';
    
    // Configure two-factor toggle
    const twofaToggle = document.getElementById('twofa-enabled');
    // console.log('twofaToggle in populateUserData in account.js in assets/js', twofaToggle ? 'found' : 'not found');
    if (twofaToggle) {
      // console.log('this is the twofaToggle in populateUserData in account.js in assets/js', twofaToggle);
      // console.log('Setting 2FA toggle to:', this.user.twoFactorEnabled);
      twofaToggle.checked = this.user.twoFactorEnabled || false;
    } else {
      // console.log('twofaToggle not found in populateUserData in account.js in assets/js');
    }
    
    // Set default values for new fields if not present in user data
    document.getElementById('date-format').value = this.user.dateFormat || 'Y-m-d';
    document.getElementById('export-separator').value = this.user.exportSeparator || ',';
    document.getElementById('encoding').value = this.user.encoding || 'UTF-8';
  }
  
  /**
   * Populate timezone dropdown
   */
  populateTimezones() {
    // console.log('populateTimezones in account.js in assets/js');
    const timezoneSelect = document.getElementById('timezone');
    const timezones = [
      'UTC', 'Europe/Paris', 'Europe/London', 'America/New_York', 'America/Los_Angeles',
      'Asia/Tokyo', 'Australia/Sydney', 'Pacific/Auckland'
      // Add more timezones as needed
    ];
    
    timezoneSelect.innerHTML = '';
    timezones.forEach(tz => {
      const option = document.createElement('option');
      option.value = tz;
      option.textContent = tz;
      if (this.user && this.user.timezone === tz) {
        option.selected = true;
      }
      timezoneSelect.appendChild(option);
    });
  }
  
  /**
   * Populate language dropdown
   */
  populateLanguages() {
    // console.log('populateLanguages in account.js in assets/js');
    const languageSelect = document.getElementById('language');
    
    if (!this.user || !this.user.availableLocales) return;
    
    // Set the current locale as selected
    if (this.user.currentLocale) {
      languageSelect.value = this.user.currentLocale;
    }
    
    // Add change event listener
    languageSelect.addEventListener('change', (e) => {
      this.changeLanguage(e.target.value);
    });
  }
  
  /**
   * Get language name from locale code
   */
  getLanguageName(locale) {
    const languages = {
      'en': 'English',
      'fr': 'Français',
      'de': 'Deutsch',
      'es': 'Español',
      'it': 'Italiano'
      // Add more languages as needed
    };
    
    return languages[locale] || locale;
  }
  
  /**
   * Change the UI language
   */
  async changeLanguage(locale) {
    try {
      // console.log('Changing language to:', locale);
      const response = await axios.post(this.apiEndpoints.changeLocale, { locale });
      
      if (response.data.success) {
        this.showSuccessMessage('Language changed successfully. Reloading page...');
        // Give time for the message to be seen
        setTimeout(() => {
          window.location.reload();
        }, 1000);
      } else {
        this.showErrorMessage('Failed to change language: ' + (response.data.error || 'Unknown error'));
      }
    } catch (error) {
      console.error('Failed to change language:', error.response?.data || error);
      this.showErrorMessage('Failed to change language: ' + (error.response?.data?.error || error.message));
    }
  }
  
  /**
   * Set up event listeners
   */
  setupEventListeners() {
    // console.log('Setting up event listeners');
    
    document.getElementById('profile-form')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.updateProfile();
    });
    
    document.getElementById('password-form')?.addEventListener('submit', (e) => {
      e.preventDefault();
      this.updatePassword();
    });
    
    // Download logs button
    const downloadLogsBtn = document.getElementById('download-logs');
    if (downloadLogsBtn) {
      // console.log('Adding click event listener to download logs button');
      downloadLogsBtn.addEventListener('click', () => {
        // console.log('Download logs button clicked, redirecting to:', this.apiEndpoints.downloadLogs);
        window.location.href = this.apiEndpoints.downloadLogs;
      });
    }
    
    document.getElementById('empty-logs')?.addEventListener('click', () => {
      // Use ThreeModal library instead of browser confirm dialog
      ThreeModal.showConfirmation({
        message: this.user.translations.account.messages.confirm_empty_logs,
        confirmText: this.user.translations.account.buttons.empty_logs,
        cancelText: 'Cancel',
        confirmIcon: 'fa-trash-alt',
        cancelIcon: 'fa-times',
        confirmColor: '#dc3545',
        themeColor: 0xdc3545,
        onConfirm: () => {
          // Empty the logs when user confirms
          this.emptyLogs();
        }
      });
    });
    
    // 2FA toggle
    const twofaToggle = document.getElementById('twofa-enabled');
    // console.log('twofaToggle in account.js in assets/js', twofaToggle);
    if (twofaToggle) {
      // console.log('Adding change event listener to 2FA toggle');
      
      // Get references to the toggle components
      const toggleSwitch = twofaToggle.closest('.toggle-switch');
      const toggleLabel = toggleSwitch ? toggleSwitch.querySelector('.toggle-label') : null;
      
      // Add main change listener to checkbox
      twofaToggle.addEventListener('change', (e) => {
        // console.log('2FA toggle changed:', e.target.checked);
        this.updateTwoFactor(twofaToggle.checked);
      });
      
      // Add click event to the parent toggle-switch to improve UX
      if (toggleSwitch) {
        toggleSwitch.addEventListener('click', (e) => {
          // Only process if not clicking directly on the checkbox
          // This prevents double triggering with the change event
          if (e.target !== twofaToggle) {
            // Toggle the checkbox
            twofaToggle.checked = !twofaToggle.checked;
            
            // Manually trigger the change event
            const changeEvent = new Event('change');
            twofaToggle.dispatchEvent(changeEvent);
          }
        });
      }
    }
    
    // Language selector
    const languageSelect = document.getElementById('language');
    if (languageSelect) {
      languageSelect.addEventListener('change', () => {
        const locale = languageSelect.value;
        this.changeLanguage(locale);
      });
    }
    
    // Password strength meter
    const newPasswordInput = document.getElementById('new-password');
    // console.log('newPasswordInput in account.js in assets/js', newPasswordInput);
    const strengthBar = document.getElementById('password-strength-bar');
    // console.log('strengthBar in account.js in assets/js', strengthBar);
    
    if (newPasswordInput && strengthBar) {
      // console.log('Adding input event listener to password field');
      newPasswordInput.addEventListener('input', (e) => {
        // console.log('Password input changed');
        const password = newPasswordInput.value;
        const strength = this.measurePasswordStrength(password);
        // console.log('Password strength calculated:', strength);
        
        // Clear previous classes
        strengthBar.className = '';
        strengthBar.style.width = strength + '%';
        
        if (password.length > 0) {
          if (strength < 30) {
            // console.log('Setting strength-weak class');
            strengthBar.classList.add('strength-weak');
          } else if (strength < 60) {
            // console.log('Setting strength-medium class');
            strengthBar.classList.add('strength-medium');
          } else {
            // console.log('Setting strength-strong class');
            strengthBar.classList.add('strength-strong');
          }
        }
      });
    }
  }
  
  /**
   * Update user profile
   */
  async updateProfile() {
    const profileData = {
      username: document.getElementById('username').value,
      email: document.getElementById('email').value,
      timezone: document.getElementById('timezone').value,
      dateFormat: document.getElementById('date-format').value,
      exportSeparator: document.getElementById('export-separator').value,
      encoding: document.getElementById('encoding').value,
      language: document.getElementById('language').value // Add language to profile data
    };
    
    try {
      const response = await axios.post(this.apiEndpoints.updateProfile, profileData);
      this.showSuccessMessage('Profile updated successfully');
      
      // Update local user data
      this.user = {
        ...this.user,
        ...profileData
      };
      
      // Change language if it was updated
      if (profileData.language !== this.user.currentLocale) {
        await this.changeLanguage(profileData.language);
      }
      
    } catch (error) {
      this.showErrorMessage(error.response?.data?.message || 'Failed to update profile');
      console.error('Failed to update profile:', error);
    }
  }
  
  /**
   * Update two-factor authentication settings
   */
  async updateTwoFactor(enabled) {
    // console.log('updateTwoFactor called with enabled =', enabled);
    const twoFaToggle = document.getElementById('twofa-enabled');
    const originalState = !enabled; // Store original state in case we need to revert
    
    try {
      // console.log('Sending request to update 2FA settings to:', this.apiEndpoints.updateTwoFactor);
      const response = await axios.post(this.apiEndpoints.updateTwoFactor, { enabled });
      // console.log('2FA update response:', response.data);
      this.showSuccessMessage('Two-factor authentication ' + (enabled ? 'enabled' : 'disabled') + ' successfully');
      
      // Update user data
      this.user.twoFactorEnabled = enabled;
      
    } catch (error) {
      console.error('Error updating 2FA:', error.response || error.message);
      // Revert the toggle if the API call failed
      twoFaToggle.checked = originalState;
      this.showErrorMessage(error.response?.data?.message || 'Failed to update two-factor authentication settings');
    }
  }
  
  /**
   * Update user password
   */
  async updatePassword() {
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    if (newPassword !== confirmPassword) {
      this.showErrorMessage('New password and confirmation do not match');
      return;
    }
    
    const passwordData = {
      oldPassword: document.getElementById('current-password').value,
      plainPassword: newPassword
    };
    
    try {
      const response = await axios.post(this.apiEndpoints.updatePassword, passwordData);
      this.showSuccessMessage('Password updated successfully');
      document.getElementById('password-form').reset();
    } catch (error) {
      this.showErrorMessage(error.response?.data?.message || 'Failed to update password');
      console.error('Failed to update password:', error);
    }
  }
  
  /**
   * Empty logs
   */
  async emptyLogs() {
    try {
      console.log('emptying logs in account.js in assets/js');
      const response = await axios.post(this.apiEndpoints.emptyLogs);
      this.showSuccessMessage('Logs emptied successfully');
      console.log('response in account.js in assets/js', response); 
    } catch (error) {
      console.log('error in account.js in assets/js', error);
      this.showErrorMessage('Failed to empty logs');
      console.error('Failed to empty logs:', error);
    }
  }
  
  /**
   * Show success message
   */
  showSuccessMessage(message) {
    this.showMessage(message, 'success');
  }
  
  /**
   * Show error message
   */
  showErrorMessage(message) {
    this.showMessage(message, 'error');
  }
  
  /**
   * Show message with specified type
   */
  showMessage(message, type) {
    const flashContainer = document.querySelector('.flash-messages');
    
    const alertDiv = document.createElement('div');
    alertDiv.classList.add('alert', `alert-${type === 'error' ? 'danger' : type}`);
    alertDiv.role = 'alert';
    
    const icon = document.createElement('i');
    icon.classList.add('fas', type === 'error' ? 'fa-times' : 'fa-check');
    
    const messageP = document.createElement('p');
    messageP.textContent = message;
    messageP.classList.add('ms-2');
    
    alertDiv.appendChild(icon);
    alertDiv.appendChild(messageP);
    
    flashContainer.appendChild(alertDiv);
    
    // Remove the message after 5 seconds
    setTimeout(() => {
      alertDiv.remove();
    }, 5000);
  }
  
  /**
   * Initialize Three.js visualization
   */
  initThreeJs() {
    if (!this.threeJsContainer) return;
    
    // Create scene
    this.scene = new THREE.Scene();
    this.scene.background = new THREE.Color(0xf8f9fa);
    
    // Create camera
    this.camera = new THREE.PerspectiveCamera(
      75, 
      this.threeJsContainer.clientWidth / this.threeJsContainer.clientHeight, 
      0.1, 
      1000
    );
    this.camera.position.z = 5;
    
    // Create renderer
    this.renderer = new THREE.WebGLRenderer({ antialias: true });
    this.renderer.setSize(this.threeJsContainer.clientWidth, this.threeJsContainer.clientHeight);
    this.threeJsContainer.appendChild(this.renderer.domElement);
    
    // Add a simple cube
    const geometry = new THREE.BoxGeometry(1, 1, 1);
    const material = new THREE.MeshNormalMaterial();
    const cube = new THREE.Mesh(geometry, material);
    this.scene.add(cube);
    
    // Animation loop
    const animate = () => {
      requestAnimationFrame(animate);
      
      cube.rotation.x += 0.01;
      cube.rotation.y += 0.01;
      
      this.renderer.render(this.scene, this.camera);
    };
    
    animate();
    
    // Handle window resize
    window.addEventListener('resize', () => {
      if (!this.threeJsContainer) return;
      
      this.camera.aspect = this.threeJsContainer.clientWidth / this.threeJsContainer.clientHeight;
      this.camera.updateProjectionMatrix();
      this.renderer.setSize(this.threeJsContainer.clientWidth, this.threeJsContainer.clientHeight);
    });
  }
  
  /**
   * Handle URL parameters
   */
  handleUrlParams() {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');
    
    if (success) {
      this.showSuccessMessage(decodeURIComponent(success));
    }
    
    if (error) {
      this.showErrorMessage(decodeURIComponent(error));
    }
  }
  
  /**
   * Measure password strength on a scale of 0-100
   * @param {string} password 
   * @returns {number} Strength score 0-100
   */
  measurePasswordStrength(password) {
    if (!password) return 0;
    
    let score = 0;
    
    // Length
    score += Math.min(password.length * 4, 40);
    
    // Character variety
    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasDigit = /\d/.test(password);
    const hasSpecial = /[^a-zA-Z0-9]/.test(password);
    
    const variety = (hasLower ? 1 : 0) + 
                    (hasUpper ? 1 : 0) + 
                    (hasDigit ? 1 : 0) + 
                    (hasSpecial ? 1 : 0);
    
    score += variety * 10;
    
    // Special bonuses
    if (password.length > 8 && variety >= 3) {
      score += 10;
    }
    
    if (password.length > 12 && variety >= 4) {
      score += 10;
    }
    
    return Math.min(score, 100);
  }
}

// Initialize the account manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new AccountManager();
});
