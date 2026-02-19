import Redis from 'ioredis';
import { MongoClient, Db } from 'mongodb';
import mysql, { Pool, RowDataPacket } from 'mysql2/promise';

// ── Connection singletons ──────────────────────────────────────────────

let redisClient: Redis | null = null;
let mongoClient: MongoClient | null = null;
let mongoDB: Db | null = null;
let mysqlPool: Pool | null = null;

function getRedis(): Redis {
    if (!redisClient) {
        redisClient = new Redis({
            host: process.env.REDIS_HOST || '127.0.0.1',
            port: parseInt(process.env.REDIS_PORT || '6379', 10),
            password: process.env.REDIS_PASSWORD || undefined,
            keyPrefix: process.env.REDIS_PREFIX || 'laravel-database-',
            lazyConnect: true,
        });
    }
    return redisClient;
}

async function getMongo(): Promise<Db> {
    if (!mongoDB) {
        const uri = process.env.MONGODB_URI || 'mongodb://127.0.0.1:27017';
        mongoClient = new MongoClient(uri);
        await mongoClient.connect();
        const dbName = process.env.MONGODB_DATABASE || 'xcrawler_analytics';
        mongoDB = mongoClient.db(dbName);
    }
    return mongoDB;
}

function getMysql(): Pool {
    if (!mysqlPool) {
        mysqlPool = mysql.createPool({
            host: process.env.DB_HOST || '127.0.0.1',
            port: parseInt(process.env.DB_PORT || '3306', 10),
            user: process.env.DB_USERNAME || 'root',
            password: process.env.DB_PASSWORD || 'root',
            database: process.env.DB_DATABASE || 'jooservices_xcrawlerii',
            waitForConnections: true,
            connectionLimit: 5,
        });
    }
    return mysqlPool;
}

// ── Redis helpers ──────────────────────────────────────────────────────

export async function clearRedisAnalyticsKeys(): Promise<void> {
    const redis = getRedis();
    await redis.connect().catch(() => { }); // ignore if already connected

    const dedupeKeys = await redis.keys('anl:evt:*');
    const counterKeys = await redis.keys('anl:counters:*');
    const flushingKeys = await redis.keys('anl:flushing:*');
    const allKeys = [...dedupeKeys, ...counterKeys, ...flushingKeys];

    if (allKeys.length > 0) {
        await redis.del(...allKeys);
    }
}

export async function getRedisHash(key: string): Promise<Record<string, string>> {
    const redis = getRedis();
    await redis.connect().catch(() => { });
    return redis.hgetall(key);
}

export async function redisKeyExists(key: string): Promise<boolean> {
    const redis = getRedis();
    await redis.connect().catch(() => { });
    return (await redis.exists(key)) === 1;
}

export async function setRedisHashField(key: string, field: string, value: string): Promise<void> {
    const redis = getRedis();
    await redis.connect().catch(() => { });
    await redis.hset(key, field, value);
}

// ── MongoDB helpers ────────────────────────────────────────────────────

const ANALYTICS_COLLECTIONS = [
    'analytics_entity_totals',
    'analytics_entity_dailies',
    'analytics_entity_weeklies',
    'analytics_entity_monthlies',
    'analytics_entity_yearlies',
] as const;

export async function clearMongoAnalytics(): Promise<void> {
    const db = await getMongo();
    for (const col of ANALYTICS_COLLECTIONS) {
        try {
            await db.collection(col).deleteMany({});
        } catch {
            // Collection may not exist yet — that's fine
        }
    }
}

export async function getMongoTotals(
    entityId: string,
    domain = 'jav',
    entityType = 'movie',
): Promise<{ view: number; download: number } | null> {
    const db = await getMongo();
    const doc = await db.collection('analytics_entity_totals').findOne({
        domain,
        entity_type: entityType,
        entity_id: entityId,
    });
    if (!doc) return null;
    return { view: doc.view || 0, download: doc.download || 0 };
}

export async function getMongoDailyDocs(
    entityId: string,
    domain = 'jav',
    entityType = 'movie',
) {
    const db = await getMongo();
    return db
        .collection('analytics_entity_dailies')
        .find({ domain, entity_type: entityType, entity_id: entityId })
        .toArray();
}

export async function getMongoWeeklyDocs(
    entityId: string,
    domain = 'jav',
    entityType = 'movie',
) {
    const db = await getMongo();
    return db
        .collection('analytics_entity_weeklies')
        .find({ domain, entity_type: entityType, entity_id: entityId })
        .toArray();
}

export async function getMongoMonthlyDocs(
    entityId: string,
    domain = 'jav',
    entityType = 'movie',
) {
    const db = await getMongo();
    return db
        .collection('analytics_entity_monthlies')
        .find({ domain, entity_type: entityType, entity_id: entityId })
        .toArray();
}

export async function getMongoYearlyDocs(
    entityId: string,
    domain = 'jav',
    entityType = 'movie',
) {
    const db = await getMongo();
    return db
        .collection('analytics_entity_yearlies')
        .find({ domain, entity_type: entityType, entity_id: entityId })
        .toArray();
}

// ── MySQL helpers ──────────────────────────────────────────────────────

interface MovieCounters extends RowDataPacket {
    views: number;
    downloads: number;
}

interface MovieUuidRow extends RowDataPacket {
    uuid: string;
}

export async function getMysqlMovieCounters(uuid: string): Promise<{ views: number; downloads: number } | null> {
    const pool = getMysql();
    const [rows] = await pool.query<MovieCounters[]>(
        'SELECT views, downloads FROM jav WHERE uuid = ? LIMIT 1',
        [uuid],
    );
    if (rows.length === 0) return null;
    return { views: rows[0].views || 0, downloads: rows[0].downloads || 0 };
}

export async function getMovieUuids(count = 3): Promise<string[]> {
    const pool = getMysql();
    const [rows] = await pool.query<MovieUuidRow[]>(
        'SELECT uuid FROM jav WHERE uuid IS NOT NULL AND uuid != "" LIMIT ?',
        [count],
    );
    return rows.map((r) => r.uuid);
}

// ── Cleanup ────────────────────────────────────────────────────────────

export async function closeAll(): Promise<void> {
    if (redisClient) {
        await redisClient.quit().catch(() => { });
        redisClient = null;
    }
    if (mongoClient) {
        await mongoClient.close().catch(() => { });
        mongoClient = null;
        mongoDB = null;
    }
    if (mysqlPool) {
        await mysqlPool.end().catch(() => { });
        mysqlPool = null;
    }
}
