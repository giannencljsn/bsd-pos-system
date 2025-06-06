console.log("IndexedDB script loaded ✅");

const DB_NAME = 'BSDOffline';
const DB_VERSION = 2;
const STORE_NAME = 'offlineStore';

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                console.log("Object store created:", STORE_NAME);
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function saveOfflineData(data) {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const request = store.add({ ...data, synced: false });

        request.onsuccess = () => {
            console.log("Data saved offline:", data);
            resolve(true);
        };

        request.onerror = (e) => {
            console.error("Failed to save data offline:", e);
            reject(e);
        };
    });
}

async function getAllOfflineData() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const request = store.getAll();

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function clearOfflineData() {
    const db = await openDB();
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        const request = store.clear();

        request.onsuccess = () => {
            console.log("Offline data cleared");
            resolve(true);
        };

        request.onerror = () => reject(request.error);
    });
}
