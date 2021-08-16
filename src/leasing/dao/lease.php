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
use sys, dvc;

class lease extends _dao {
  function getCurrentLease(int $property_id) {
    $debug = false;
    // $debug = true;

    // $timer = \application::app()->timer();

    if (class_exists('dvc\offertolease\dao\offer_to_lease')) {
      /**
       * pay attention
       * the lease_end parameter is on the end of the $where array
       **/
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
          '( o.`vacate` IS NULL OR o.`vacate` = %s OR o.`vacate` > %s)',
          $this->quote(date('0000-00-00')),
          $this->quote(date('Y-m-d'))
        ),
        'NOT o.`lessor_signature` IS NULL',
        sprintf(
          'o.`lease_end` > %s',
          $this->quote(date('Y-m-d'))
        )

      ];
      /**
       * pay attention
       * the lease_end parameter is on the end of the $where array
       **/

      $sqlTemplate =
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
          o.`rent`,
          o.`rent_bond`,
          o.`vacate`
        FROM
          `offer_to_lease` o
        WHERE
          %s
        ORDER BY `lease_start` DESC';

      $sql = sprintf(
        $sqlTemplate,
        implode(' AND ', $where)

      );

      if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));

      if ($res = $this->Result($sql)) {
        if ($dto = $res->dto()) {
          $otl = new dvc\offertolease\dao\offer_to_lease;
          $dto->lease_term = (int)$otl->getLeaseTermMonths($dto);

          return $dto;
        } else {
          /**
           * https://cmss.darcy.com.au/forum/view/7932
           * remove the last parameter, and try again,
           * this is a periodic continuance of the last lease
           */

          /**
           * pay attention
           * the lease_end parameter is on the end of the $where array
           **/

          array_pop($where);
          $sql = sprintf(
            $sqlTemplate,
            implode(' AND ', $where)

          );

          if ($res = $this->Result($sql)) {
            if ($dto = $res->dto()) {
              $otl = new dvc\offertolease\dao\offer_to_lease;
              $dto->lease_term = (int)$otl->getLeaseTermMonths($dto);

              return $dto;
            }
          }
        }
      }
    }

    return null;
  }
}
