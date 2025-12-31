/**
 * Firebase Configuration and Initialization
 * Emergency Communication System - Chat Integration
 */

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyAvfyPTCsBp0dL76VsEVkiIrIsQkko91os",
    authDomain: "emergencycommunicationsy-eb828.firebaseapp.com",
    databaseURL: "https://emergencycommunicationsy-eb828-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "emergencycommunicationsy-eb828",
    storageBucket: "emergencycommunicationsy-eb828.firebasestorage.app",
    messagingSenderId: "201064241540",
    appId: "1:201064241540:web:4f6d026cd355404ec365d1",
    measurementId: "G-ESQ63CMP9B"
};

// Initialize Firebase (will be done via module import in HTML)
let app, database, analytics;

// Check if Firebase is loaded
if (typeof firebase !== 'undefined') {
    app = firebase.initializeApp(firebaseConfig);
    database = firebase.database();
    if (typeof firebase.analytics !== 'undefined') {
        analytics = firebase.analytics();
    }
} else {
    console.warn('Firebase SDK not loaded. Please include Firebase scripts.');
}

// Export for use in other scripts
window.firebaseConfig = firebaseConfig;
window.firebaseApp = app;
window.firebaseDatabase = database;


