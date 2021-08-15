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

// use currentUser, Json, strings;
use Json;

use dao\properties as daoProperties;

class controller extends \Controller {
  protected $viewPath = __DIR__ . '/views/';

  protected function _index() {
    $this->render([
      'title' => $this->title = config::label,
      'primary' => 'blank',
      'secondary' => [
        'index',

      ],
      'data' => (object)[
        'searchFocus' => true,
        'pageUrl' => $this->route

      ],

    ]);
  }

  protected function before() {
    config::property_maintenance_checkdatabase();
    parent::before();
  }

  protected function postHandler() {
    $action = $this->getPost('action');

    if ('get-maintenance-instructions' == $action) {
      /*
      ( _ => {
        _.post({
          url : _.url('property_maintenance'),
          data : {
            action : 'get-maintenance-instructions',
            id : 52728

          }

        }).then( d => 'ack' == d.response ? console.table(d.data) : console.log(d))

      })(_brayworth_)

       */
      if ($id = (int)$this->getPost('id')) {
        $dao = new dao\property_maintenance;
        Json::ack($action)
          ->add('data', $dao->getSchedule($id));
      } else {
        Json::nak($action);
      }
    } elseif ('import-from-console' == $action) {
      if ($properties_id = $this->getPost('properties_id')) {
        $dao = new dao\property_maintenance;
        $dao->importFromConsole($properties_id);

        Json::ack($action);
      } else {
        Json::nak($action);
      }
    } elseif ('property-maintenance-delete' == $action) {
      if ($id = (int)$this->getPost('id')) {
        $dao = new dao\property_maintenance;
        $dao->delete($id);
        Json::ack($action);
      } else {
        Json::nak($action);
      }
    } elseif ('property-maintenance-save' == $action) {
      if ($id = (int)$this->getPost('id')) {
        $dao = new dao\property_maintenance;
        if ($dto = $dao->getByID($id)) {
          $dao->UpdateByID([
            'type' => $this->getPost('type'),
            'limit' => $this->getPost('limit'),
            'notes' => $this->getPost('notes'),
            'properties_id' => $this->getPost('properties_id'),
          ], $dto->id);
          Json::ack($action);
        } else {
          Json::nak($action);
        }
      } elseif ($people_id = (int)$this->getPost('people_id')) {
        $dao = new dao\property_maintenance;
        $dao->Insert([
          'type' => $this->getPost('type'),
          'limit' => $this->getPost('limit'),
          'notes' => $this->getPost('notes'),
          'people_id' => $people_id,
          'properties_id' => $this->getPost('properties_id'),
        ]);
        Json::ack($action);
      } else {
        Json::nak(sprintf('invalid person - ', $action));
      }
    } else {
      parent::postHandler();
    }
  }

  public function add($properties_id) {

    if ($properties_id = (int)$properties_id) {
      $dao = new daoProperties;
      if ($prop = $dao->getByID($properties_id)) {
        if ($prop->people_id) {
          $allProps = [];
          $sql = sprintf(
            'SELECT `id`, `address_street` FROM `properties` WHERE `people_id` = %d',
            $prop->people_id

          );
          if ($res = $dao->Result($sql)) {
            $allProps = $res->dtoSet();
          }
          $dto = new dao\dto\property_maintenance;
          $dto->people_id = $prop->people_id;

          $this->data = (object)[
            'title' => $this->title = config::label_add,
            'dto' => $dto,
            'allProps' => $allProps,

          ];

          $this->load('edit');
        } else {
          $this->data = (object)[
            'title' => config::label_add,
            'text' => 'property has no contact'

          ];
          $this->load('warning-modal');
        }
      } else {
        $this->data = (object)[
          'title' => config::label_add,
          'text' => 'property not found'

        ];
        $this->load('warning-modal');
      }
    } else {
      $this->data = (object)[
        'title' => config::label,
        'text' => 'invalid property'

      ];
      $this->load('warning-modal');
    }
  }

  public function edit($id) {

    if ($id = (int)$id) {
      $dao = new dao\property_maintenance;
      if ($dto = $dao->getByID($id)) {

        $allProps = [];
        if ($dto->people_id) {
          $sql = sprintf(
            'SELECT `id`, `address_street` FROM `properties` WHERE `people_id` = %d',
            $dto->people_id

          );
          if ($res = $dao->Result($sql)) {
            $allProps = $res->dtoSet();
          }
        }

        $this->data = (object)[
          'title' => $this->title = config::label,
          'dto' => $dto,
          'allProps' => $allProps,

        ];

        $this->load('edit');
      } else {
        $this->data = (object)[
          'title' => config::label,
          'text' => 'item not found'

        ];
        $this->load('warning-modal');
      }
    } else {
      $this->data = (object)[
        'title' => config::label,
        'text' => 'invalid item'

      ];
      $this->load('warning-modal');
    }
  }

  public function property($id) {

    if ($id = (int)$id) {
      $dao = new daoProperties;
      if ($dto = $dao->getByID($id)) {

        $dao = new dao\property_maintenance;

        $this->data = (object)[
          'title' => $this->title = config::label,
          'dto' => $dto,
          'schedule' => $dao->getSchedule($dto->id)

        ];

        $this->load('property');
      } else {
        $this->data = (object)[
          'title' => config::label,
          'text' => 'property not found'

        ];
        $this->load('warning-modal');
      }
    } else {
      $this->data = (object)[
        'title' => config::label,
        'text' => 'invalid id'

      ];
      $this->load('warning-modal');
    }
  }
}
