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

class maintenance extends _dao {
  function getSchedule(int $id) {
    if (config::$CONSOLE_INTEGRATION) {
      $sql = sprintf(
        'SELECT
        ConsoleOwnerID,
        properties_id
      FROM
        `console_properties`
      WHERE
        properties_id = %d',
        $id

      );

      if ($res = $this->Result($sql)) {
        if ($dto = $res->dto()) {
          if ($dto->ConsoleOwnerID) {
            $dao = new \cms\console\dao\console_owners_maintenance;
            return $dao->getSchedule($dto->ConsoleOwnerID);
          }
        }
      }
    }

    return null;
  }
}
