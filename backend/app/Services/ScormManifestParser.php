<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use RuntimeException;

class ScormManifestParser
{
    private const SCORM12_NAMESPACE = 'http://www.imsproject.org/xsd/imscp_rootv1p1p2';
    private const ADLCP_NAMESPACE = 'http://www.adlnet.org/xsd/adlcp_rootv1p2';

    /**
     * Parse an imsmanifest.xml string and return structured manifest data.
     *
     * @return array{
     *   identifier: string,
     *   title: string,
     *   description: string,
     *   version: string,
     *   organizations: list<array{identifier: string, title: string}>,
     *   scos: list<array{identifier: string, title: string, href: string, sco_type: string}>,
     *   launch_path: string,
     *   sco_count: int,
     *   metadata: array<string, string>,
     * }
     */
    public function parse(string $xmlContent): array
    {
        $doc = new DOMDocument;
        $doc->loadXML($xmlContent, LIBXML_NOWARNING | LIBXML_NOERROR);

        $xpath = new DOMXPath($doc);
        $this->registerNamespaces($xpath, $doc);

        $identifier = $this->attr($doc->documentElement, 'identifier', '');
        $version = $this->attr($doc->documentElement, 'version', '1.0');

        $metadata = $this->parseMetadata($xpath);
        $organizations = $this->parseOrganizations($xpath);
        $resources = $this->parseResources($xpath);
        $scos = $this->mapScos($organizations, $resources);

        if (empty($scos)) {
            throw new RuntimeException('No launchable SCOs found in manifest.');
        }

        $title = $metadata['title'] ?? ($organizations[0]['title'] ?? 'Untitled');

        return [
            'identifier' => $identifier,
            'title' => $title,
            'description' => $metadata['description'] ?? '',
            'version' => $version,
            'organizations' => $organizations,
            'scos' => $scos,
            'launch_path' => $scos[0]['href'],
            'sco_count' => count($scos),
            'metadata' => $metadata,
        ];
    }

    /** @return array<string, string> */
    private function parseMetadata(DOMXPath $xpath): array
    {
        $metadata = [];

        $titleNode = $xpath->query('//imsmd:general/imsmd:title/imsmd:langstring')
            ?? $xpath->query('//metadata/lom/general/title/langstring');

        if ($titleNode && $titleNode->length > 0) {
            $metadata['title'] = trim($titleNode->item(0)->nodeValue ?? '');
        }

        $descNode = $xpath->query('//imsmd:general/imsmd:description/imsmd:langstring')
            ?? $xpath->query('//metadata/lom/general/description/langstring');

        if ($descNode && $descNode->length > 0) {
            $metadata['description'] = trim($descNode->item(0)->nodeValue ?? '');
        }

        return array_filter($metadata);
    }

    /** @return list<array{identifier: string, title: string}> */
    private function parseOrganizations(DOMXPath $xpath): array
    {
        $organizations = [];

        $defaultOrgId = '';
        $orgsNode = $xpath->query('//*[local-name()="organizations"]');
        if ($orgsNode && $orgsNode->length > 0) {
            $defaultOrgId = $this->attr($orgsNode->item(0), 'default', '');
        }

        $orgNodes = $xpath->query('//*[local-name()="organization"]');
        if (! $orgNodes) {
            return $organizations;
        }

        foreach ($orgNodes as $org) {
            $orgId = $this->attr($org, 'identifier', '');
            $titleNode = $xpath->query('*[local-name()="title"]', $org);
            $title = ($titleNode && $titleNode->length > 0) ? trim($titleNode->item(0)->nodeValue ?? '') : 'Course';

            $organizations[] = [
                'identifier' => $orgId,
                'title' => $title,
                'is_default' => ($orgId === $defaultOrgId || empty($defaultOrgId)),
            ];
        }

        return $organizations;
    }

    /** @return array<string, array{identifier: string, href: string, sco_type: string, files: list<string>}> */
    private function parseResources(DOMXPath $xpath): array
    {
        $resources = [];

        $resourceNodes = $xpath->query('//*[local-name()="resource"]');
        if (! $resourceNodes) {
            return $resources;
        }

        foreach ($resourceNodes as $resource) {
            $id = $this->attr($resource, 'identifier', '');
            $href = $this->attr($resource, 'href', '');
            $scormType = $this->attrNs($resource, 'scormtype', self::ADLCP_NAMESPACE)
                ?? $this->attr($resource, 'adlcp:scormtype', '');

            if (empty($id)) {
                continue;
            }

            $files = [];
            $fileNodes = $xpath->query('*[local-name()="file"]', $resource);
            if ($fileNodes) {
                foreach ($fileNodes as $file) {
                    $fileHref = $this->attr($file, 'href', '');
                    if ($fileHref !== '') {
                        $files[] = $fileHref;
                    }
                }
            }

            $resources[$id] = [
                'identifier' => $id,
                'href' => $href,
                'sco_type' => strtolower($scormType),
                'files' => $files,
            ];
        }

        return $resources;
    }

    /**
     * Walk all item elements and map them to SCO resources.
     *
     * @param  list<array{identifier: string, title: string}>  $organizations
     * @param  array<string, array{identifier: string, href: string, sco_type: string, files: list<string>}>  $resources
     * @return list<array{identifier: string, title: string, href: string, sco_type: string}>
     */
    private function mapScos(array $organizations, array $resources): array
    {
        // Build map of items from all organizations by re-querying via a fresh xpath walk
        // We'll resolve SCOs: items whose identifierref points to a sco resource
        $scos = [];

        foreach ($resources as $resource) {
            if ($resource['sco_type'] === 'sco' && $resource['href'] !== '') {
                $scos[] = [
                    'identifier' => $resource['identifier'],
                    'title' => $resource['identifier'],  // will be overridden by item title below
                    'href' => $resource['href'],
                    'sco_type' => 'sco',
                ];
            }
        }

        return $scos;
    }

    /**
     * Parse organizations and items to get proper SCO titles.
     * This is a second-pass that enriches scos[] with titles from <item> elements.
     *
     * @return list<array{identifier: string, title: string, href: string, sco_type: string}>
     */
    public function enrichScoTitles(string $xmlContent, array $scos): array
    {
        $doc = new DOMDocument;
        $doc->loadXML($xmlContent, LIBXML_NOWARNING | LIBXML_NOERROR);
        $xpath = new DOMXPath($doc);
        $this->registerNamespaces($xpath, $doc);

        // Map identifierref → title from item nodes
        $itemTitles = [];
        $itemNodes = $xpath->query('//*[local-name()="item"]');
        if ($itemNodes) {
            foreach ($itemNodes as $item) {
                $identifierref = $this->attr($item, 'identifierref', '');
                if ($identifierref === '') {
                    continue;
                }
                $titleNode = $xpath->query('*[local-name()="title"]', $item);
                if ($titleNode && $titleNode->length > 0) {
                    $title = trim($titleNode->item(0)->nodeValue ?? '');
                    if ($title !== '') {
                        $itemTitles[$identifierref] = $title;
                    }
                }
            }
        }

        return array_map(function (array $sco) use ($itemTitles): array {
            if (isset($itemTitles[$sco['identifier']])) {
                $sco['title'] = $itemTitles[$sco['identifier']];
            }
            return $sco;
        }, $scos);
    }

    private function registerNamespaces(DOMXPath $xpath, DOMDocument $doc): void
    {
        $root = $doc->documentElement;
        if (! $root) {
            return;
        }

        // Register common SCORM 1.2 namespaces
        $xpath->registerNamespace('ims', self::SCORM12_NAMESPACE);
        $xpath->registerNamespace('adlcp', self::ADLCP_NAMESPACE);
        $xpath->registerNamespace('imsmd', 'http://www.imsproject.org/xsd/imsmd_rootv1p2p1');

        // Also discover and register any namespaces declared on the root element
        if ($root->attributes) {
            foreach ($root->attributes as $attr) {
                if (str_starts_with($attr->nodeName, 'xmlns:')) {
                    $prefix = substr($attr->nodeName, 6);
                    if (! in_array($prefix, ['ims', 'adlcp', 'imsmd'], true)) {
                        try {
                            $xpath->registerNamespace($prefix, $attr->nodeValue ?? '');
                        } catch (\Throwable) {
                            // ignore invalid namespace declarations
                        }
                    }
                }
            }
        }
    }

    private function attr(\DOMNode $node, string $name, string $default = ''): string
    {
        if ($node instanceof \DOMElement) {
            return $node->hasAttribute($name) ? ($node->getAttribute($name) ?? $default) : $default;
        }
        return $default;
    }

    private function attrNs(\DOMNode $node, string $localName, string $namespace): ?string
    {
        if ($node instanceof \DOMElement) {
            $value = $node->getAttributeNS($namespace, $localName);
            return ($value !== '' && $value !== null) ? $value : null;
        }
        return null;
    }
}
