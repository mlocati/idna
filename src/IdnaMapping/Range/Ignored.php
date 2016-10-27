<?php

namespace MLocati\IDNA\IdnaMapping\Range;

use MLocati\IDNA\IdnaMapping\TableRow;
use Exception;

/**
 * The code point is removed: this is equivalent to mapping the code point to an empty string.
 */
class Ignored extends Range
{
    /**
     * Initializes the instance.
     *
     * @param TableRow $row
     *
     * @throws Exception
     */
    public function __construct(TableRow $row)
    {
        parent::__construct($row);
        if ($row->mapping !== null) {
            throw new Exception('Mapping field unexpected in ignored ranges');
        }
        if ($row->statusIDNA2008 !== '') {
            throw new Exception('IDNA2008 Status field unexpected in ignored ranges');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see Range::isCompatible()
     */
    protected function isCompatibleWith(Range $range)
    {
        return $range instanceof self;
    }
}
