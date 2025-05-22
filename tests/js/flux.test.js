import '@testing-library/jest-dom';
import { JSDOM } from 'jsdom';
import { Flux } from '../../assets/js/flux';

// Set up a basic DOM environment
const dom = new JSDOM('<!DOCTYPE html><html><body><div id="flux-container"></div></body></html>');
global.document = dom.window.document;
global.window = dom.window;

describe('Flux', () => {
    let flux;

    beforeEach(() => {
        // Clear the document body before each test
        document.body.innerHTML = '<div id="flux-container"></div>';
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
        const container = document.getElementById('flux-container');
        expect(container).not.toBeNull();
    });

    test('should create button container inside of flux-container', () => {
        flux.createUIStructure();
        const buttonContainer = document.getElementById('flux-button-container');
        expect(buttonContainer).not.toBeNull();
    });

    test('should create button to run the same record inside of button container', () => {
        flux.createUIStructure();
        const button = document.getElementById('run-same-record');
        expect(button).not.toBeNull();
        expect(button.textContent).toBe('Run same record');
    });

    test('should create button to cancel the document inside of button container', () => {
        flux.createUIStructure();
        const button = document.getElementById('cancel-document');
        expect(button).not.toBeNull();
        expect(button.textContent).toBe('Cancel document');
    });

    test('button container should be in flexbox with its two element aligned horizontally', () => {
        flux.createUIStructure();
        const buttonContainer = document.getElementById('flux-button-container');
        expect(buttonContainer).toHaveClass('flex-row');
    });
}); 