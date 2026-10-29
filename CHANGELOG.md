# Changelog

## 2.0.0

Rewritten for GenesisDB 2.0 and version 2.0 of the GenesisDB PHP client. Not compatible with the 1.x configuration or method names, see the README for the mapping.

- Requires `genesisdb/client-sdk` ^2.0, PHP 8.2 or newer and Neos Flow 8 or 9.
- Configuration `url`, `authToken` and `timeout`. `apiUrl` became `url`, `apiVersion` is gone. Empty values fall back to `GENESISDB_URL` and `GENESISDB_AUTH_TOKEN`.
- Methods follow the PHP client: `commit`, `fetch`, `subscribe`, `query`, `subjects`, `types`, `registerSchema`, `schemas`, `erase`, `audit`, `status`, `ping`. `commit` returns the stored events.
- The client is created once per service instead of on every call, and `client()` exposes it.
