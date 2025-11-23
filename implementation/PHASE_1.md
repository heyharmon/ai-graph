# Phase 1: Website Crawling & Source Inventory

## Objective
Build the foundation for the knowledge graph by crawling websites and creating an inventory of pages. Each page becomes a "source" in the knowledge graph system, serving as a potential location where entities can be extracted from in future phases.

## Core Capabilities

### 1. Website Crawling via Sitemap
* Implement sitemap crawler service (see `examples/SITEMAP_CRAWLER.md`)
* Crawl sitemap XML files to discover website pages
* Handle sitemap index files that reference multiple sitemaps
* Support common sitemap locations:
    * `/sitemap.xml`
    * `/sitemap_index.xml`
    * `/sitemap-index.xml`
    * `/sitemap/index.xml`
    * `/wp-sitemap.xml`
* Parse robots.txt for sitemap references
* Respect crawl limits and timeouts

### 2. Source Storage
* Store each discovered page as a "source" record
* Source model includes:
    * URL (unique identifier)
    * Title (extracted from page or URL)
    * Status (pending, crawled, failed)
    * Crawled at timestamp
* Sources serve as the foundation for entity extraction in Phase 2
* Each source can be linked to multiple entities when extraction occurs

### 3. Onboarding Flow
* User provides website URL
* System validates and normalizes URL
* Sitemap crawler discovers pages (shows progress)
* Display crawl results:
    * Total number of pages found
    * Preview list of first 10 pages (title and URL)
* This completes the initial onboarding step
* Future onboarding steps (entity extraction) will be added in Phase 2

## User Flow
1. User enters their website URL
2. System validates URL format and normalizes it
3. System crawls sitemap(s) to discover pages (shows progress)
4. System stores all discovered pages as sources
5. User sees onboarding results:
    * Total pages discovered
    * Preview of first 10 pages
6. Onboarding stops here (entity extraction happens in Phase 2)

## Success Criteria
* Successfully crawl sitemaps for websites of varying sizes (10 pages to 1,000+ pages)
* Handle common sitemap formats and structures
* Store all discovered pages as source records
* Display crawl results clearly in onboarding flow
* Process typical website (50-200 pages) within 30-60 seconds
* Gracefully handle errors (missing sitemaps, invalid XML, timeouts)

## Technical Considerations
* Use sitemap crawler service pattern from `examples/SITEMAP_CRAWLER.md`
* Handle websites without sitemaps (future: fallback to HTML crawling)
* Respect rate limits and implement proper timeouts
* Store sources efficiently for fast querying
* Track crawl status for each source
* Handle sitemap index files that reference multiple child sitemaps
* Support both absolute and relative sitemap URLs
* Normalize URLs to prevent duplicates

## Database Schema
* `sources` table:
    * `id` (primary key)
    * `url` (unique, indexed)
    * `title` (nullable)
    * `status` (enum: pending, crawled, failed)
    * `crawled_at` (timestamp, nullable)
    * `created_at` (timestamp)
    * `updated_at` (timestamp)
* Future: `source_entity` pivot table (Phase 2) to link sources to extracted entities
