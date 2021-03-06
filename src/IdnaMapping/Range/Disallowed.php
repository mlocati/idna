<?php

namespace MLocati\IDNA\IdnaMapping\Range;

use Exception;
use MLocati\IDNA\IdnaMapping\TableRow;

/**
 * The code point is not allowed.
 */
class Disallowed extends Range
{
    /**
     * Initializes the instance.
     *
     * @param \MLocati\IDNA\IdnaMapping\TableRow $row
     *
     * @throws \Exception
     */
    public function __construct(TableRow $row)
    {
        parent::__construct($row);
        if ($row->mapping !== null) {
            throw new Exception('Mapping field unexpected in disallowed ranges');
        }
        if ($row->statusIDNA2008 !== '') {
            throw new Exception('IDNA2008 status field unexpected in disallowed ranges');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \MLocati\IDNA\IdnaMapping\Range\Range::isCompatibleWith()
     */
    protected function isCompatibleWith(Range $range)
    {
        return $range instanceof self;
    }
}
