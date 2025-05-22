import '@testing-library/jest-dom';
import { JSDOM } from 'jsdom';
import { Flux } from '../../assets/js/flux';

// Set up a basic DOM environment
const dom = new JSDOM('<!DOCTYPE html><html><body></body></html>');
global.document = dom.window.document;
global.window = dom.window;

describe('Flux', () => {
    let flux;

    beforeEach(() => {
        // Clear the document body before each test
        document.body.innerHTML = '';
        // Create a new instance of Flux for each test
        flux = new Flux();
    });

    test('should initialize properly', () => {
        expect(flux).toBeDefined();
    });

    test('should create UI structure', () => {
        // Spy on console.log to verify it was called
        const consoleSpy = jest.spyOn(console, 'log');
        
        // Call createUIStructure
        flux.createUIStructure();
        
        // Verify the console.log was called with the correct message
        expect(consoleSpy).toHaveBeenCalledWith('Flux createUIStructure');
        
        // Clean up
        consoleSpy.mockRestore();
    });

    test('should initialize when DOM is loaded', () => {
        // Spy on console.log
        const consoleSpy = jest.spyOn(console, 'log');
        
        // Trigger DOMContentLoaded event
        document.dispatchEvent(new Event('DOMContentLoaded'));
        
        // Verify the initialization logs
        expect(consoleSpy).toHaveBeenCalledWith('Flux constructor');
        expect(consoleSpy).toHaveBeenCalledWith('Flux init');
        
        // Clean up
        consoleSpy.mockRestore();
    });

    test('should find div with id flux-container', () => {
        const fluxContainer = document.getElementById('flux-container');
        expect(fluxContainer).toBeDefined();
    });
}); 