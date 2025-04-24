console.log('loading account.js in assets/js');
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


// Positionne les différentes Popup au centre de la page en prenant en compte le scroll
function positionningPopup(id, idcontainer) {
  console.log('positionningPopup in account.js in assets/js');
	// Positionnement du bloc transparent (qui permet d'éviter les clics le temps de la popup)
	$(id,idcontainer).css({
		"height": $(document).height(),
		"width": $(document).width(),
	});
	
	// Positionnement initial (au centre de l'écran) + scroll
	$(id + "> .user_account_popup_content",idcontainer).css({
		"top": $(window).height()/2 - 200 + $(window).scrollTop(),
		"left": $(window).width()/2 - 150,
	});
	
    // On déclenche un événement scroll pour mettre à jour le positionnement au chargement de la page
    $(window).trigger('scroll');
 
    $(window).scroll(function(event){
		$(id + "> .user_account_popup_content",idcontainer).css({
			"top": $(window).height()/2 - 200 + $(window).scrollTop()
		});
    });
	
	// Repositionnement si resize de la fenêtre
	$(window).resize(function(){
		$(id,idcontainer).css({
			"height": $(document).height(),
			"width": $(document).width(),
		});
		
		$(id + "> .user_account_popup_content",idcontainer).css({
			"top": $(window).height()/2 - 200 + $(window).scrollTop(),
			"left": $(window).width()/2 - 150,
		});
	});	
}

// Account page JavaScript implementation
import * as THREE from 'three';
import axios from 'axios';

/**
 * Account Manager Class
 * Handles all account page functionality
 */
class AccountManager {
  constructor() {
    // Add console logs for debugging
    console.log("AccountManager initializing...");
    
    // Debug location info
    console.log("Window location:", {
      href: window.location.href,
      origin: window.location.origin,
      pathname: window.location.pathname,
      host: window.location.host
    });
    
    // The correct application path based on the logs
    let baseUrl = '';
    if (window.location.href.includes('myddleware_NORMAL')) {
      baseUrl = '/myddleware_NORMAL/public';
    }
    
    console.log("Using base URL:", baseUrl);
    
    // API endpoints with the correct application path prefix
    this.potentialEndpoints = [
      `${baseUrl}/rule/api/account/info`,
      `/myddleware_NORMAL/public/rule/api/account/info`
    ];
    
    console.log("API endpoints to try:", this.potentialEndpoints);
    
    // Start with the first option
    this.apiEndpoints = {
      getUserInfo: this.potentialEndpoints[0],
      updateProfile: `${baseUrl}/rule/api/account/profile/update`,
      updatePassword: `${baseUrl}/rule/api/account/password/update`,
      updateTwoFactor: `${baseUrl}/rule/api/account/twofactor/update`,
      changeLocale: `${baseUrl}/rule/api/account/locale`,
      downloadLogs: `${baseUrl}/rule/api/account/logs/download`,
      emptyLogs: `${baseUrl}/rule/api/account/logs/empty`
    };
    
    console.log("Initial API endpoint:", this.apiEndpoints.getUserInfo);
    
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
    console.log('init in account.js in assets/js');
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
    console.log('createUIStructure in account.js in assets/js');
    const container = document.getElementById('account-container');
    if (!container) return;

    // style the container
    container.style.backgroundColor = '#f8f9fa';
    container.style.borderRadius = '10px';
    container.style.padding = '20px';
    container.style.boxShadow = '0 0 10px 0 rgba(0, 0, 0, 0.1)';
    container.style.maxWidth = '1000px';
    container.style.margin = '0 auto';
    container.style.marginTop = '20px';
    
    container.innerHTML = `
      <div class="account-header">
        <h1 class="account-title">Account Settings</h1>
        <div id="three-container"></div>
      </div>
      
      <div class="flash-messages"></div>
      
      <!-- Tabbed Navigation -->
      <ul class="nav nav-tabs account-tabs" id="account-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
            <i class="fas fa-user-cog"></i> General
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
            <i class="fas fa-shield-alt"></i> Security
          </button>
        </li>
      </ul>
      
      <!-- Tab Content -->
      <div class="tab-content account-tab-content" id="account-tab-content">
        <!-- General Tab -->
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
          <div class="account-grid">
            <!-- Profile Information -->
            <div class="account-section profile-section">
              <h2>Profile Information</h2>
              <form id="profile-form" class="account-form">
                <div class="form-group">
                  <label for="username">Username</label>
                  <input type="text" id="username" name="username" class="form-control" />
                </div>
                
                <div class="form-group">
                  <label for="email">Email</label>
                  <input type="email" id="email" name="email" class="form-control" />
                </div>
                
                <div class="form-group">
                  <label for="timezone">Time Zone</label>
                  <select id="timezone" name="timezone" class="form-control"></select>
                </div>
                
                <div class="form-group">
                  <label for="date-format">Date Format</label>
                  <select id="date-format" name="date-format" class="form-control">
                    <option value="Y-m-d">YYYY-MM-DD</option>
                    <option value="d/m/Y">DD/MM/YYYY</option>
                    <option value="m/d/Y">MM/DD/YYYY</option>
                    <option value="d.m.Y">DD.MM.YYYY</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="export-separator">Export Separator (CSV)</label>
                  <select id="export-separator" name="export-separator" class="form-control">
                    <option value=",">Comma (,)</option>
                    <option value=";">Semicolon (;)</option>
                    <option value="\t">Tab</option>
                    <option value="|">Pipe (|)</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="encoding">Charset/Encoding</label>
                  <select id="encoding" name="encoding" class="form-control">
                    <option value="UTF-8">UTF-8</option>
                    <option value="ISO-8859-1">ISO-8859-1</option>
                    <option value="Windows-1252">Windows-1252</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="language">Language</label>
                  <select id="language" name="language" class="form-control">
                    <option value="en">English</option>
                    <option value="fr">Français</option>
                    <option value="de">Deutsch</option>
                    <option value="es">Español</option>
                    <option value="it">Italiano</option>
                  </select>
                </div>
                
                <button type="submit" class="btn btn-primary mt-2">Save Profile</button>
              </form>
            </div>
            
            
            <!-- Log Management -->
            <div class="account-section logs-section">
              <h4>Log Management</h4>
              <div class="buttons-container">
                <button id="download-logs" class="btn btn-secondary">
                  <i class="fas fa-download"></i> Download Logs
                </button>
                <button id="empty-logs" class="btn btn-warning mt-2">
                  <i class="fas fa-trash-alt"></i> Empty Logs
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Security Tab -->
        <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
          <div class="account-grid">
            <!-- Password Management -->
            <div class="account-section password-section">
              <h2>Password Management</h2>
              <form id="password-form" class="account-form">
                <div class="form-group">
                  <label for="current-password">Current Password</label>
                  <input type="password" id="current-password" name="oldPassword" class="form-control" />
                </div>
                
                <div class="form-group">
                  <label for="new-password">New Password</label>
                  <input type="password" id="new-password" name="plainPassword" class="form-control" />
                  <div class="password-strength-meter">
                    <div id="password-strength-bar"></div>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="confirm-password">Confirm New Password</label>
                  <input type="password" id="confirm-password" name="confirmPassword" class="form-control" />
                </div>
                
                <button type="submit" class="btn btn-primary mt-2">Update Password</button>
              </form>
            </div>
            
            <!-- Two-Factor Authentication -->
            <div class="account-section">
              <h2 class="mt-2">Two-Factor Authentication</h2>
              <div class="form-group">
                <div class="d-flex align-items-center justify-content-between">
                  <label for="twofa-enabled" class="form-label mb-0">Enable Two-Factor Authentication</label>
                  <div class="form-check form-switch">
                    <input type="checkbox" class="form-check-input" id="twofa-enabled" name="enabled" style="width: 3em; height: 1.5em;" />
                  </div>
                </div>
                <small class="helper-text mt-2">
                  When enabled, you'll be asked to enter a verification code sent to your email after logging in.
                </small>
                <div id="smtp-warning" class="warning-message hidden mt-2">
                  Two-factor authentication requires email configuration. Please configure either SMTP settings or Brevo API key first.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    
    this.threeJsContainer = document.getElementById('three-container');

    // Add event listeners for tab switching
    const tabs = document.querySelectorAll('#account-tabs .nav-link');
    tabs.forEach(tab => {
      tab.addEventListener('click', (e) => {
        e.preventDefault();
        // Remove active class from all tabs
        tabs.forEach(t => {
          t.classList.remove('active');
          const target = document.querySelector(t.getAttribute('data-bs-target'));
          if (target) {
            target.classList.remove('show', 'active');
          }
        });
        
        // Add active class to clicked tab
        tab.classList.add('active');
        const targetId = tab.getAttribute('data-bs-target');
        const targetPane = document.querySelector(targetId);
        if (targetPane) {
          targetPane.classList.add('show', 'active');
        }
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
    console.log("Starting to fetch user data...");
    
    // Try each potential endpoint until one works
    for (let i = 0; i < this.potentialEndpoints.length; i++) {
      const endpoint = this.potentialEndpoints[i];
      console.log(`Attempt ${i+1}: Trying to fetch from ${endpoint}`);
      
      try {
        console.log("Attempting fetch request...");
        const response = await axios.get(endpoint);
        console.log(`Success with endpoint ${endpoint}!`);
        console.log("User data received:", response.data);
        
        // Update the working endpoint for all API calls
        this.apiEndpoints = {
          getUserInfo: endpoint,
          updateProfile: endpoint.replace('info', 'profile/update'),
          updatePassword: endpoint.replace('info', 'password/update'),
          updateTwoFactor: endpoint.replace('info', 'twofactor/update'),
          changeLocale: endpoint.replace('info', 'locale'),
          downloadLogs: endpoint.replace('info', 'logs/download'),
          emptyLogs: endpoint.replace('info', 'logs/empty')
        };
        
        console.log("Updated all API endpoints based on working endpoint:", this.apiEndpoints);
        
        // Save user data and populate UI
        this.user = response.data;
        this.populateUserData();
        this.populateTimezones();
        this.populateLanguages();
        
        // Configure UI based on user data
        if (!this.user.smtpConfigured) {
          document.getElementById('smtp-warning').classList.remove('hidden');
          document.getElementById('twofa-enabled').disabled = true;
        }
        
        if (this.user.roles.includes('ROLE_SUPER_ADMIN')) {
          document.getElementById('empty-logs').style.display = 'block';
        } else {
          document.getElementById('empty-logs').style.display = 'none';
        }
        
        // Exit the loop if successful
        return;
        
      } catch (error) {
        console.error(`Attempt ${i+1} failed:`, error.message);
        console.log("Error details:", {
          status: error.response?.status,
          statusText: error.response?.statusText,
          url: endpoint
        });
        
        // Continue to the next endpoint if this one failed
      }
    }
    
    // If we've tried all endpoints and none worked, show an error
    this.showErrorMessage('Failed to load user data. Please check the console for details and refresh the page.');
  }
  
  /**
   * Populate form fields with user data
   */
  populateUserData() {
    console.log('populateUserData in account.js in assets/js');
    document.getElementById('username').value = this.user.username || '';
    document.getElementById('email').value = this.user.email || '';
    document.getElementById('timezone').value = this.user.timezone || 'UTC';
    document.getElementById('twofa-enabled').checked = this.user.twoFactorEnabled || false;
    
    // Set default values for new fields if not present in user data
    document.getElementById('date-format').value = this.user.dateFormat || 'Y-m-d';
    document.getElementById('export-separator').value = this.user.exportSeparator || ',';
    document.getElementById('encoding').value = this.user.encoding || 'UTF-8';
  }
  
  /**
   * Populate timezone dropdown
   */
  populateTimezones() {
    console.log('populateTimezones in account.js in assets/js');
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
    console.log('populateLanguages in account.js in assets/js');
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
      console.log('Changing language to:', locale);
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
    // Profile form
    const profileForm = document.getElementById('profile-form');
    profileForm.addEventListener('submit', e => {
      e.preventDefault();
      this.updateProfile();
    });
    
    // Two-factor toggle
    const twoFaToggle = document.getElementById('twofa-enabled');
    twoFaToggle.addEventListener('change', e => {
      this.updateTwoFactor(e.target.checked);
    });
    
    // Password form
    const passwordForm = document.getElementById('password-form');
    passwordForm.addEventListener('submit', e => {
      e.preventDefault();
      this.updatePassword();
    });
    
    // Download logs
    const downloadBtn = document.getElementById('download-logs');
    downloadBtn.addEventListener('click', () => {
      window.open(this.apiEndpoints.downloadLogs, '_blank');
    });
    
    // Empty logs
    const emptyBtn = document.getElementById('empty-logs');
    emptyBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to empty the log file?')) {
        this.emptyLogs();
      }
    });
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
    const twoFaToggle = document.getElementById('twofa-enabled');
    const originalState = !enabled; // Store original state in case we need to revert
    
    try {
      const response = await axios.post(this.apiEndpoints.updateTwoFactor, { enabled });
      this.showSuccessMessage('Two-factor authentication ' + (enabled ? 'enabled' : 'disabled') + ' successfully');
      
      // Update user data
      this.user.twoFactorEnabled = enabled;
      
    } catch (error) {
      // Revert the toggle if the API call failed
      twoFaToggle.checked = originalState;
      this.showErrorMessage(error.response?.data?.message || 'Failed to update two-factor authentication settings');
      console.error('Failed to update two-factor authentication settings:', error);
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
      const response = await axios.post(this.apiEndpoints.emptyLogs);
      this.showSuccessMessage('Logs emptied successfully');
    } catch (error) {
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
}

// Initialize the account manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
  new AccountManager();
});
