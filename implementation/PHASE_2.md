# Phase 2: Sources Management Page

## Objective
Separate the source discovery results from the onboarding flow by creating a dedicated Sources page. This improves the user experience by providing a dedicated space to view and manage all discovered sources, while keeping the onboarding flow focused on the initial crawl process.

## Core Capabilities

### 1. Onboarding Flow Refinement
* Remove the results/preview section from the onboarding page
* After successful sitemap crawl, automatically redirect user to Sources page
* Onboarding page now only shows:
    * Website URL input form
    * Loading/progress indicator during crawl
    * Automatic redirect to Sources page when crawl is complete
* Simplify onboarding to focus on the crawl action, not results display

### 2. Sources Page
* Create new dedicated Sources page (`/knowledge-graphs/:id/sources`)
* Display all sources for a knowledge graph (not just preview)
* Show comprehensive source information:
    * Source ID/Number
    * Title (if available, otherwise show "(No title)")
    * URL (clickable, opens in new tab)
    * Status (discovered, failed)
    * Discovered at timestamp
* Table features:
    * Pagination (e.g., 100 sources per page)
    * Sorting by:
        * Title (alphabetical)
        * URL (alphabetical)
        * Discovered at (chronological)
    * Search/filter by:
        * URL (partial match)
        * Title (partial match)
    * Display total count of sources

### 3. API Enhancements
* Update `KnowledgeGraphController@store` to remove `sources_preview` from response
* Add new endpoint: `GET /api/knowledge-graphs/:id/sources`
    * Returns paginated list of sources
    * Supports query parameters:
        * `page` (pagination)
        * `per_page` (items per page, default 25)
        * `search` (search in URL and title)
        * `sort_by` (title, url, discovered_at)
        * `sort_order` (asc, desc)
* Response format:
    ```json
    {
        "sources": [...],
        "pagination": {
            "current_page": 1,
            "per_page": 25,
            "total": 150,
            "last_page": 6
        }
    }
    ```

## User Flow
1. User enters website URL on onboarding page
2. System validates and crawls sitemap (shows progress)
3. Onboarding page redirects to sources page when crawl is complete
4. User can:
    * Browse all sources with pagination
    * Search/filter sources
    * Sort sources
    * Click URLs to view source pages

## Technical Considerations
* Create new Vue component: `Sources.vue` or `SourcesIndex.vue`
* Add route: `/knowledge-graphs/:id/sources`
* Update `KnowledgeGraphController`:
    * Remove `sources_preview` from `store()` response
* Create new `SourceController`:
    * Add `index()` method for listing sources
* Use Laravel pagination for efficient data loading
* Implement search using Laravel's `where` clauses (URL and title)
* Add database indexes if needed for search performance:
    * Index on `sources.url` (already exists)
    * Consider full-text index on `sources.title` if needed
* Frontend should handle:
    * Loading states
    * Empty states (no sources found)
    * Error handling
    * URL parameter management for filters/sorting

## Database Considerations
* No schema changes required
* Existing `sources` table structure is sufficient
* Ensure indexes are optimized for:
    * `knowledge_graph_id` lookups (already indexed)
    * URL searches (already indexed)
    * Title searches (may need full-text index if performance issues)
* Edit existing migrations, don't create new migrations for tables that already exist, I will refresh database.

## Frontend Components

### Onboarding.vue Changes
* Remove `sourcesPreview` ref and related display logic
* Remove results table section

### New Sources.vue Component
* Table component with:
    * Header row with sortable columns
    * Search input field
    * Pagination controls
    * Loading spinner
    * Empty state message
* Props/route params:
    * `knowledgeGraphId` (from route)
* State management:
    * Sources list
    * Current page
    * Search query
    * Sort column and order
    * Loading state
    * Error state

## API Endpoints

### GET /api/knowledge-graphs/:id/sources
**Query Parameters:**
- `page` (integer, default: 1)
- `per_page` (integer, default: 25, max: 100)
- `search` (string, optional) - searches in URL and title
- `sort_by` (string, optional) - one of: `title`, `url`, `discovered_at`
- `sort_order` (string, optional) - `asc` or `desc`, default: `asc`

**Response:**
```json
{
    "sources": [
        {
            "id": 1,
            "url": "https://example.com/page1",
            "title": "Page Title",
            "status": "discovered",
            "discovered_at": "2025-11-23T08:00:00.000000Z",
            "created_at": "2025-11-23T08:00:00.000000Z",
            "updated_at": "2025-11-23T08:00:00.000000Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 25,
        "total": 150,
        "last_page": 6,
        "from": 1,
        "to": 25
    }
}
```

## Future Enhancements (Phase 2+)
* Add bulk actions (select multiple sources)
* Add source status filtering (discovered vs failed)
* Add export functionality (CSV, JSON)
* Add source detail view (click to see full details)
* Add ability to manually add/remove sources
* Add source validation/verification status

