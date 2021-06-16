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

class tenants extends _dao {
  function getTenantsOfProperty( int $id) : array {

    if ( $res = $this->getCurrentTenants($id)) {
      return $res->dtoSet();

    }

    return [];

  }

  function getCurrentTenants( int $property_id = 0) {
    $debug = false;
    // $debug = true;

    $timer = \application::app()->timer();

    $where = [
      sprintf( '`lease_start` <= %s', $this->quote( date( 'Y-m-d'))),
      sprintf( '`lease_end` > %s', $this->quote( date( 'Y-m-d'))),
      'NOT `lessor_signature` IS NULL'

    ];
    // 'NOT ISNULL(`lessor_signature`)'

    if ( $property_id) {
      array_unshift( $where, sprintf('`property_id` = %d', $property_id));

    }

    $sql = sprintf(
      'SELECT
        `id`,
        `property_id`,
        `address_street`,
        `tenants`,
        `tenants_approved`,
        `lease_start`,
        `lease_start_inaugural`,
        `lease_end`,
        `lessor_signature`
      FROM
        `offer_to_lease`
      WHERE
        %s
      ORDER BY `lease_start` DESC',
      implode( ' AND ', $where)

    );

    if ( $debug) \sys::logSQL( sprintf('<%s> %s', $sql, __METHOD__));

    $dbc = \sys::dbCheck( '_tens' );

    $dbc->temporary = true;
    $dbc->defineField( 'properties_id', 'bigint');
    $dbc->defineField( 'person_id', 'bigint');
    $dbc->defineField( 'name', 'varchar', 50);
    $dbc->defineField( 'phone', 'varchar', 50);
    $dbc->defineField( 'email', 'varchar', 50);
    $dbc->defineField( 'lease_start_inaugural', 'date');
    $dbc->defineField( 'lease_start', 'date');
    $dbc->defineField( 'lease_end', 'date');
    $dbc->defineField( 'source', 'varchar');
    $dbc->defineField( 'type', 'varchar');

    $dbc->check();

    if ( $res = $this->Result( $sql)) {
      $ids = [];
      $res->dtoSet( function($dto) use (&$ids, $debug) {
        if ( $dto->tenants) {
          if ( $tenants = json_decode( $dto->tenants)) {
            foreach ($tenants as $tenant) {
              if ( in_array( $tenant->id, $ids)) {
                if ( $debug) \sys::logger( sprintf('<%s/%s in multiple residence (a) !> %s', $tenant->id, $dto->property_id, __METHOD__));

              }
              else {
                $ids[] = $tenant->id;
                $a = [
                  'properties_id' => $dto->property_id,
                  'lease_start_inaugural' => $dto->lease_start_inaugural,
                  'lease_start' => $dto->lease_start,
                  'lease_end' => $dto->lease_end,
                  'person_id' => $tenant->id,
                  'name' => $tenant->name,
                  'phone' => $tenant->phone,
                  'email' => $tenant->email,
                  'source' => 'lease',
                  'type' => 'tenant'

                ];

                $this->db->Insert('_tens', $a);

              }

            }

          }

        }

        if ( $dto->tenants_approved) {
          if ( $tenants = json_decode( $dto->tenants_approved)) {
            foreach ($tenants as $tenant) {
              if ( in_array( $tenant->id, $ids)) {
                if ( $debug) \sys::logger( sprintf('<%s/%s in multiple residence (b) !> %s', $tenant->id, $dto->property_id, __METHOD__));

              }
              else {
                $ids[] = $tenant->id;
                $a = [
                  'properties_id' => $dto->property_id,
                  'lease_start_inaugural' => $dto->lease_start_inaugural,
                  'lease_start' => $dto->lease_start,
                  'lease_end' => $dto->lease_end,
                  'person_id' => $tenant->id,
                  'name' => $tenant->name,
                  'phone' => $tenant->phone,
                  'email' => $tenant->email,
                  'source' => 'lease',
                  'type' => 'occupant'

                ];

                $this->db->Insert('_tens', $a);

              }

            }

          }

        }

      });

    }

    if ( config::check_console_tenants) {
      /**
       * are there any console tenants missing here
       */

      $sql = sprintf(
        'SELECT
          ct.`id`,
          ct.`ContactID`,
          ct.`ConsolePropertyID`,
          ct.`LeaseFirstStart` lease_start_inaugural,
          ct.`LeaseStart` lease_start,
          ct.`LeaseStop` lease_end,
          cc.`people_id`,
          people.`name`,
          people.`mobile`,
          people.`telephone`,
          people.`email`,
          cp.`properties_id`
        FROM
          `console_tenants` ct
            LEFT JOIN
          `console_contacts` cc ON cc.ConsoleID = ct.ContactID
            LEFT JOIN
          `people` ON people.id = cc.people_id
            LEFT JOIN
          `console_properties` cp ON cp.ConsoleID = ct.ConsolePropertyID
        WHERE
          ( ct.Vacating IS NULL OR ct.Vacating <= %s)
          AND NOT cc.people_id IN (SELECT `person_id` FROM `_tens`)',
        $this->quote( date('Y-m-d'))

      );

      if ( $debug) {
        $this->Q('DROP TABLE IF EXISTS _tens_');
        $this->Q('CREATE TABLE _tens_ AS SELECT * FROM _tens');
        \sys::logSQL( sprintf('<%s> %s', $sql, __METHOD__));

      }

      if ( $res = $this->Result( $sql)) {
        $res->dtoSet( function( $dto) use (&$ids, $debug) {

          if ( in_array( $dto->people_id, $ids)) {
            if ( $debug) \sys::logger( sprintf('<%s/%s in multiple residence (c) !> %s', $dto->people_id, $dto->properties_id, __METHOD__));

          }
          else {
            $ids[] = $dto->people_id;
            $a = [
              'properties_id' => $dto->properties_id,
              'lease_start_inaugural' => $dto->lease_start_inaugural,
              'lease_start' => $dto->lease_start,
              'lease_end' => $dto->lease_end,
              'person_id' => $dto->people_id,
              'name' => $dto->name,
              'phone' => strings::IsMobilePhone( $dto->mobile) ? $dto->mobile : $dto->telephone,
              'email' => $dto->email,
              'source' => 'console',
              'type' => 'tenant'

            ];

            $this->db->Insert('_tens', $a);

            // \sys::logger( sprintf('<%s> %s', $dto->people_id, __METHOD__));

          }

          return $dto;

        });

      }

    }


    // \sys::logger( sprintf('<%s> %s', $timer->elapsed(), __METHOD__));

    $sql = 'SELECT
        t.*,
        p.address_street,
        p.street_index
      FROM `_tens` t
        LEFT JOIN `properties` p on p.id = t.properties_id
      ORDER BY p.street_index ASC';

    return $this->Result($sql);

  }

}