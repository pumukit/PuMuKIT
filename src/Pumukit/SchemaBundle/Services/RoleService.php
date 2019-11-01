<?php

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Role;

class RoleService
{
    private $documentManager;
    private $locales;

    public function __construct(DocumentManager $documentManager, array $locales = ['en'])
    {
        $this->documentManager = $documentManager;
        $this->locales = $locales;
    }

    public function exportAllToCsv(): string
    {
        $csv = $this->generateCsvHeader();

        return $this->generateCsvContent($csv);
    }

    private function generateCsvHeader(): string
    {
        $csv = ['cod', 'xml', 'display'];
        foreach ($this->locales as $language) {
            $csv[] = 'name_'.$language;
        }
        foreach ($this->locales as $language) {
            $csv[] = 'text_'.$language;
        }

        return implode(';', $csv).PHP_EOL;
    }

    private function generateCsvContent(string $csv): string
    {
        $roles = $this->documentManager->getRepository(Role::class)->findAll();
        foreach ($roles as $rol) {
            $dataCSV = [];
            $dataCSV[] = $rol->getCod();
            $dataCSV[] = $rol->getXML();
            $dataCSV[] = (int) $rol->getDisplay();
            foreach ($this->locales as $language) {
                $dataCSV[] = $rol->getName($language);
            }
            foreach ($this->locales as $language) {
                $dataCSV[] = $rol->getText($language);
            }
            $csv .= implode(';', $dataCSV).PHP_EOL;
        }

        return $csv;
    }
}
