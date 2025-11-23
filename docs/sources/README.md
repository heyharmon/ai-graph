# Sources Domain

## Purpose

Represents individual pages discovered from website sitemaps. Sources belong to a graph and can be extracted for entities in future phases.

## Backend

- **SourceController**: provides endpoint for listing sources for a graph with pagination, search, and sorting.
- **Source model**: stores source details including `url`, `title`, `status`, and `discovered_at`. Belongs to a graph.

## Frontend

- **Pages**: `SourcesIndex.vue` displays all sources for a graph with search and pagination.

