<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\leasing\dao;

use dao\_dao;
use sys;

class lease extends _dao {
  function getCurrentLease(int $property_id) {
    $debug = false;
    // $debug = true;

    $timer = \application::app()->timer();

    $where = [
      sprintf('`property_id` = %d', $property_id),
      sprintf('`lease_start` <= %s', $this->quote(date('Y-m-d'))),
      sprintf('`lease_end` > %s', $this->quote(date('Y-m-d'))),
      sprintf('( `vacate` IS NULL OR `vacate` = %s OR `vacate` > %s)', $this->quote(date('0000-00-00')), $this->quote(date('Y-m-d'))),
      'NOT `lessor_signature` IS NULL'

    ];

    $sql = sprintf(
      'SELECT
        `id`,
        `property_id`,
        `address_street`,
        `tenants`,
        `tenants_approved`,
        `tenants_guarantors`,
        `lease_start`,
        `lease_start_inaugural`,
        `lease_end`,
        `vacate`
      FROM
        `offer_to_lease`
      WHERE
        %s
      ORDER BY `lease_start` DESC',
      implode(' AND ', $where)

    );

    if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));

    if ($res = $this->Result($sql)) {
      return $res->dto();
    }

    return null;
  }

}
