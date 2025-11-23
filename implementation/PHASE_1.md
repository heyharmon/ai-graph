Phase 1: Knowledge Graph Foundation
Objective
Build a system that automatically creates a visual, interactive semantic map of any business by analyzing their website content.
Core Capabilities
1. Website Crawling
* Crawl website using Exa
* Identify page types (service pages, location pages, about pages, blog posts, etc.)
2. AI-Powered Entity Extraction
* Analyze Exa website data using LLM
* Extract key business entities:
    * Products/Services: What the business offers
    * Locations: Geographic areas served (cities, regions, states)
    * Customer Types: Target audiences, industries, personas
    * Attributes: Key characteristics (free/paid, certifications, specializations, delivery methods)
    * Competitors: Mentioned or implied competitive landscape
* Normalize entities (e.g., "Denver, CO" and "Denver" become one entity) (v2)
3. Relationship Mapping
* Determine connections between entities:
    * Which services are offered in which locations
    * Which customer types use which services
    * Which attributes apply to which products/services
    * Which locations are grouped into regions
* Calculate relationship strength based on content co-occurrence (v2)
* Build directional graph structure
4. Knowledge Graph Database
* Store entities with types, attributes, and metadata including source url
* Store relationships (with source URLs?)
* Enable querying: "Show all services in Denver" or "What customer types use emergency services?" (v2)
* Support graph updates
* Support graph versioning (v2)
5. Interactive Visualization
* Present graph as interactive visual map
* Node size reflects content coverage or importance (v2)
* Edge thickness reflects relationship strength
* Click nodes to explore connections and see source evidence
* Filter by entity type
* Search and highlight specific entities
6. Business Intelligence Dashboard
* Summary metrics:
    * Total entities discovered by type
    * Graph density and connectivity
    * Content coverage distribution
    * Potential gaps (disconnected entities)
* Entity detail views showing:
    * All relationships
    * Source pages mentioning this entity
    * Attribute summary
* Gap analysis: services without locations, locations without customer targeting, etc. (v2)
User Flow
1. User enters their website URL
2. System crawls website (shows progress)
3. AI processes content and extracts entities (shows progress)
4. User sees interactive knowledge graph
5. User explores nodes, relationships, and source evidence
6. User identifies gaps in business narrative
7. User can re-crawl to update graph as site changes
Success Criteria
* Accurately extract 80%+ of obvious business entities from website
* Generate meaningful relationship connections
* Render graph that loads in <3 seconds for typical business (50-200 pages)
* Provide actionable insights about business structure gaps
* Users can understand their business semantically at a glance
Technical Considerations
* Handle websites of varying sizes (10 pages to 1,000 pages)
* Process within reasonable timeframe (5-10 minutes for typical site)
* Store graph data efficiently for fast querying
* LLM prompting strategy for consistent entity extraction
* Graph visualization performance with 100+ nodes
* Handle website updates and re-crawling