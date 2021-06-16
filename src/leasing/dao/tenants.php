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

class tenants extends _dao {
  function getTenantsOfProperty( int $id) : array {

    if ( $res = $this->getCurrentTenants($id)) {
      return $res->dtoSet();

    }

    return [];

  }

  function getCurrentTenants( int $property_id = 0) {

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
        %s',
      implode( ' AND ', $where)

    );

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
      $res->dtoSet( function($dto) use (&$ids) {
        if ( $dto->tenants) {
          if ( $tenants = json_decode( $dto->tenants)) {
            foreach ($tenants as $tenant) {
              if ( in_array( $tenant->id, $ids)) {
                \sys::logger( sprintf('<%s in multiple residence !> %s', $tenant->id, __METHOD__));

              }
              else {
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
                \sys::logger( sprintf('<%s in multiple residence !> %s', $tenant->id, __METHOD__));

              }
              else {
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

    // \sys::logger( sprintf('<%s> %s', $timer->elapsed(), __METHOD__));

    $sql = 'SELECT
        t.*,
        p.address_street,
        p.street_index
      FROM `_tens` t
        LEFT JOIN `properties` p on p.id = t.properties_id';

    return $this->Result($sql);

  }

}