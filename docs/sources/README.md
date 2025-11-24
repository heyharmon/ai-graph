# Sources Domain

## Purpose

Represents individual pages discovered from website sitemaps. Sources belong to a graph and can be extracted for entities in future phases.

## Backend

- **SourceController**: provides endpoint for listing sources for a graph with pagination, search, and sorting.
- **Source model**: stores source details including `url`, `title`, `source` (origin like 'website'), `type` (e.g., 'Product Page', 'Blog Post', 'Page'), `status`, and `discovered_at`. Belongs to a graph.

## Frontend

- **Pages**: `SourcesIndex.vue` displays all sources for a graph with search and pagination.

## Source Tracking

- **source column**: Tracks where the source originated (e.g., 'website' for sitemap crawls).
- **type column**: Optional classification of the source page type (e.g., 'Product Page', 'Blog Post', 'Info Page', 'Page').

