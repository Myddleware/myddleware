export default {
    testEnvironment: 'jsdom',
    transform: {
        '^.+\\.js$': ['babel-jest', { presets: ['@babel/preset-env'] }],
    },
    moduleNameMapper: {
        '^three$': '<rootDir>/node_modules/three',
        '^axios$': '<rootDir>/node_modules/axios',
    },
    setupFilesAfterEnv: ['<rootDir>/jest.setup.js'],
}; 