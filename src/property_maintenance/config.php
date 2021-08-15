<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\property_maintenance;

class config extends \config {
  const label = 'Maintenance Instructions';
  const label_add = 'Add Maintenance Instructions';

  const property_maintenance_db_version = 0.2;

  static protected $_PROPERTY_MAINTENANCE_VERSION = 0;

  public static function property_maintenance_checkdatabase() {
    if (self::property_maintenance_version() < self::property_maintenance_db_version) {
      self::property_maintenance_version(self::property_maintenance_db_version);

      $dao = new dao\dbinfo;
      $dao->dump($verbose = false);
    }

    // sys::logger( 'bro!');

  }

  public static function property_maintenance_config() {
    return implode(DIRECTORY_SEPARATOR, [
      rtrim(self::cmsStore(), '/'),
      'property_maintenance.json',
    ]);
  }

  public static function property_maintenance_init() {
    if (file_exists($config = self::property_maintenance_config())) {
      $j = json_decode(file_get_contents($config));

      if (isset($j->property_maintenance_version)) {
        self::$_PROPERTY_MAINTENANCE_VERSION = (float)$j->property_maintenance_version;
      };
    }
  }

  static protected function property_maintenance_version($set = null) {
    $ret = self::$_PROPERTY_MAINTENANCE_VERSION;

    if ((float)$set) {
      $config = self::property_maintenance_config();

      $j = file_exists($config) ?
        json_decode(file_get_contents($config)) :
        (object)[];

      self::$_PROPERTY_MAINTENANCE_VERSION = $j->property_maintenance_version = $set;

      file_put_contents($config, json_encode($j, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    return $ret;
  }
}

config::property_maintenance_init();
