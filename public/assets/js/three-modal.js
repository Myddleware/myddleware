/*********************************************************************************
 * This file is part of Myddleware.
 * 
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2023  Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 * 
 * This file is part of Myddleware.
 * 
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

import * as THREE from 'three';

/**
 * ThreeModal class provides animated interactive modals using Three.js
 */
export class ThreeModal {
  /**
   * Creates a confirmation modal with Three.js animations
   * 
   * @param {object} options Configuration options
   * @param {string} options.message Message to display
   * @param {string} options.confirmText Text for the confirm button
   * @param {string} options.cancelText Text for the cancel button
   * @param {string} options.confirmIcon Font Awesome icon class for confirm button
   * @param {string} options.cancelIcon Font Awesome icon class for cancel button
   * @param {string} options.confirmColor Color for confirm button
   * @param {string} options.cancelColor Color for cancel button
   * @param {string} options.themeColor Main theme color for 3D elements
   * @param {Function} options.onConfirm Callback when user confirms
   * @param {Function} options.onCancel Callback when user cancels
   * @param {Function} options.onOpen Callback when modal opens
   * @param {Function} options.onClose Callback when modal closes
   * @param {boolean} options.useWarningSymbol Whether to use warning symbol (exclamation mark)
   * @param {object} options.customThreeScene Custom Three.js scene configuration
   */
  static showConfirmation(options) {
    const defaults = {
      message: 'Are you sure?',
      confirmText: 'Confirm',
      cancelText: 'Cancel',
      confirmIcon: 'fa-check',
      cancelIcon: 'fa-times',
      confirmColor: '#dc3545', // Red
      cancelColor: '#6c757d',  // Gray
      themeColor: 0xdc3545,    // Red (hex)
      onConfirm: () => {},
      onCancel: () => {},
      onOpen: () => {},
      onClose: () => {},
      useWarningSymbol: true,
      customThreeScene: null
    };
    
    // Merge provided options with defaults
    const settings = { ...defaults, ...options };
    
    // Create modal container
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'three-modal-overlay';
    modalOverlay.style.position = 'fixed';
    modalOverlay.style.top = '0';
    modalOverlay.style.left = '0';
    modalOverlay.style.width = '100%';
    modalOverlay.style.height = '100%';
    modalOverlay.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
    modalOverlay.style.display = 'flex';
    modalOverlay.style.justifyContent = 'center';
    modalOverlay.style.alignItems = 'center';
    modalOverlay.style.zIndex = '1000';
    modalOverlay.style.opacity = '0';
    modalOverlay.style.transition = 'opacity 0.3s ease';
    
    // Create modal content
    const modalContent = document.createElement('div');
    modalContent.className = 'three-modal-content';
    modalContent.style.backgroundColor = 'white';
    modalContent.style.borderRadius = '10px';
    modalContent.style.padding = '2rem';
    modalContent.style.width = '400px';
    modalContent.style.maxWidth = '90%';
    modalContent.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.3)';
    modalContent.style.transform = 'translateY(20px) scale(0.95)';
    modalContent.style.opacity = '0';
    modalContent.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
    
    // Create Three.js container
    const threeContainer = document.createElement('div');
    threeContainer.className = 'three-modal-container';
    threeContainer.style.height = '120px';
    threeContainer.style.width = '100%';
    threeContainer.style.marginBottom = '1.5rem';
    
    // Add message
    const messageDiv = document.createElement('div');
    messageDiv.className = 'three-modal-message';
    messageDiv.style.marginBottom = '1.5rem';
    messageDiv.style.fontSize = '1.1rem';
    messageDiv.style.textAlign = 'center';
    messageDiv.style.color = '#333';
    messageDiv.style.lineHeight = '1.5';
    messageDiv.textContent = settings.message;
    
    // Add buttons container
    const buttonsContainer = document.createElement('div');
    buttonsContainer.className = 'three-modal-buttons';
    buttonsContainer.style.display = 'flex';
    buttonsContainer.style.justifyContent = 'space-between';
    buttonsContainer.style.gap = '1rem';
    
    // Cancel button
    const cancelButton = document.createElement('button');
    cancelButton.className = 'three-modal-button three-modal-cancel';
    cancelButton.style.flex = '1';
    cancelButton.style.backgroundColor = settings.cancelColor;
    cancelButton.style.color = 'white';
    cancelButton.style.padding = '0.7rem 1.2rem';
    cancelButton.style.border = 'none';
    cancelButton.style.borderRadius = '30px';
    cancelButton.style.cursor = 'pointer';
    cancelButton.style.transition = 'all 0.3s ease';
    cancelButton.style.fontWeight = '500';
    cancelButton.innerHTML = `<i class="fas ${settings.cancelIcon} me-2"></i>${settings.cancelText}`;
    
    cancelButton.addEventListener('mouseover', () => {
      // Darken color by 10%
      cancelButton.style.backgroundColor = this.shadeColor(settings.cancelColor, -10);
      cancelButton.style.transform = 'translateY(-2px)';
      cancelButton.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.15)';
    });
    
    cancelButton.addEventListener('mouseout', () => {
      cancelButton.style.backgroundColor = settings.cancelColor;
      cancelButton.style.transform = 'translateY(0)';
      cancelButton.style.boxShadow = 'none';
    });
    
    // Confirm button
    const confirmButton = document.createElement('button');
    confirmButton.className = 'three-modal-button three-modal-confirm';
    confirmButton.style.flex = '1';
    confirmButton.style.backgroundColor = settings.confirmColor;
    confirmButton.style.color = 'white';
    confirmButton.style.padding = '0.7rem 1.2rem';
    confirmButton.style.border = 'none';
    confirmButton.style.borderRadius = '30px';
    confirmButton.style.cursor = 'pointer';
    confirmButton.style.transition = 'all 0.3s ease';
    confirmButton.style.fontWeight = '500';
    confirmButton.innerHTML = `<i class="fas ${settings.confirmIcon} me-2"></i>${settings.confirmText}`;
    
    confirmButton.addEventListener('mouseover', () => {
      // Darken color by 10%
      confirmButton.style.backgroundColor = this.shadeColor(settings.confirmColor, -10);
      confirmButton.style.transform = 'translateY(-2px)';
      confirmButton.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.15)';
    });
    
    confirmButton.addEventListener('mouseout', () => {
      confirmButton.style.backgroundColor = settings.confirmColor;
      confirmButton.style.transform = 'translateY(0)';
      confirmButton.style.boxShadow = 'none';
    });
    
    // Assemble the modal
    buttonsContainer.appendChild(cancelButton);
    buttonsContainer.appendChild(confirmButton);
    
    modalContent.appendChild(threeContainer);
    modalContent.appendChild(messageDiv);
    modalContent.appendChild(buttonsContainer);
    
    modalOverlay.appendChild(modalContent);
    document.body.appendChild(modalOverlay);
    
    // Initialize Three.js scene
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(75, threeContainer.clientWidth / threeContainer.clientHeight, 0.1, 1000);
    camera.position.z = 5;
    
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setClearColor(0x000000, 0);
    renderer.setSize(threeContainer.clientWidth, threeContainer.clientHeight);
    threeContainer.appendChild(renderer.domElement);
    
    // Use custom scene if provided, otherwise create default scene
    if (settings.customThreeScene) {
      settings.customThreeScene(scene, camera, renderer);
    } else if (settings.useWarningSymbol) {
      // Create warning symbol (exclamation mark)
      const group = new THREE.Group();
      
      // Circle
      const circleGeometry = new THREE.RingGeometry(0.8, 1, 32);
      const circleMaterial = new THREE.MeshBasicMaterial({ 
        color: settings.themeColor, 
        side: THREE.DoubleSide 
      });
      const circle = new THREE.Mesh(circleGeometry, circleMaterial);
      group.add(circle);
      
      // Exclamation mark - vertical line
      const lineGeometry = new THREE.CylinderGeometry(0.15, 0.15, 0.8, 32);
      const lineMaterial = new THREE.MeshBasicMaterial({ color: settings.themeColor });
      const line = new THREE.Mesh(lineGeometry, lineMaterial);
      line.position.y = 0.2;
      group.add(line);
      
      // Exclamation mark - dot
      const dotGeometry = new THREE.SphereGeometry(0.15, 32, 32);
      const dotMaterial = new THREE.MeshBasicMaterial({ color: settings.themeColor });
      const dot = new THREE.Mesh(dotGeometry, dotMaterial);
      dot.position.y = -0.5;
      group.add(dot);
      
      scene.add(group);
      
      // Animation function
      const animate = () => {
        const animationId = requestAnimationFrame(animate);
        
        group.rotation.z += 0.01;
        group.rotation.y += 0.01;
        
        renderer.render(scene, camera);
        
        // Store animation ID on the modal overlay for cleanup
        modalOverlay.dataset.animationId = animationId;
      };
      
      animate();
    }
    
    // Show modal with animation
    setTimeout(() => {
      modalOverlay.style.opacity = '1';
      modalContent.style.opacity = '1';
      modalContent.style.transform = 'translateY(0) scale(1)';
      settings.onOpen();
    }, 10);
    
    // Event handlers
    const closeModal = () => {
      modalContent.style.opacity = '0';
      modalContent.style.transform = 'translateY(20px) scale(0.95)';
      modalOverlay.style.opacity = '0';
      
      // Cancel animation
      if (modalOverlay.dataset.animationId) {
        cancelAnimationFrame(parseInt(modalOverlay.dataset.animationId));
      }
      
      // Remove from DOM after animation completes
      setTimeout(() => {
        if (document.body.contains(modalOverlay)) {
          document.body.removeChild(modalOverlay);
        }
        settings.onClose();
      }, 300);
    };
    
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) {
        closeModal();
        settings.onCancel();
      }
    });
    
    cancelButton.addEventListener('click', () => {
      closeModal();
      settings.onCancel();
    });
    
    confirmButton.addEventListener('click', () => {
      closeModal();
      settings.onConfirm();
    });
    
    // Handle escape key
    const escHandler = (e) => {
      if (e.key === 'Escape') {
        closeModal();
        settings.onCancel();
        document.removeEventListener('keydown', escHandler);
      }
    };
    
    document.addEventListener('keydown', escHandler);
    
    // Return an object to control the modal
    return {
      close: () => {
        closeModal();
      },
      updateMessage: (newMessage) => {
        messageDiv.textContent = newMessage;
      }
    };
  }
  
  /**
   * Shows an alert modal with a message and OK button
   * 
   * @param {object} options Configuration options (same as showConfirmation)
   * @returns Modal control object
   */
  static showAlert(options) {
    const defaults = {
      confirmText: 'OK',
      confirmIcon: 'fa-check',
      useWarningSymbol: false,
      onConfirm: () => {}
    };
    
    // Call showConfirmation with only one button
    return this.showConfirmation({
      ...defaults,
      ...options,
      // Hide cancel button by making it same as confirm
      cancelText: '',
      cancelColor: 'transparent',
      cancelIcon: '',
      onCancel: () => {} // No-op for cancel since we won't show it
    });
  }
  
  /**
   * Utility function to lighten or darken a color
   * 
   * @param {string} color Color in hex format (#RRGGBB)
   * @param {number} percent Percentage to lighten (positive) or darken (negative)
   * @returns {string} Modified color
   */
  static shadeColor(color, percent) {
    let R = parseInt(color.substring(1, 3), 16);
    let G = parseInt(color.substring(3, 5), 16);
    let B = parseInt(color.substring(5, 7), 16);

    R = parseInt(R * (100 + percent) / 100);
    G = parseInt(G * (100 + percent) / 100);
    B = parseInt(B * (100 + percent) / 100);

    R = (R < 255) ? R : 255;
    G = (G < 255) ? G : 255;
    B = (B < 255) ? B : 255;

    R = Math.max(0, R).toString(16).padStart(2, '0');
    G = Math.max(0, G).toString(16).padStart(2, '0');
    B = Math.max(0, B).toString(16).padStart(2, '0');

    return `#${R}${G}${B}`;
  }
} 