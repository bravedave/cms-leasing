<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

$dbc = sys::dbCheck('property_maintenance');

$dbc->defineField('created', 'datetime');
$dbc->defineField('updated', 'datetime');
$dbc->defineField('properties_id', 'bigint');
$dbc->defineField('people_id', 'bigint');
$dbc->defineField('type', 'varchar');
$dbc->defineField('limit', 'varchar');
$dbc->defineField('notes', 'text');

$dbc->check();
