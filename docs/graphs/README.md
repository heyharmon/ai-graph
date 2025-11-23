# Graphs Domain

## Purpose

Manages knowledge graphs created from website sitemaps. Each graph represents a website and contains discovered sources (pages).

## Backend

- **GraphController**: provides endpoints for creating graphs (with sitemap crawling), listing user's graphs, and viewing a graph.
- **Graph model**: stores graph details including `website_url` and `sources_count`. Belongs to a user and has many sources.

## Frontend

- **Pages**: `GraphsIndex.vue` lists all graphs, `GraphsCreate.vue` creates a new graph via sitemap crawl.

