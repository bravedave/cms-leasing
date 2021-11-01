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

use cms\leasing\config;
use dao\_dao;
use strings;
use sys;

class tenants extends _dao {
  function getTenantsOfProperty(int $id): array {

    if ($res = $this->getCurrentTenants($id)) {
      return $res->dtoSet();
    }

    return [];
  }

  function getCurrentTenants(int $property_id = 0) {
    $debug = false;
    // $debug = true;

    $timer = \application::app()->timer();
    if ($debug) sys::logSQL(sprintf('<start %ss> %s', $timer->elapsed(), __METHOD__));

    $dbc = sys::dbCheck('_tens');

    $dbc->temporary = true;
    $dbc->defineField('properties_id', 'bigint');
    $dbc->defineField('person_id', 'bigint');
    $dbc->defineField('name', 'varchar', 50);
    $dbc->defineField('phone', 'varchar', 50);
    $dbc->defineField('email', 'varchar', 50);
    $dbc->defineField('lease_start_inaugural', 'date');
    $dbc->defineField('lease_start', 'date');
    $dbc->defineField('lease_end', 'date');
    $dbc->defineField('vacate', 'date');
    $dbc->defineField('vacate_console', 'date');
    $dbc->defineField('lease_id', 'bigint');
    $dbc->defineField('source', 'varchar');
    $dbc->defineField('console_tenant_id', 'bigint');
    $dbc->defineField('type', 'varchar');

    $dbc->check();

    /**
     * pay attention
     * the lease_end parameter is on the end of the array
     **/
    $where = [];
    $where_autoextend = [];
    $where_noleggio = [];

    $where[] = $_w = sprintf(
      '(`lease_start_inaugural` <= %s OR `lease_start` <= %s)',
      $this->quote(date('Y-m-d')),
      $this->quote(date('Y-m-d'))
    );
    $where_autoextend[] = $_w;
    $where_noleggio[] = sprintf(
      '(n.`lease_start_inaugural` <= %s OR n.`lease_start` <= %s)',
      $this->quote(date('Y-m-d')),
      $this->quote(date('Y-m-d'))
    );

    // just on where, not autoextend
    $where[] = sprintf(
      '( `vacate` IS NULL OR `vacate` = %s OR `vacate` > %s)',
      $this->quote(date('0000-00-00')),
      $this->quote(date('Y-m-d'))
    );

    // just on where, not autoextend
    $where[] = sprintf(
      '`lease_end` > %s',
      $this->quote(date('Y-m-d'))
    );
    $where_noleggio[] = sprintf(
      '(COALESCE( n.`lease_end`, %s) = %s OR DATE( n.`lease_end`) > %s)',
      $this->quote('0000-00-00'),
      $this->quote('0000-00-00'),
      $this->quote(date('Y-m-d'))
    );

    $where[] = $_w = 'NOT `lessor_signature` IS NULL';
    $where_autoextend[] = $_w;
    $where_noleggio[] = 'n.`archived` = 0';

    if ($property_id) {
      array_unshift(
        $where,
        sprintf('`property_id` = %d', $property_id)
      );
      array_unshift(
        $where_autoextend,
        sprintf('`property_id` = %d', $property_id)
      );
      array_unshift(
        $where_noleggio,
        sprintf('n.`properties_id` = %d', $property_id)
      );
    }

    $sqlTemplate =
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
      ORDER BY
        `property_id` ASC,
        `lessor_signature_time` DESC';

    $sqlNoleggio = sprintf(
      'SELECT
        n.`id`,
        n.`properties_id` property_id,
        p.`address_street`,
        n.`tenants`,
        n.`lease_start`,
        n.`lease_start_inaugural`,
        n.`lease_end`
      FROM
        `noleggio` n
          LEFT JOIN
        `properties` p ON p.`id` = n.`properties_id`
      WHERE
        %s
      LIMIT 1',
      implode(' AND ', $where_noleggio)
    );

    $sql = sprintf(
      $sqlTemplate,
      implode(' AND ', $where)

    );

    // if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));
    if ($res = $this->Result($sql)) {

      $ids = [];
      $property_ids = [];
      $searchForIdProperty = function ($id, $property, $array): int {
        foreach ($array as $k => $v) {
          if ($property == $v['properties_id'] && $id == $v['person_id']) {
            return $k;
          }
        }

        return -1;
      };

      $workerFunction = function ($dto, string $src = 'lease') use (&$ids, &$property_ids, $searchForIdProperty, $debug) {
        if (in_array($dto->property_id, $property_ids)) {
          return null;
        }
        $property_ids[] = $dto->property_id;

        if ($dto->tenants) {
          if ($tenants = json_decode($dto->tenants)) {
            foreach ($tenants as $tenant) {
              if ($searchForIdProperty($tenant->id, $dto->property_id, $ids) > -1) {
                if ($debug) sys::logger(sprintf('<%s/%s in multiple residence (a) !> %s', $tenant->id, $dto->property_id, __METHOD__));
              } else {
                $ids[] = [
                  'person_id' => $tenant->id,
                  'properties_id' => $dto->property_id,

                ];

                $a = [
                  'properties_id' => $dto->property_id,
                  'lease_start_inaugural' => $dto->lease_start_inaugural,
                  'lease_start' => $dto->lease_start,
                  'lease_end' => $dto->lease_end,
                  'lease_id' => $dto->id,
                  'vacate' => $dto->vacate,
                  'person_id' => $tenant->id,
                  'name' => $tenant->name,
                  'phone' => $tenant->phone ?? $tenant->mobile,
                  'email' => $tenant->email,
                  'source' => $src,
                  'type' => 'tenant'

                ];

                $this->db->Insert('_tens', $a);
              }
            }
          }
        }

        if ($dto->tenants_approved) {
          if ($tenants = json_decode($dto->tenants_approved)) {
            foreach ($tenants as $tenant) {
              if ($searchForIdProperty($tenant->id, $dto->property_id, $ids) > -1) {
                if ($debug) sys::logger(sprintf('<%s/%s in multiple residence (b) !> %s', $tenant->id, $dto->property_id, __METHOD__));
              } else {
                $ids[] = [
                  'person_id' => $tenant->id,
                  'properties_id' => $dto->property_id,

                ];
                $a = [
                  'properties_id' => $dto->property_id,
                  'lease_start_inaugural' => $dto->lease_start_inaugural,
                  'lease_start' => $dto->lease_start,
                  'lease_end' => $dto->lease_end,
                  'lease_id' => $dto->id,
                  'vacate' => $dto->vacate,
                  'person_id' => $tenant->id,
                  'name' => $tenant->name,
                  'phone' => $tenant->phone,
                  'email' => $tenant->email,
                  'source' => $src,
                  'type' => 'occupant'

                ];

                $this->db->Insert('_tens', $a);
              }
            }
          }
        }

        if ($dto->tenants_guarantors) {
          if ($tenants = json_decode($dto->tenants_guarantors)) {
            foreach ($tenants as $tenant) {
              if ($searchForIdProperty($tenant->id, $dto->property_id, $ids) > -1) {
                if ($debug) sys::logger(sprintf('<%s/%s in multiple residence (g) !> %s', $tenant->id, $dto->property_id, __METHOD__));
              } else {
                $ids[] = [
                  'person_id' => $tenant->id,
                  'properties_id' => $dto->property_id,

                ];
                $a = [
                  'properties_id' => $dto->property_id,
                  'lease_start_inaugural' => $dto->lease_start_inaugural,
                  'lease_start' => $dto->lease_start,
                  'lease_end' => $dto->lease_end,
                  'lease_id' => $dto->id,
                  'vacate' => $dto->vacate,
                  'person_id' => $tenant->id,
                  'name' => $tenant->name,
                  'phone' => $tenant->phone,
                  'email' => $tenant->email,
                  'source' => $src,
                  'type' => 'guarantor'

                ];

                $this->db->Insert('_tens', $a);
              }
            }
          }
        }

        return $dto;
      };

      $_dtoSet = $res->dtoSet($workerFunction);

      if (!$_dtoSet) {
        /**
         * https://cmss.darcy.com.au/forum/view/7932
         * remove the last parameter, and try again,
         * this is a periodic continuance of the last lease
         *
         * corrected for https://cmss.darcy.com.au/forum/view/8361
         * the original query would report a previous lease where no vacate had been set
         */

        $sql = sprintf(
          $sqlTemplate . ' LIMIT 1',
          implode(' AND ', $where_autoextend)

        );
        if ($res = $this->Result($sql)) {
          if ($_dto = $res->dto()) {
            if (!(strtotime($_dto->vacate) > 0)) { // they are vacating - false alarm
              $workerFunction($_dto);
              \sys::logger(sprintf('<trying again ignoring lease end - %s> %s', $_dtoSet ? 'found' : 'not found', __METHOD__));
            }
          } else {
            if ($res = $this->Result($sqlNoleggio)) {
              if ($_dto = $res->dto()) {
                $_dto->tenants_approved = '';
                $_dto->tenants_guarantors = '';
                $_dto->vacate = '';
                $workerFunction($_dto, 'noleggio');
                // \sys::logger(sprintf('<%s> %s', 'no tenant found !', __METHOD__));
              }
              // else {
              //   \sys::logger( sprintf('<%s> %s', 'no result ..', __METHOD__));
              //   \sys::logSQL(sprintf('<%s> %s', $sqlNoleggio, __METHOD__));

              // }
            }
            // else {
            //   \sys::logger( sprintf('<%s> %s', 'no result ..', __METHOD__));

            // }
          }
        }
      }
    }

    if (config::check_console_tenants) {

      $sql = sprintf(
        'UPDATE _tens
          SET
            vacate_console = (SELECT
                ct.vacating
              FROM
                console_properties cp
                  LEFT JOIN
                console_tenants ct ON ct.ConsolePropertyID = cp.ConsoleID
              WHERE
                cp.properties_id = _tens.properties_id
                  AND ((ct.`LeaseFirstStart` != %s
                  AND ct.`LeaseFirstStart` <= %s)
                  OR ct.`LeaseStart` <= %s)
                  AND (ct.`LeaseStop` = %s
                  OR ct.`LeaseStop` > %s)
              ORDER BY
                ct.`LeaseStart` DESC
                LIMIT 1
                )',
        $this->quote('0000-00-00'),
        $this->quote(date('Y-m-d')),
        $this->quote(date('Y-m-d')),
        $this->quote('0000-00-00'),
        $this->quote(date('Y-m-d'))

      );
      $this->Q($sql);

      /**
       * are there any console tenants missing here
       */

      /**
       * Forum : 7861 => Current Tenants - missing tenants
       *
       * added filter NOT cp.properties_id IN (SELECT DISTINCT `properties_id` FROM `_tens`)
       * removed filter NOT cc.people_id IN (SELECT `person_id` FROM `_tens`)
       */
      $where = [
        sprintf(
          '( ct.Vacating IS NULL OR ct.Vacating = %s OR ct.Vacating > %s)',
          $this->quote(date('0000-00-00')),
          $this->quote(date('Y-m-d'))
        ),
        sprintf(
          '((ct.`LeaseFirstStart` != %s AND ct.`LeaseFirstStart` <= %s) OR ct.`LeaseStart` <= %s)',
          $this->quote('0000-00-00'),
          $this->quote(date('Y-m-d')),
          $this->quote(date('Y-m-d'))
        ),
        sprintf(
          '(ct.`LeaseStop` = %s OR ct.`LeaseStop` > %s)',
          $this->quote('0000-00-00'),
          $this->quote(date('Y-m-d'))
        ),
        'NOT cp.properties_id IN (SELECT DISTINCT `properties_id` FROM `_tens`)'

      ];

      // 'NOT ISNULL(`lessor_signature`)'

      if ($property_id) {
        array_unshift(
          $where,
          sprintf('cp.`properties_id` = %d', $property_id)
        );
      }

      $sql = sprintf(
        'SELECT
          ct.`id`,
          ct.`ContactID`,
          ct.`ConsolePropertyID`,
          ct.`LeaseFirstStart` lease_start_inaugural,
          ct.`LeaseStart` lease_start,
          ct.`LeaseStop` lease_end,
          ct.`Vacating`,
          ct.`Vacate_Override`,
          cc.`people_id`,
          people.`name`,
          people.`mobile`,
          people.`telephone`,
          people.`email`,
          cp.`properties_id`,
          ct.`ContactIDs`
        FROM
          `console_tenants` ct
            LEFT JOIN
          `console_contacts` cc ON cc.ConsoleID = ct.ContactID
            LEFT JOIN
          `people` ON people.id = cc.people_id
            LEFT JOIN
          `console_properties` cp ON cp.ConsoleID = ct.ConsolePropertyID
        WHERE
          %s',
        implode(' AND ', $where)

      );

      if ($debug) {
        $this->Q('DROP TABLE IF EXISTS _tens_');
        $this->Q('CREATE TABLE _tens_ AS SELECT * FROM _tens');
        sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));
      }

      if ($res = $this->Result($sql)) {
        $res->dtoSet(function ($dto) use (&$ids, $searchForIdProperty, $debug) {

          $a = [
            'properties_id' => $dto->properties_id,
            'lease_start_inaugural' => $dto->lease_start_inaugural,
            'lease_start' => $dto->lease_start,
            'lease_end' => $dto->lease_end,
            'vacate' => $dto->Vacating,
            'person_id' => $dto->people_id,
            'name' => $dto->name,
            'phone' => strings::isMobilePhone($dto->mobile) ? $dto->mobile : $dto->telephone,
            'email' => $dto->email,
            'source' => 'console',
            'console_tenant_id' => $dto->id,
            'type' => 'tenant'

          ];

          if (strtotime($dto->Vacating) < 0) {
            if (strtotime($dto->Vacate_Override) > strtotime($dto->lease_start)) {
              $a['vacate'] = $dto->Vacate_Override;
            }
          }

          if ($searchForIdProperty($dto->people_id, $dto->properties_id, $ids) > -1) {
            if ($debug) sys::logger(sprintf('<%s/%s in multiple residence (c) !> %s', $dto->people_id, $dto->properties_id, __METHOD__));
          } else {
            $ids[] = [
              'person_id' => $dto->people_id,
              'properties_id' => $dto->properties_id,

            ];
            $this->db->Insert('_tens', $a);
            // if (strtotime($a['vacate']) > 0) {
            //   \sys::logger(sprintf('<vacating - %s> %s', $a['vacate'], __METHOD__));
            // }

            // sys::logger( sprintf('<%s> %s', $dto->people_id, __METHOD__));

          }

          /*--- -------------------------------------------- ---*/
          if ($Contacts = (array)json_decode($dto->ContactIDs)) {

            foreach ($Contacts as $Contact) {
              $_sql = sprintf(
                'SELECT
                  cc.`FileAs`,
                  concat( cc.`First`," ",cc.`Last`) name,
                  cc.`Home`,
                  cc.`Mobile`,
                  cc.`Email`,
                  cc.`people_id`,
                  p.`name`
                FROM
                  `console_contacts` cc
                    LEFT JOIN
                  `people` p ON p.`id` = cc.`people_id`
                WHERE
                  cc.`ConsoleID` = %s',
                $this->quote($Contact)

              );

              if ($_res = $this->Result($_sql)) {
                if ($_dto = $_res->dto()) {
                  if ($_dto->people_id) {
                    if ($searchForIdProperty($_dto->people_id, $dto->properties_id, $ids) > -1) {
                      // if ($debug) sys::logger(sprintf('<%s/%s in multiple residence (d) !> %s', $qp->id, $dto->properties_id, __METHOD__));

                    } else {
                      $ids[] = [
                        'person_id' => $_dto->people_id,
                        'properties_id' => $dto->properties_id,

                      ];

                      $a['person_id'] = $_dto->people_id;
                      $a['name'] = $_dto->name;
                      $a['phone'] = strings::IsMobilePhone($_dto->Mobile) ? $_dto->Mobile : $_dto->Home;
                      $a['email'] = $_dto->Email;
                      $a['type'] = 'cotenant';

                      $this->db->Insert('_tens', $a);
                    }
                  } elseif (trim($_dto->name)) {
                    if ($_dto->Mobile != $dto->mobile || $_dto->Email != $dto->email) {

                      $qp = \QuickPerson::find([
                        'name' => $_dto->name,
                        'phone' => $_dto->Home,
                        'mobile' => $_dto->Mobile,
                        'email' => $_dto->Email

                      ]);

                      if ($searchForIdProperty($qp->id, $dto->properties_id, $ids) > -1) {
                        // if ($debug) sys::logger(sprintf('<%s/%s in multiple residence (d) !> %s', $qp->id, $dto->properties_id, __METHOD__));

                      } else {
                        $ids[] = [
                          'person_id' => $qp->id,
                          'properties_id' => $dto->properties_id,

                        ];

                        $a['person_id'] = $qp->id;
                        $a['name'] = $qp->name;
                        if (isset($qp->mobile)) {
                          $a['phone'] = strings::IsMobilePhone($qp->mobile) ? $qp->mobile : $qp->telephone;
                        } else {
                          sys::dump($_dto, null, false);
                          sys::dump($qp);
                          // $a['phone'] = $qp->telephone;

                        }
                        $a['email'] = $qp->email;
                        $a['type'] = 'cotenant';

                        $this->db->Insert('_tens', $a);
                      }
                    }
                  }

                  // sys::logger( sprintf('<%s> %s', $ct->name, __METHOD__));

                }
              }
            }
          }
          /*--- -------------------------------------------- ---*/

          return $dto;
        });
      }

      if ($debug) sys::logger(sprintf('<checked tenants %ss> %s', $timer->elapsed(), __METHOD__));
    }

    $sql = sprintf(
      'SELECT
        t.*,
        p.`address_street`,
        p.`property_manager`,
        u.`name` property_manager_name,
        p.`street_index`
      FROM
        `_tens` t
          LEFT JOIN
        `properties` p ON p.`id` = t.`properties_id`
          LEFT JOIN
        `users` u ON p.`property_manager` = u.`id`
      ORDER BY
        p.`street_index` ASC,
        CASE `type`
          WHEN %s THEN 1
          WHEN %s THEN 2
          ELSE 3
        END ASC',
      $this->quote('tenant'),
      $this->quote('cotenant')

    );

    if ($debug) {
      $this->Q('DROP TABLE IF EXISTS _tens__');
      $this->Q(sprintf('CREATE TABLE _tens__ AS %s', $sql));
      sys::logger(sprintf('<complete %ss> %s', $timer->elapsed(), __METHOD__));
    }
    return $this->Result($sql);
  }

  function getTenantsLease(int $tenant_id) {
    $debug = false;
    // $debug = true;
    // $debug = $tenant_id > 0;

    $timer = \application::app()->timer();

    $where = [
      sprintf('(`lease_start_inaugural` <= %s OR `lease_start` <= %s)', $this->quote(date('Y-m-d')), $this->quote(date('Y-m-d'))),
      sprintf('`lease_end` > %s', $this->quote(date('Y-m-d'))),
      sprintf('( `vacate` IS NULL OR `vacate` = %s OR `vacate` > %s)', $this->quote(date('0000-00-00')), $this->quote(date('Y-m-d'))),
      'NOT `lessor_signature` IS NULL'

    ];

    $sql = sprintf(
      'SELECT
        `id`,
        `property_id`,
        `address_street`,
        `address_suburb`,
        `address_postcode`,
        `tenants`,
        `tenants_approved`,
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
      while ($dto = $res->dto()) {
        if ($dto->tenants) {
          if ($tenants = json_decode($dto->tenants)) {
            foreach ($tenants as $tenant) {
              if ($tenant_id == $tenant->id) {

                $a = [
                  'properties_id' => $dto->property_id,
                  'address_street' => $dto->address_street,
                  'address_suburb' => $dto->address_suburb,
                  'address_postcode' => $dto->address_postcode,
                  'lease_start_inaugural' => $dto->lease_start_inaugural,
                  'lease_start' => $dto->lease_start,
                  'lease_end' => $dto->lease_end,
                  'vacate' => $dto->vacate,
                  'person_id' => $tenant->id,
                  'name' => $tenant->name,
                  'phone' => $tenant->phone,
                  'email' => $tenant->email,
                  'source' => 'lease',
                  'type' => 'tenant'

                ];

                return (object)$a;
              }
            }
          }
        }
      }
    }

    /*--- -----[check in console]----- ---*/
    if (config::check_console_tenants) {
      $sql = sprintf(
        'SELECT
          TenantIDs
        FROM
          `console_contacts` cc
        WHERE
          cc.`people_id` = %d',
        $tenant_id

      );

      if ($res = $this->Result($sql)) {
        if ($dto = $res->dto()) {
          if ($dto->TenantIDs) {
            $tens = (array)json_decode($dto->TenantIDs);
            if ($tens) {
              if (1 == count($tens)) {
                if ($debug) sys::logger(sprintf('<%s> %s', implode('><', $tens), __METHOD__));

                $where = [
                  sprintf('( ct.Vacating IS NULL OR ct.Vacating = %s OR ct.Vacating > %s)', $this->quote(date('0000-00-00')), $this->quote(date('Y-m-d'))),
                  sprintf('((ct.`LeaseFirstStart` != %s AND ct.`LeaseFirstStart` <= %s) OR ct.`LeaseStart` <= %s)', $this->quote('0000-00-00'), $this->quote(date('Y-m-d')), $this->quote(date('Y-m-d'))),
                  sprintf('(ct.`LeaseStop` = %s OR ct.`LeaseStop` > %s)', $this->quote('0000-00-00'), $this->quote(date('Y-m-d'))),
                  sprintf('ct.TenantID = %d', $tens[0])

                ];

                $sql = sprintf(
                  'SELECT
                    ct.`id`,
                    ct.`ContactID`,
                    ct.`ConsolePropertyID`,
                    ct.`LeaseFirstStart` lease_start_inaugural,
                    ct.`LeaseStart` lease_start,
                    ct.`LeaseStop` lease_end,
                    ct.`Vacating`,
                    cc.`people_id`,
                    people.`name`,
                    people.`mobile`,
                    people.`telephone`,
                    people.`email`,
                    p.`address_street`,
                    p.`address_suburb`,
                    p.`address_postcode`,
                    cp.`properties_id`,
                    ct.`ContactIDs`
                  FROM
                    `console_tenants` ct
                      LEFT JOIN
                    `console_contacts` cc ON cc.ConsoleID = ct.ContactID
                      LEFT JOIN
                    `people` ON people.id = cc.people_id
                      LEFT JOIN
                    `console_properties` cp ON cp.ConsoleID = ct.ConsolePropertyID
                      LEFT JOIN
                    `properties` p ON p.id = cp.properties_id
                  WHERE
                    %s',
                  implode(' AND ', $where)

                );
                if ($_res = $this->Result($sql)) {
                  if ($_dto = $_res->dto()) {
                    $a = [
                      'properties_id' => $_dto->properties_id,
                      'address_street' => $_dto->address_street,
                      'address_suburb' => $_dto->address_suburb,
                      'address_postcode' => $_dto->address_postcode,
                      'lease_start_inaugural' => $_dto->lease_start_inaugural,
                      'lease_start' => $_dto->lease_start,
                      'lease_end' => $_dto->lease_end,
                      'vacate' => $_dto->Vacating,
                      'person_id' => $_dto->people_id,
                      'name' => $_dto->name,
                      'phone' => strings::isMobilePhone($_dto->mobile) ? $_dto->mobile : $_dto->telephone,
                      'email' => $_dto->email,
                      'source' => 'console',
                      'type' => 'tenant'

                    ];

                    if ($_dto->people_id == $tenant_id) {

                      return (object)$a;
                    } else {
                      if ($debug) sys::logger(sprintf('<found primary, need co-tenant from console - %s> %s', $timer->elapsed(), __METHOD__));

                      if ($Contacts = (array)json_decode($_dto->ContactIDs)) {

                        foreach ($Contacts as $Contact) {
                          $sql = sprintf(
                            'SELECT
                              cc.`FileAs`,
                              concat( cc.`First`," ",cc.`Last`) name,
                              cc.`Home`,
                              cc.`Mobile`,
                              cc.`Email`,
                              cc.`people_id`,
                              p.`name`
                            FROM
                              `console_contacts` cc
                                LEFT JOIN
                              `people` p ON p.`id` = cc.`people_id`
                            WHERE
                              cc.`ConsoleID` = %s',
                            $this->quote($Contact)

                          );

                          if ($__res = $this->Result($sql)) {
                            if ($__dto = $__res->dto()) {
                              if ($__dto->people_id == $tenant_id) {

                                $a['person_id'] = $__dto->people_id;
                                $a['name'] = $__dto->name;
                                $a['phone'] = strings::IsMobilePhone($__dto->Mobile) ? $__dto->Mobile : $__dto->Home;
                                $a['email'] = $__dto->Email;
                                $a['type'] = 'cotenant';

                                if ($debug) sys::logger(sprintf('<found co-tenant from console - %s> %s', $timer->elapsed(), __METHOD__));
                                return (object)$a;
                              }
                            }
                          }
                        }
                      }
                    }

                    if ($debug) sys::logger(sprintf('<console lease found> %s', __METHOD__));
                  } else {
                    if ($debug) sys::logSQL(sprintf('<%s> %s', $sql, __METHOD__));
                    sys::logger(sprintf('<there are no leases in console (result)> %s', __METHOD__));
                  }
                } else {
                  sys::logger(sprintf('<there are no leases in console (query fail)> %s', __METHOD__));
                }
              } else {
                sys::logger(sprintf('<there are %s tenancies> %s', count($tens), __METHOD__));
              }
            }
          }
        }
      }

      if ($debug) sys::logger(sprintf('<checked console for tenants %s> %s', $timer->elapsed(), __METHOD__));
    }
    /*--- ---[end check in console]--- ---*/

    if ($debug) sys::logger(sprintf('<%s> %s', $timer->elapsed(), __METHOD__));

    return null;
  }
}
