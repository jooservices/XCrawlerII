# Event System – MongoDB Index Recommendations

Collections: `event_stores`, `event_logs` (database: same as app MongoDB connection, e.g. `xcrawler` or `xcrawler_logs`).

## event_stores

Recommended indexes for query patterns (create via migration or Mongo shell):

```javascript
// Primary lookup by event_id (unique)
db.event_stores.createIndex({ event_id: 1 }, { unique: true });

// Aggregate stream (event sourcing reads)
db.event_stores.createIndex({
    aggregate_type: 1,
    aggregate_id: 1,
    occurred_at: 1,
});

// Correlation / causation tracing
db.event_stores.createIndex({ correlation_id: 1 }, { sparse: true });
db.event_stores.createIndex({ causation_id: 1 }, { sparse: true });

// Time-bound queries (e.g. recent events)
db.event_stores.createIndex({ occurred_at: -1 });
db.event_stores.createIndex({ created_at: -1 });

// Optional: by event name
db.event_stores.createIndex({ event_name: 1, occurred_at: -1 });
```

## event_logs

```javascript
// Primary lookup by event_id (unique)
db.event_logs.createIndex({ event_id: 1 }, { unique: true });

// Entity audit trail
db.event_logs.createIndex({ entity_type: 1, entity_id: 1, occurred_at: -1 });

// Correlation
db.event_logs.createIndex({ correlation_id: 1 }, { sparse: true });

// Time-bound queries
db.event_logs.createIndex({ occurred_at: -1 });
db.event_logs.createIndex({ created_at: -1 });
```

Use `sparse: true` where the field is often null to keep index size smaller.
