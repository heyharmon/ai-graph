# Phase 3: Entity Extraction & Knowledge Graph

## Objective
Extract business entities from crawled website pages using Exa Answer API and build the knowledge graph structure. Present entities in a robust table format for exploration and analysis.

## Core Capabilities

### 1. AI-Powered Entity Extraction
* Use Exa Answer API to extract entities from website sources
* Extract key business entities:
    * Products/Services: What the business offers
    * Locations: Geographic areas served (cities, regions, states)
    * Customer Types: Target audiences, industries, personas
    * Attributes: Key characteristics (free/paid, certifications, specializations, delivery methods)
    * Competitors: Mentioned or implied competitive landscape
* Process entities page by page, linking each entity to its source(s)
* Normalize entities (e.g., "Denver, CO" and "Denver" become one entity) (v2)

### 2. Entity Storage & Relationships
* Store entities with types, attributes, and metadata
* Link entities to their source pages (many-to-many relationship)
* Build relationship connections between entities:
    * Which services are offered for certain products
    * Which attributes apply to which products/services
    * Which state does a city belong to
* Store directional graph structure for future visualization

### 3. Onboarding Entity Extraction
* Continue onboarding flow from Phase 1
* Show entity extraction progress for first 10 pages
* Display entities discovered during onboarding preview
* Complete onboarding after showing extraction results
* Direct user to main entities table page

### 4. Entities Table View
* Present all extracted entities in a robust, sortable table
* Table columns:
    * Entity name
    * Entity type (Product/Service, Location, Customer Type, Attribute, Competitor)
    * Related entities (links/relationships)
    * Source count (number of pages mentioning this entity)
    * Source links (expandable list of URLs)
    * Attributes/metadata
* Filtering capabilities:
    * Filter by entity type
    * Search by entity name
    * Filter by source URL
* Sorting by name, type, source count
* Pagination for large entity sets

### 5. Entity Detail View
* Click entity row to see detailed view
* Show all relationships (connected entities)
* List all source pages mentioning this entity
* Display entity attributes and metadata
* Show extraction confidence/evidence (future enhancement)

## User Flow
1. User completes Phase 1 onboarding (website crawl)
2. System begins entity extraction for first 10 pages (shows progress)
3. User sees preview of entities discovered during onboarding
4. Onboarding completes, user directed to entities table
5. System continues extracting entities from remaining pages (background process)
6. User explores entities table:
    * Views all entities
    * Filters by type or searches
    * Clicks entity to see details and relationships
    * Views source pages for each entity
7. User can trigger re-extraction when website changes
8. User can submit one-off requests to Exa Answer to update graph

## Success Criteria
* Accurately extract 80%+ of obvious business entities from website pages
* Successfully link entities to their source pages
* Generate meaningful relationship connections between entities
* Display entities table that loads in <2 seconds for typical business (50-200 entities)
* Process entity extraction within reasonable timeframe (5-10 minutes for typical site)
* Provide clear entity detail views with relationships and sources

## Technical Considerations
* Handle websites of varying sizes (10 pages to 1,000 pages)
* Process entity extraction efficiently (batch processing, queue jobs)
* Store graph data efficiently for fast querying
* LLM prompting strategy for consistent entity extraction
* Handle Exa Answer API rate limits and errors
* Store entity relationships for future graph visualization (Phase 3)
* Normalize entity names to prevent duplicates
* Track extraction status per source page

## Database Schema
* `entities` table:
    * `id` (primary key)
    * `name` (indexed)
    * `type` (enum: product_service, location, customer_type, attribute, competitor)
    * `normalized_name` (for deduplication)
    * `attributes` (JSON)
    * `metadata` (JSON)
    * `created_at` (timestamp)
    * `updated_at` (timestamp)
* `source_entity` pivot table:
    * `source_id` (foreign key to sources)
    * `entity_id` (foreign key to entities)
    * `created_at` (timestamp)
* `entity_relationships` table:
    * `id` (primary key)
    * `source_entity_id` (foreign key to entities)
    * `target_entity_id` (foreign key to entities)
    * `relationship_type` (enum: offers, applies_to, belongs_to, etc.)
    * `created_at` (timestamp)

## Future Enhancements (Phase 3+)
* Interactive graph visualization
* Business intelligence dashboard
* Advanced relationship mapping
* Entity normalization improvements
* Query interface ("Show all services in Denver")
