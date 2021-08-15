<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\property_maintenance\dao\dto;

use dao\dto\_dto;

class property_maintenance extends _dto {
	public $id = 0;
	public $properties_id = 0;
	public $people_id = 0;
	public $type = '';
	public $limit = '';
	public $notes = '';
	public $source = '';

}
