<?php

declare(strict_types=1);

namespace MageStack\Integration\Model\Config;

use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Module\Dir;
use Magento\Framework\Module\Dir\Reader;

class SchemaLocator implements SchemaLocatorInterface
{
    private string $schema;

    public function __construct(
        Reader $moduleReader,
        string $moduleName = 'MageStack_Integration',
        string $fileName = 'int_api.xsd'
    ) {
        $this->schema = $moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, $moduleName)
            . DIRECTORY_SEPARATOR . $fileName;
    }

    public function getSchema(): string
    {
        return $this->schema;
    }

    public function getPerFileSchema(): string
    {
        return $this->schema;
    }
}
