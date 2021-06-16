<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\leasing;

// use currentUser;
use Json;
use strings;

class controller extends \Controller {
  protected $viewPath = __DIR__ . '/views/';

	protected function _index() {

    // 'searchFocus' => false,
    $this->render([
      'title' => $this->title = config::label,
      'primary' => 'blank',
      'secondary' => 'index',
      'data' => (object)[
        'pageUrl' => strings::url( $this->route)

      ],

    ]);

  }

	protected function posthandler() {
		$action = $this->getPost('action');

    if ( 'get-tenants-for-property' == $action) {
      /*
      ( _ => {
        _.post({
          url : _.url('leasing'),
          data : {
            action : 'get-tenants-for-property',
            id : 37995

          }

        }).then( d => console.log('ack' == d.response ? d.tenants : d))

      })(_brayworth_)

       */
      if ( $id = $this->getPost( 'id')) {
        $dao = new dao\tenants;
        if ( $tens = $dao->getTenantsOfProperty( $id)) {
          Json::ack( $action)
            ->add( 'tenants', $tens);

        }
        else {
          Json::nak( $action);

        }

      }
      else {
        Json::nak( $action);

      }

    }
    elseif ( 'get-tenants-lease' == $action) {
      /*
      ( _ => {
        _.post({
          url : _.url('leasing'),
          data : {
            action : 'get-tenants-lease',
            id : 37995

          }

        }).then( d => console.log('ack' == d.response ? d.lease : d))

      })(_brayworth_)

       */
      if ( $id = $this->getPost( 'id')) {
        $dao = new dao\tenants;
        if ( $lease = $dao->getTenantsLease( $id)) {
          Json::ack( $action)
            ->add( 'lease', $lease);

        }
        else {
          Json::nak( $action);

        }

      }
      else {
        Json::nak( $action);

      }

    }
    else {
			parent::postHandler();

    }

  }

	public function tenants() {
    $dao = new dao\tenants;
    $this->data = (object)[
      'tenants' => $dao->getCurrentTenants()

    ];

    $this->render([
      'title' => $this->title = 'Current Tenants',
      'primary' => 'tenants',
      'secondary' => 'index',
      'data' => (object)[
        'searchFocus' => false,
        'pageUrl' => strings::url( $this->route . '/tenants')

      ],

    ]);

  }

}