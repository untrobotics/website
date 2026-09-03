'use strict';

const mysql = require('mysql2/promise');
const { config } = require('./config');
const log = require('./logger');

/**
 * Shared mysql2 promise pool. The bot is low-throughput (a handful of queries
 * per verification), so a small pool is plenty.
 */
const pool = mysql.createPool({
  host: config.db.host,
  user: config.db.user,
  password: config.db.password,
  database: config.db.database,
  waitForConnections: true,
  connectionLimit: 5,
  queueLimit: 0,
  // Match the app's MySQL charset (tables are latin1 – see 01-schema.sql).
  charset: 'utf8mb4',
  // Return DATETIME/TIMESTAMP as JS Date objects in the server's timezone.
  timezone: 'Z',
  dateStrings: false,
});

/** Verify the DB is reachable; called once at startup so failures are loud. */
async function ping() {
  const conn = await pool.getConnection();
  try {
    await conn.ping();
    log.info('db: connection OK', `(${config.db.host}/${config.db.database})`);
  } finally {
    conn.release();
  }
}

module.exports = { pool, ping };
