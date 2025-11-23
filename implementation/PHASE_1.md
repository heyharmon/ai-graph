# Phase 1: Website Crawling & Source Inventory

## Objective
Build the foundation for the knowledge graph by crawling websites via sitemap XML files and creating an inventory of pages. Each discovered page becomes a "source" in the knowledge graph system—a record containing a URL and optional title that will serve as a potential location where entities can be extracted from in Phase 2.

## Core Capabilities

### 1. Website Crawling via Sitemap
* Implement sitemap crawler service using `examples/SITEMAP_CRAWLER.md` as reference
    * Use the example service as-is or modify as deemed fit for this app's use case
    * The example handles nested sitemap indexes (index → index → sitemap)
* Crawl sitemap XML files to discover website pages
* Handle sitemap index files that reference multiple sitemaps (nested indexes supported)
* Support common sitemap locations:
    * `/sitemap.xml`
    * `/sitemap_index.xml`
    * `/sitemap-index.xml`
    * `/sitemap/index.xml`
    * `/wp-sitemap.xml`
* Parse robots.txt for sitemap references
* Respect crawl limits and timeouts
* Limit total sources imported to maximum of 1,000 sources per website
* Convert relative URLs found in sitemaps to absolute URLs before storing

### 2. Source Storage
* Store each discovered page as a "source" record
* Source model includes:
    * URL (unique identifier, stored as absolute URL)
    * Title (extracted from `<title>` tag in sitemap XML if present, otherwise null)
        * Note: We do not attempt to crawl pages or extract titles from page content in Phase 1
    * Status (discovered, failed)
    * Discovered at timestamp
* Sources serve as the foundation for entity extraction in Phase 2
* Each source can be linked to multiple entities when extraction occurs
* Normalize URLs to prevent duplicates (same URL from multiple sitemaps)

### 3. Onboarding Flow
* User provides website URL
* System validates and normalizes URL
* Sitemap crawler discovers pages (shows progress indicator)
* Display crawl results:
    * Total number of pages found (up to 1,000 max)
    * Preview list of first 10 pages (title if available, and URL)
* This completes the initial onboarding step
* Future onboarding steps (entity extraction) will be added in Phase 2

## User Flow
1. User enters their website URL
2. System validates URL format and normalizes it
3. System crawls sitemap(s) to discover pages (shows progress indicator)
4. System stores discovered pages as sources (up to 1,000 max)
5. User sees onboarding results:
    * Total pages discovered
    * Preview of first 10 pages (title if available, and URL)
6. Onboarding stops here (entity extraction happens in Phase 2)

## Success Criteria
* Successfully crawl sitemaps for websites of varying sizes (10 pages to 1,000+ pages)
* Handle common sitemap formats and structures
* Support nested sitemap indexes (index → index → sitemap)
* Store discovered pages as source records (maximum 1,000 per website)
* Convert relative URLs to absolute URLs before storing
* Extract titles from `<title>` tags in sitemap XML when present
* Display crawl results clearly in onboarding flow
* Process typical website (50-200 pages) within 30-60 seconds
* Gracefully handle errors (missing sitemaps, invalid XML, timeouts, unreachable websites)

## Technical Considerations
* Use sitemap crawler service pattern from `examples/SITEMAP_CRAWLER.md`
    * Reference implementation handles nested sitemap indexes
    * Adapt as needed for this app's use case
* Limit total sources to 1,000 per website (stop importing after limit reached)
* Convert relative URLs in sitemaps to absolute URLs before storage
* Respect rate limits and implement proper timeouts
* Store sources efficiently for fast querying
* Track discovery status for each source
* Handle sitemap index files that reference multiple child sitemaps
* Support both absolute and relative sitemap URLs (convert to absolute for storage)
* Normalize URLs to prevent duplicates
* **Out of Scope for Phase 1:**
    * HTML crawling fallback for websites without sitemaps
    * Page content fetching or crawling
    * Title extraction from page HTML content

## Database Schema
* `sources` table:
    * `id` (primary key)
    * `knowledge_graph_id` (foreign key to knowledge_graphs table)
    * `url` (unique per knowledge_graph, indexed, absolute URL)
    * `title` (nullable, extracted from sitemap XML `<title>` tag if present)
    * `status` (enum: discovered, failed)
    * `discovered_at` (timestamp, nullable)
    * `created_at` (timestamp)
    * `updated_at` (timestamp)
* `knowledge_graphs` table:
    * `id` (primary key)
    * `user_id` (foreign key to users table)
    * `website_url` (the base URL that was crawled)
    * `sources_count` (cached count of discovered sources)
    * `created_at` (timestamp)
    * `updated_at` (timestamp)
* Future: `source_entity` pivot table (Phase 2) to link sources to extracted entities
