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
use dvc\offertolease\dao\offer_to_lease;
use sys;

class lease extends _dao {
  function getCurrentLease(int $property_id) {
    $debug = false;
    // $debug = true;

    $timer = \application::app()->timer();

    $where = [
      sprintf(
        'o.`property_id` = %d',
        $property_id
      ),
      sprintf(
        'o.`lease_start` <= %s',
        $this->quote(date('Y-m-d'))
      ),
      sprintf(
        'o.`lease_end` > %s',
        $this->quote(date('Y-m-d'))
      ),
      sprintf(
        '( o.`vacate` IS NULL OR o.`vacate` = %s OR o.`vacate` > %s)',
        $this->quote(date('0000-00-00')),
        $this->quote(date('Y-m-d'))
      ),
      'NOT o.`lessor_signature` IS NULL'

    ];

    $sql = sprintf(
      'SELECT
        o.`id`,
        o.`property_id`,
        o.`address_street`,
        o.`tenants`,
        o.`tenants_approved`,
        o.`tenants_guarantors`,
        o.`lease_start`,
        o.`lease_start_inaugural`,
        o.`lease_end`,
        o.`vacate`
      FROM
        `offer_to_lease` o
      WHERE
        %s
      ORDER BY `lease_start` DESC',
      implode(' AND ', $where)

    );

    if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));

    if ($res = $this->Result($sql)) {
      if ($dto = $res->dto()) {
        $otl = new offer_to_lease;
        $dto->lease_term = (int)$otl->getLeaseTermMonths($dto);

        return $dto;
      }
    }

    return null;
  }
}
