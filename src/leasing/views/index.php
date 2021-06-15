<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms\tenants;

use strings;  ?>

<ul class="nav flex-column">
  <li class="nav-item h6"><a href="<?= strings::url( $this->route) ?>">Index</a></li>
  <li class="nav-item"><a href="<?= strings::url( $this->route . '/tenants') ?>" class="nav-link">Current Tenants</a></li>

</ul>
