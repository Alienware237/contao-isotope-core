<?php

namespace Isotope;

use Contao\CoreBundle\Doctrine\Schema\SchemaManager as ContaoSchemaManager;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * DatabaseUpdater automatically performs safe or necessary database updates on config changes.
 */
class DatabaseUpdater
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct()
    {
        $this->connection = System::getContainer()->get('database_connection');
    }

    /**
     * Automatically add and update columns and keys for specific tables.
     */
    public function autoUpdateTables(array $arrTables): void
    {
        $container = System::getContainer();

        // Use the correct service ID for Contao 5
        if (!$container->has('contao.doctrine.schema_manager')) {
            return;
        }

        /** @var \Contao\CoreBundle\Doctrine\Schema\SchemaManager $schemaManager */
        $schemaManager = $container->get('contao.doctrine.schema_manager');

        // Get the update commands (this returns an array of SQL statements)
        $commands = $schemaManager->getSchemaUpdateCommands();

        foreach ($commands as $strCommand) {
            foreach ($arrTables as $strTable) {
                // Check if the SQL command affects our specific table
                if (str_contains($strCommand, $strTable)) {
                    $this->runQuery($strCommand, $strTable);
                }
            }
        }
    }

    private function runQuery(string $strCommand, string $strTable): void
    {
        // Execute Index changes
        if (preg_match("/^(CREATE|DROP) INDEX [\w`]+ ON $strTable/i", $strCommand)) {
            $this->connection->executeStatement($strCommand);
            return;
        }

        // Execute Table alterations (with pre-checks for data integrity)
        if (str_starts_with($strCommand, "ALTER TABLE $strTable ")) {
            $this->fixStringToInt($strCommand, $strTable);
            $this->fixNullValues($strCommand, $strTable);

            try {
                $this->connection->executeStatement($strCommand);
            } catch (\Exception $e) {
                // Log error if a specific command fails but continue with others
                System::getContainer()->get('monolog.logger.contao')->error('Isotope DatabaseUpdate failed: ' . $e->getMessage());
            }
        }
    }

    private function fixStringToInt(string $strCommand, string $strTable): void
    {
        if (!preg_match('/ `?(\w+)`? (INT DEFAULT 0 NOT NULL|int\(10\) NOT NULL default 0)$/i', $strCommand, $match)) {
            return;
        }

        $sm = $this->connection->createSchemaManager();
        $columns = $sm->listTableColumns($strTable);

        if (!isset($columns[strtolower($match[1])]) || !$this->isStringType($columns[strtolower($match[1])]->getType())) {
            return;
        }

        $this->connection->executeStatement("UPDATE `$strTable` SET `$match[1]`='0' WHERE `$match[1]`='' OR `$match[1]` IS NULL");
    }

    private function fixNullValues(string $strCommand, string $strTable): void
    {
        if (!preg_match("/^ALTER TABLE $strTable (CHANGE|MODIFY) `?(\w+)`? .+ NOT NULL/i", $strCommand, $match)) {
            return;
        }

        $this->connection->executeStatement("UPDATE `$strTable` SET `$match[2]`='' WHERE `$match[2]` IS NULL");
    }

    private function isStringType(Type $type): bool
    {
        return \in_array($type->getName(), [Types::STRING, Types::TEXT], true);
    }
}
