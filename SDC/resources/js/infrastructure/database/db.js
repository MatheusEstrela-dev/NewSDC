import Dexie from 'dexie';

export const db = new Dexie('SDC_MG_Database');

db.version(1).stores({
    // 'id' is a UUID generated on the client
    // 'sync_status' controls the queue (pending, synced)
    rat_pendentes: 'id, sync_status, created_at' 
});

export default db;
