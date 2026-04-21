console.log("Core device module loaded");

// =============================
// DEVICE KEY
// =============================
const DEVICE_KEY = 'device_uuid';

// =============================
// UUID GENERATOR
// =============================
function generateUUID() {
    return (crypto?.randomUUID)
        ? crypto.randomUUID()
        : 'dev_' + Date.now() + '_' + Math.random().toString(36).substring(2, 10);
}

// =============================
// GET DEVICE ID (SAFE)
// =============================
function getDeviceId() {

    let id = localStorage.getItem(DEVICE_KEY);

    if (!id) {
        id = generateUUID();
        localStorage.setItem(DEVICE_KEY, id);
    }

    return id;
}

// =============================
// EXPOSE CORE MODULE
// =============================
window.POS = window.POS || {};

window.POS.device = {
    getId: getDeviceId
};

// =============================
// LEGACY SUPPORT (DO NOT REMOVE YET)
// =============================
window.deviceId = getDeviceId();