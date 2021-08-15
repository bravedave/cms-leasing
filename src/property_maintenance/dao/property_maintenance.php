<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\property_maintenance\dao;

use cms\leasing;
use dao\_dao;

class property_maintenance extends _dao {
  protected $_db_name = 'property_maintenance';
  protected $template = __NAMESPACE__ . '\dto\property_maintenance';

  function getSchedule(int $id): array {
    $dao = new \dao\properties;
    if ( $prop = $dao->getByID($id)) {
      if ( $prop->people_id) {
        $sql = sprintf(
          'SELECT
            pm.*,
            p.`address_street`
          FROM
            `property_maintenance` pm
              LEFT JOIN
            `properties` p ON p.`id` = pm.`properties_id`
          WHERE
            pm.`people_id` = %d
          ORDER BY
            pm.`type`',
          $prop->people_id

        );

        if ( $res = $this->Result($sql)) {
          if ( $dtoSet = $this->dtoSet($res)) {
            return $dtoSet;

          }

        }

      }

      $dao = new leasing\dao\maintenance;
      if ($res = $dao->getSchedule($id)) {
        return $res->dtoSet(function ($dto) use ($id, $prop) {
          return (object)[
            'id' => 0,
            'properties_id' => $id,
            'address_street' => $prop->address_street,
            'people_id' => $dto->people_id,
            'type' => $dto->Type,
            'limit' => '0' == $dto->Limit ? '' : $dto->Limit,
            'notes' => $dto->Notes,
            'source' => 'console'
          ];
        });
      }

    }

    return [];
  }

  function importFromConsole(int $id): void {
    $dao = new leasing\dao\maintenance;
    if ($res = $dao->getSchedule($id)) {
      $res->dtoSet(function ($dto) {
        $a = [
          'people_id' => $dto->people_id,
          'type' => $dto->Type,
          'limit' => '0' == $dto->Limit ? '' : $dto->Limit,
          'notes' => $dto->Notes
        ];

        $this->Insert($a);
      });
    }
  }

  function Insert($a) {
    $a['created'] = $a['updated'] = \db::dbTimeStamp();
    return parent::Insert($a);
  }

  function UpdateByID($a, $id) {
    $a['updated'] = \db::dbTimeStamp();
    return parent::UpdateByID($a, $id);
  }
}
