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
  public function getCurrentLease(int $property_id) {
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
          '((o.`lease_start_inaugural` > %s AND o.`lease_start_inaugural` <= %s) OR o.`lease_start` <= %s)',
          $this->quote('0000-00-00'),
          $this->quote(date('Y-m-d')),
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
          o.`lease_term` `lease_agreement_type`,
          o.`lease_start`,
          o.`lease_start_inaugural`,
          o.`lease_end`,
          o.`lease_end` `lease_end_absolute`,
          o.`rent`,
          o.`rent_per_month`,
          o.`rent_period`,
          o.`rent_bond`,
          o.`rent_increase`,
          o.`rent_increase_date`,
          o.`rent_increase_rent`,
          o.`rent_increase_period`,
          o.`rent_reference`,
          o.`rent_reference_override`,
          o.`vacate`
        FROM
          `offer_to_lease` o
        WHERE
          %s
        ORDER BY
          `lease_start` DESC,
          `lessor_signature_time` DESC';

      $sql = sprintf(
        $sqlTemplate,
        implode(' AND ', $where)

      );

      if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));

      if ($res = $this->Result($sql)) {
        if ($dto = $res->dto()) {
          $otl = new dvc\offertolease\dao\offer_to_lease;
          $dto->lease_term = (int)$otl->getLeaseTermMonths($dto);

          $sql = sprintf(
            'SELECT
              id,
              lease_type,
              tenants, lease_end, vacate
            FROM
              offer_to_lease o
            WHERE
              %d = o.property_id
              AND DATE(`lessor_signature_time`) > %s
            ORDER BY
              `lessor_signature_time` DESC
            LIMIT
              1',
            $property_id,
            $this->quote('0000-00-00')

          );

          /**
           * check if the lease has been extended ...
           */
          if ($resX = $this->Result($sql)) {
            if ($dtoX = $resX->dto()) {
              if ($dtoX->id != $dto->id) {
                if ('renewal' == $dtoX->lease_type) {
                  if (strtotime($dtoX->lease_end) > strtotime($dto->lease_end)) {
                    $tenantMatch = false;
                    if ( $dtoX->tenants && $dto->tenants) {
                      $nowTenants = array_map(function($el) {
                        return isset( $el->id) ? $el->id : 0;
                      }, (array)json_decode($dto->tenants));
                      $nextTenants = array_map(function($el) {
                        return isset( $el->id) ? $el->id : 0;
                      }, (array)json_decode($dtoX->tenants));

                      foreach ($nextTenants as $_t) if ( in_array( $_t, $nowTenants)) $tenantMatch = true;

                      // \sys::logger( sprintf('<%s> %s', print_r( $nowTenants, true), __METHOD__));
                      // \sys::logger( sprintf('<%s> %s', print_r( $nextTenants, true), __METHOD__));
                      // \sys::logger( sprintf('<%s> %s', $tenantMatch ? 'bingo' : 'bah', __METHOD__));

                    }

                    if ( $tenantMatch) {
                      $dto->lease_end_absolute = $dtoX->lease_end;
                      if ( strtotime( $dtoX->vacate) > strtotime( $dto->vacate)) $dto->vacate = $dtoX->vacate;

                    }
                  }
                }
              }
            }
          }

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

  public function getCurrentLeaseByRentReference(string $reference, string $date = null) {
    $debug = false;
    // $debug = true;

    // $timer = \application::app()->timer();
    $date = $date ?? date('Y-m-d');

    if (class_exists('dvc\offertolease\dao\offer_to_lease')) {

      $where = [];
      $where_autoextend = [];

      $where[] = $_w = sprintf(
        '(o.`rent_reference` = %s OR o.`rent_reference_override` = %s)',
        $this->quote( $reference),
        $this->quote( $reference)
      );
      $where_autoextend[] = $_w;

      $where[] = $_w = sprintf(
        '((o.`lease_start_inaugural` > %s AND o.`lease_start_inaugural` <= %s) OR o.`lease_start` <= %s)',
        $this->quote('0000-00-00'),
        $this->quote(date('Y-m-d')),
        $this->quote(date('Y-m-d'))
      );
      $where_autoextend[] = $_w;

      // just on where, not autoextend
      $where[] = sprintf(
        'o.`lease_end` > %s',
        $this->quote($date)
      );

      // just on where, not autoextend
      $where[] = sprintf(
        '( o.`vacate` IS NULL OR o.`vacate` = %s OR o.`vacate` > %s)',
        $this->quote(date('0000-00-00')),
        $this->quote($date)
      );

      $where[] = $_w = 'NOT o.`lessor_signature` IS NULL';


      $sqlTemplate =
        'SELECT
          o.`id`,
          o.`property_id`,
          o.`address_street`,
          o.`tenants`,
          o.`tenants_approved`,
          o.`tenants_guarantors`,
          o.`lease_term` `lease_agreement_type`,
          o.`lease_start`,
          o.`lease_start_inaugural`,
          o.`lease_end`,
          o.`lease_end` `lease_end_absolute`,
          o.`rent`,
          o.`rent_per_week`,
          o.`rent_per_month`,
          o.`rent_period`,
          o.`rent_bond`,
          o.`vacate`
        FROM
          `offer_to_lease` o
        WHERE
          %s
        ORDER BY
          `lessor_signature_time` DESC,
          `lease_start` DESC';

      $sql = sprintf(
        $sqlTemplate,
        implode(' AND ', $where)

      );

      if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));

      if ($res = $this->Result($sql)) {
        if ($dto = $res->dto()) {
          $otl = new dvc\offertolease\dao\offer_to_lease;
          $dto->lease_term = (int)$otl->getLeaseTermMonths($dto);

          $sql = sprintf(
            'SELECT
              id,
              lease_type,
              tenants, lease_end, vacate
            FROM
              offer_to_lease o
            WHERE
              %d = o.property_id
              AND DATE(`lessor_signature_time`) > %s
            ORDER BY
              `lessor_signature_time` DESC
            LIMIT
              1',
            $dto->property_id,
            $this->quote('0000-00-00')

          );

          /**
           * check if the lease has been extended ...
           */
          if ($resX = $this->Result($sql)) {
            if ($dtoX = $resX->dto()) {
              if ($dtoX->id != $dto->id) {
                if ('renewal' == $dtoX->lease_type) {
                  if (strtotime($dtoX->lease_end) > strtotime($dto->lease_end)) {
                    $tenantMatch = false;
                    if ( $dtoX->tenants && $dto->tenants) {
                      $nowTenants = array_map(function($el) {
                        return isset( $el->id) ? $el->id : 0;
                      }, (array)json_decode($dto->tenants));
                      $nextTenants = array_map(function($el) {
                        return isset( $el->id) ? $el->id : 0;
                      }, (array)json_decode($dtoX->tenants));

                      foreach ($nextTenants as $_t) if ( in_array( $_t, $nowTenants)) $tenantMatch = true;

                      // \sys::logger( sprintf('<%s> %s', print_r( $nowTenants, true), __METHOD__));
                      // \sys::logger( sprintf('<%s> %s', print_r( $nextTenants, true), __METHOD__));
                      // \sys::logger( sprintf('<%s> %s', $tenantMatch ? 'bingo' : 'bah', __METHOD__));

                    }

                    if ( $tenantMatch) {
                      $dto->lease_end_absolute = $dtoX->lease_end;
                      if ( strtotime( $dtoX->vacate) > strtotime( $dto->vacate)) $dto->vacate = $dtoX->vacate;

                    }
                  }
                }
              }
            }
          }

          return $dto;
        } else {
          /**
           * https://cmss.darcy.com.au/forum/view/7932
           * remove the lease end and vacate and try again,
           * this is a periodic continuance of the last lease
           *
           * corrected for https://cmss.darcy.com.au/forum/view/8361
           * the original query would report a previous lease where no vacate had been set
           */
          $sql = sprintf(
            $sqlTemplate,
            implode(' AND ', $where_autoextend)

          );

          if ($res = $this->Result($sql)) {
            if ($dto = $res->dto()) {
              if ( !(strtotime( $dto->vacate) > 0)) { // they are vacating - false alarm
                $otl = new dvc\offertolease\dao\offer_to_lease;
                $dto->lease_term = (int)$otl->getLeaseTermMonths($dto);

                return $dto;

              }
            }
          }
        }
      }
    }

    return null;
  }

  public function getCurrentNoleggio(int $property_id) {
    $debug = false;
    // $debug = true;

    // $timer = \application::app()->timer();

    if (class_exists('cms\noleggio\dao\noleggio')) {
      $where = [
        sprintf(
          'n.`properties_id` = %d',
          $property_id
        ),
        sprintf(
          '((n.`lease_start_inaugural` > %s AND n.`lease_start_inaugural` <= %s) OR n.`lease_start` <= %s)',
          $this->quote('0000-00-00'),
          $this->quote(date('Y-m-d')),
          $this->quote(date('Y-m-d'))
        ),
        'n.`archived` = 0',
        sprintf(
          '(COALESCE( n.`lease_end`, %s) = %s OR DATE(n.`lease_end`) > %s)',
          $this->quote(date('0000-00-00')),
          $this->quote(date('0000-00-00')),
          $this->quote(date('Y-m-d'))
        )

      ];

      $sqlTemplate =
      'SELECT
          n.`id`,
          n.`properties_id`,
          p.`address_street`,
          n.`tenants`,
          n.`lease_start`,
          n.`lease_start_inaugural`,
          n.`lease_end`,
          n.`rent`,
          n.`rent_period`,
          n.`bond`,
          n.`rent_reference`
        FROM
          `noleggio` n
            LEFT JOIN
          `properties` p ON p.`id` = n.`properties_id`
        WHERE
          %s';

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
        }
      }
    }

    return null;
  }
}
