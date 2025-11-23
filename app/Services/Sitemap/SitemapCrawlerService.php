<?php

namespace App\Services\Sitemap;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleXMLElement;

class SitemapCrawlerService
{
    private const MAX_SITEMAP_FETCHES = 30;
    private const MAX_URLS = 1000; // Phase 1 limit
    private const MAX_CONSECUTIVE_FETCH_FAILURES = 3;
    private const REQUEST_TIMEOUT_SECONDS = 8;
    private const ROBOTS_TIMEOUT_SECONDS = 5;

    /**
     * Crawl sitemap(s) and return array of sources with URL and optional title
     * 
     * @param string $websiteUrl
     * @return array Array of ['url' => string, 'title' => string|null]
     */
    public function crawl(string $websiteUrl): array
    {
        Log::info('Starting sitemap crawl', ['website_url' => $websiteUrl]);
        
        $normalizedBase = $this->normalizeWebsite($websiteUrl);
        if (!$normalizedBase) {
            Log::warning('Failed to normalize website URL', ['website_url' => $websiteUrl]);
            return [];
        }

        Log::info('Normalized website URL', ['normalized_base' => $normalizedBase]);

        $queue = $this->initialSitemapCandidates($normalizedBase);
        Log::info('Initial sitemap candidates', [
            'count' => count($queue),
            'candidates' => $queue,
        ]);

        $visited = [];
        $collected = [];
        $consecutiveFailures = 0;

        while (!empty($queue) && count($visited) < self::MAX_SITEMAP_FETCHES && count($collected) < self::MAX_URLS) {
            $sitemapUrl = array_shift($queue);
            if (!$sitemapUrl || isset($visited[$sitemapUrl])) {
                if (isset($visited[$sitemapUrl])) {
                    Log::debug('Skipping already visited sitemap', ['url' => $sitemapUrl]);
                }
                continue;
            }

            $visited[$sitemapUrl] = true;
            Log::info('Processing sitemap', [
                'url' => $sitemapUrl,
                'visited_count' => count($visited),
                'collected_count' => count($collected),
            ]);

            $xml = $this->fetchXml($sitemapUrl);
            if (!$xml instanceof SimpleXMLElement) {
                $consecutiveFailures++;
                Log::warning('Failed to fetch/parse XML, incrementing consecutive failures', [
                    'url' => $sitemapUrl,
                    'consecutive_failures' => $consecutiveFailures,
                    'max_allowed' => self::MAX_CONSECUTIVE_FETCH_FAILURES,
                ]);
                if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FETCH_FAILURES) {
                    Log::error('Max consecutive failures reached, stopping crawl', [
                        'consecutive_failures' => $consecutiveFailures,
                    ]);
                    break;
                }

                continue;
            }

            Log::info('Successfully fetched and parsed XML', ['url' => $sitemapUrl]);
            $consecutiveFailures = 0;

            $rootName = strtolower($xml->getName());
            Log::info('XML root element detected', [
                'url' => $sitemapUrl,
                'root_name' => $rootName,
            ]);

            if ($rootName === 'sitemapindex') {
                $childSitemaps = $this->extractLocValues($xml, 'sitemap');
                Log::info('Found sitemapindex, extracted child sitemaps', [
                    'url' => $sitemapUrl,
                    'child_sitemap_count' => count($childSitemaps),
                    'child_sitemaps' => $childSitemaps,
                ]);
                foreach ($childSitemaps as $childUrl) {
                    if ($childUrl && !isset($visited[$childUrl])) {
                        $queue[] = $childUrl;
                        Log::debug('Added child sitemap to queue', ['child_url' => $childUrl]);
                    }
                }
                continue;
            }

            if ($rootName === 'urlset') {
                $urls = $this->extractUrlEntries($xml);
                Log::info('Found urlset, extracted URL entries', [
                    'url' => $sitemapUrl,
                    'url_entry_count' => count($urls),
                ]);
                foreach ($urls as $entry) {
                    $url = $this->normalizeUrl($entry['url'], $normalizedBase);
                    if ($url === '' || isset($collected[$url])) {
                        if ($url === '') {
                            Log::debug('Skipping empty URL', ['entry' => $entry]);
                        } elseif (isset($collected[$url])) {
                            Log::debug('Skipping duplicate URL', ['url' => $url]);
                        }
                        continue;
                    }
                    $collected[$url] = [
                        'url' => $url,
                        'title' => $entry['title'] ?? null,
                    ];
                    if (count($collected) >= self::MAX_URLS) {
                        Log::info('Reached max URLs limit, stopping collection', [
                            'max_urls' => self::MAX_URLS,
                            'collected_count' => count($collected),
                        ]);
                        break 2;
                    }
                }
            } else {
                Log::warning('Unknown XML root element, skipping', [
                    'url' => $sitemapUrl,
                    'root_name' => $rootName,
                ]);
            }
        }

        $resultCount = count($collected);
        Log::info('Sitemap crawl completed', [
            'total_sources_collected' => $resultCount,
            'sitemaps_visited' => count($visited),
            'visited_urls' => array_keys($visited),
        ]);

        return array_values($collected);
    }

    private function normalizeWebsite(?string $website): ?string
    {
        if (!$website) {
            return null;
        }

        $website = trim($website);
        if ($website === '') {
            return null;
        }

        if (!Str::startsWith($website, ['http://', 'https://'])) {
            $website = 'https://' . ltrim($website, '/');
        }

        return rtrim($website, '/');
    }

    private function normalizeUrl(string $url, string $baseUrl): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        // If already absolute, return as-is
        if (Str::startsWith($url, ['http://', 'https://'])) {
            return rtrim($url, '/');
        }

        // Convert relative URL to absolute
        $baseUrl = rtrim($baseUrl, '/');
        $url = ltrim($url, '/');
        
        return $baseUrl . '/' . $url;
    }

    private function initialSitemapCandidates(string $baseUrl): array
    {
        $candidates = [
            $baseUrl . '/sitemap.xml',
            $baseUrl . '/sitemap_index.xml',
            $baseUrl . '/sitemap-index.xml',
            $baseUrl . '/sitemap/index.xml',
            $baseUrl . '/wp-sitemap.xml',
        ];

        $robots = $this->fetchRobotsSitemaps($baseUrl);
        $candidates = array_merge($candidates, $robots);

        return array_values(array_unique(array_filter($candidates)));
    }

    private function fetchRobotsSitemaps(string $baseUrl): array
    {
        $robotsUrl = $baseUrl . '/robots.txt';
        Log::debug('Checking robots.txt for sitemap references', ['robots_url' => $robotsUrl]);

        try {
            $response = Http::timeout(self::ROBOTS_TIMEOUT_SECONDS)->accept('text/plain')->get($robotsUrl);
        } catch (\Throwable $e) {
            Log::debug('Failed to fetch robots.txt', [
                'robots_url' => $robotsUrl,
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        if (!$response->successful()) {
            Log::debug('robots.txt request failed', [
                'robots_url' => $robotsUrl,
                'status' => $response->status(),
            ]);
            return [];
        }

        $sitemaps = [];
        foreach (explode("\n", $response->body()) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (Str::startsWith(Str::lower($line), 'sitemap:')) {
                $sitemapUrl = trim(Str::after($line, ':'));
                if ($sitemapUrl) {
                    if (!Str::startsWith($sitemapUrl, ['http://', 'https://'])) {
                        $sitemapUrl = $baseUrl . '/' . ltrim($sitemapUrl, '/');
                    }
                    $sitemaps[] = $sitemapUrl;
                    Log::debug('Found sitemap reference in robots.txt', ['sitemap_url' => $sitemapUrl]);
                }
            }
        }

        Log::info('Finished checking robots.txt', [
            'robots_url' => $robotsUrl,
            'sitemaps_found' => count($sitemaps),
            'sitemaps' => $sitemaps,
        ]);

        return $sitemaps;
    }

    private function fetchXml(string $url): ?SimpleXMLElement
    {
        Log::debug('Fetching sitemap XML', ['url' => $url]);
        
        try {
            $response = Http::timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->accept('application/xml')
                ->accept('text/xml')
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning('Failed fetching sitemap XML - exception thrown', [
                'url' => $url,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            return null;
        }

        if (!$response->successful()) {
            Log::warning('Sitemap request returned non-200', [
                'url' => $url,
                'status' => $response->status(),
                'response_body_preview' => substr($response->body(), 0, 200),
            ]);
            return null;
        }

        Log::debug('HTTP request successful', [
            'url' => $url,
            'status' => $response->status(),
            'content_length' => strlen($response->body()),
        ]);

        $body = trim($response->body());
        if ($body === '') {
            Log::warning('Sitemap XML response body is empty', ['url' => $url]);
            return null;
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, SimpleXMLElement::class, LIBXML_NOCDATA);
        if ($xml === false) {
            $libxmlErrors = libxml_get_errors();
            Log::warning('Unable to parse sitemap XML', [
                'url' => $url,
                'errors' => array_map(function($error) {
                    return [
                        'level' => $error->level,
                        'code' => $error->code,
                        'message' => trim($error->message),
                        'line' => $error->line,
                    ];
                }, $libxmlErrors),
                'body_preview' => substr($body, 0, 500),
            ]);
            libxml_clear_errors();
            return null;
        }
        libxml_clear_errors();

        Log::debug('Successfully parsed XML', [
            'url' => $url,
            'root_element' => $xml->getName(),
        ]);

        return $xml;
    }

    /**
     * Extract URL entries with optional titles from urlset XML
     * 
     * @param SimpleXMLElement $xml
     * @return array Array of ['url' => string, 'title' => string|null]
     */
    private function extractUrlEntries(SimpleXMLElement $xml): array
    {
        $prefix = $this->registerDefaultNamespace($xml);
        
        // Try different XPath patterns for url nodes
        $urlPaths = ["//url"];
        if ($prefix) {
            $urlPaths[] = "//{$prefix}:url";
        }

        Log::debug('Extracting URL entries from urlset', [
            'namespace_prefix' => $prefix,
            'url_xpaths' => $urlPaths,
        ]);

        $entries = [];
        foreach ($urlPaths as $urlPath) {
            $urlNodes = $xml->xpath($urlPath);
            if (empty($urlNodes)) {
                Log::debug('No URL nodes found with XPath', ['xpath' => $urlPath]);
                continue;
            }

            Log::debug('Found URL nodes', [
                'xpath' => $urlPath,
                'node_count' => count($urlNodes),
            ]);

            foreach ($urlNodes as $urlNode) {
                // Extract loc (URL) - required field
                // Get namespaces to access children properly
                $namespaces = $urlNode->getNamespaces(true);
                $defaultNs = $namespaces[''] ?? null;
                
                $url = null;
                
                // Try accessing via default namespace children
                if ($defaultNs) {
                    $children = $urlNode->children($defaultNs);
                    if (isset($children->loc)) {
                        $url = trim((string) $children->loc);
                    }
                }
                
                // Fallback: try direct access (works if no namespace or default namespace)
                if (!$url && isset($urlNode->loc)) {
                    $url = trim((string) $urlNode->loc);
                }
                
                // Fallback: try XPath with namespace registered
                if (!$url && $prefix) {
                    $urlNode->registerXPathNamespace('ns', $defaultNs ?: $namespaces[$prefix] ?? '');
                    $locNodes = $urlNode->xpath('./ns:loc');
                    if (!empty($locNodes)) {
                        $url = trim((string) $locNodes[0]);
                    }
                }
                
                // Last fallback: try XPath without namespace
                if (!$url) {
                    $locNodes = $urlNode->xpath('./loc');
                    if (!empty($locNodes)) {
                        $url = trim((string) $locNodes[0]);
                    }
                }

                if (!$url) {
                    Log::debug('Failed to extract URL from url node', [
                        'has_default_ns' => !empty($defaultNs),
                        'default_ns' => $defaultNs,
                        'all_namespaces' => array_keys($namespaces),
                    ]);
                    continue;
                }

                // Extract title if present (optional field)
                $title = null;
                
                // Try accessing via default namespace children
                if ($defaultNs) {
                    $children = $urlNode->children($defaultNs);
                    if (isset($children->title)) {
                        $titleValue = trim((string) $children->title);
                        if ($titleValue !== '') {
                            $title = $titleValue;
                        }
                    }
                }
                
                // Fallback: try direct access
                if (!$title && isset($urlNode->title)) {
                    $titleValue = trim((string) $urlNode->title);
                    if ($titleValue !== '') {
                        $title = $titleValue;
                    }
                }

                $entries[] = [
                    'url' => $url,
                    'title' => $title,
                ];
            }

            if (!empty($entries)) {
                Log::debug('Successfully extracted URL entries', [
                    'xpath' => $urlPath,
                    'entry_count' => count($entries),
                ]);
                break;
            }
        }

        if (empty($entries)) {
            Log::warning('Failed to extract any URL entries from urlset', [
                'url_paths_tried' => $urlPaths,
            ]);
        }

        return $entries;
    }

    /**
     * Extract loc values from XML (for sitemap index)
     * 
     * @param SimpleXMLElement $xml
     * @param string $parentNode
     * @return array
     */
    private function extractLocValues(SimpleXMLElement $xml, string $parentNode): array
    {
        $paths = ["//{$parentNode}/loc"];

        $prefix = $this->registerDefaultNamespace($xml);
        if ($prefix) {
            $paths[] = "//{$prefix}:{$parentNode}/{$prefix}:loc";
            $paths[] = "//{$prefix}:{$parentNode}/loc";
            $paths[] = "//{$parentNode}/{$prefix}:loc";
        }

        Log::debug('Extracting loc values', [
            'parent_node' => $parentNode,
            'namespace_prefix' => $prefix,
            'xpath_paths' => $paths,
        ]);

        foreach ($paths as $path) {
            $nodes = $xml->xpath($path);
            if (empty($nodes)) {
                Log::debug('XPath returned no nodes', ['xpath' => $path]);
                continue;
            }

            $values = [];
            foreach ($nodes as $node) {
                $value = trim((string) $node);
                if ($value !== '') {
                    $values[] = $value;
                }
            }

            if (!empty($values)) {
                Log::debug('Successfully extracted loc values', [
                    'xpath' => $path,
                    'count' => count($values),
                ]);
                return $values;
            }
        }

        Log::warning('Failed to extract loc values with any XPath pattern', [
            'parent_node' => $parentNode,
            'paths_tried' => $paths,
        ]);

        return [];
    }

    private function registerDefaultNamespace(SimpleXMLElement $xml): ?string
    {
        $namespaces = $xml->getNamespaces(true);
        $default = $namespaces[''] ?? null;
        if (!$default && !empty($namespaces)) {
            $default = reset($namespaces);
        }

        if ($default) {
            $prefix = 'sm';
            $xml->registerXPathNamespace($prefix, $default);
            return $prefix;
        }

        return null;
    }
}

